<?php
/*
* BotPool.php - Cross-bot outbound work queue, backed by a Redis Stream
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
*
* See Credits file for all acknowledgements.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License only.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
*  USA
*/
/*
Lets a pool of bot instances share outbound work: any module calls dispatch() to queue a
message (channel type/arg + text) instead of sending it directly, and whichever pool
member is free picks it up and sends it via its own AoChat connection. This is a genuine
competing-consumers work queue (each message handled by exactly one bot), not a
broadcast - every slave bot joins the *same* Redis consumer group, so Redis itself
distributes entries across whichever consumers are currently reading rather than every
bot seeing every message.

Unlike Modules/Relay.php's tell/pgroup-based cross-bot messaging, this never touches AO
chat for the handoff itself - the queue lives entirely in Redis (Sources/Redis.php's
stream_*() methods), polled from a cron tick rather than a blocking read since BeBot is a
single cooperative event loop. Requires bebot.redis.enabled - a bot with no/unreachable
Redis simply can't publish or consume (fails soft, same as every other Redis_Client
caller), it doesn't error.

Known gap (not addressed here): if a consumer reads an entry and crashes before acking
it, that entry stays pending in the group (visible via XPENDING) rather than being lost,
but nothing currently reclaims (XCLAIM) a long-pending entry from a dead consumer - a
message stranded that way needs a future follow-up, not silently ignored forever.
*/
$botpool = new BotPool($bot);
class BotPool extends BasePassiveModule
{
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("botpool");

        $this->bot->core("settings")->create(
            "BotPool",
            "Role",
            "slave",
            "Should this bot publish work (main), consume it (slave), or both?"
        );
        $this->bot->core("settings")->create(
            "BotPool",
            "StreamKey",
            "botpool:dispatch",
            "Redis stream key the pool shares - must match across every participating bot."
        );
        $this->bot->core("settings")->create(
            "BotPool",
            "GroupName",
            "botpool-workers",
            "Redis consumer group name - must match across every slave for them to compete for the same work."
        );

        if ($this->is_slave()) {
            $this->bot->redis->stream_ensure_group($this->stream_key(), $this->group_name());
        }

        $this->register_event("cron", "2sec");
    }


    function is_main()
    {
        $role = $this->bot->core("settings")->get("BotPool", "Role");
        return $role === "main" || $role === "both";
    }


    function is_slave()
    {
        $role = $this->bot->core("settings")->get("BotPool", "Role");
        return $role === "slave" || $role === "both";
    }


    function stream_key()
    {
        return $this->bot->core("settings")->get("BotPool", "StreamKey");
    }


    function group_name()
    {
        return $this->bot->core("settings")->get("BotPool", "GroupName");
    }


    // Queues an outbound message for whichever pool member picks it up. $channelType is
    // 'gc', 'pgroup', or 'tell'; $channelArg is the pgroup name / tell recipient (null for
    // 'gc'). Fire-and-forget from the caller's perspective - the stream's durability
    // means a slave that's briefly down still picks this up once it reconnects and reads,
    // there's nothing for the caller to retry or check.
    function dispatch($channelType, $channelArg, $msg, $color = null)
    {
        return $this->bot->redis->stream_add($this->stream_key(), array(
            "channelType" => $channelType,
            "channelArg" => $channelArg === null ? "" : $channelArg,
            "msg" => $msg,
            "color" => $color === null ? "" : $color,
        ));
    }


    function cron($cron)
    {
        if ($cron != 2 || !$this->is_slave()) {
            return;
        }

        $entries = $this->bot->redis->stream_read_group(
            $this->stream_key(),
            $this->group_name(),
            $this->bot->botname
        );

        $ackIds = array();
        foreach ($entries as $id => $fields) {
            $this->handle_entry($fields);
            $ackIds[] = $id;
        }

        if (!empty($ackIds)) {
            $this->bot->redis->stream_ack($this->stream_key(), $this->group_name(), $ackIds);
        }
    }


    function handle_entry($fields)
    {
        $channelType = isset($fields["channelType"]) ? $fields["channelType"] : "";
        $channelArg = isset($fields["channelArg"]) && $fields["channelArg"] !== "" ? $fields["channelArg"] : null;
        $msg = isset($fields["msg"]) ? $fields["msg"] : "";
        $color = isset($fields["color"]) && $fields["color"] !== "" ? $fields["color"] : null;

        switch ($channelType) {
            case "gc":
                $this->bot->send_gc($msg);
                break;
            case "pgroup":
                $this->bot->send_pgroup($msg, $channelArg, true, $color);
                break;
            case "tell":
                $this->bot->send_tell($channelArg, $msg, 0, false, true, $color);
                break;
        }
    }
}

?>
