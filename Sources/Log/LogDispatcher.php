<?php
/*
* LogDispatcher.php - Resolves formatters/handlers and fans a LogRecord out
* to the console, security file, daily file, and database as appropriate.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
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
class LogDispatcher
{
    // Maps a destination to the Log.* setting name that selects its format.
    const FORMAT_SETTINGS = array(
        'console' => 'ConsoleFormat',
        'file' => 'FileFormat',
        'security' => 'SecurityFileFormat',
    );

    private $bot;
    private $plainFormatter;
    private $jsonFormatter;
    private $consoleHandler;
    private $securityFileHandler;
    private $dailyFileHandler;
    private $dbHandler;


    function __construct($bot)
    {
        $this->bot = $bot;
        $this->plainFormatter = new PlainTextLogFormatter($bot->log_timestamp);
        $this->jsonFormatter = new JsonLogFormatter();
        $this->consoleHandler = new ConsoleLogHandler();
        $this->securityFileHandler = new FileLogHandler($bot->log_path . "/security.txt");
        $this->dailyFileHandler = new FileLogHandler(
            function (LogRecord $record) use ($bot) {
                return $bot->log_path . "/" . gmdate("Y-m-d", $record->timestamp) . ".txt";
            }
        );
        $this->dbHandler = new DbLogHandler($bot->db);
    }


    function dispatch(LogRecord $record)
    {
        $this->consoleHandler->handle($record, $this->resolveFormatter('console')->format($record));

        // We have a possible security related event.
        // Log to the security log and notify guildchat/pgroup. The in-game relay
        // always uses plain text, regardless of the configured file format,
        // since players can't usefully read a raw JSON line in chat.
        if (preg_match("/^security$/i", $record->second)) {
            $plainLine = $this->plainFormatter->format($record);
            if ($this->bot->guildbot) {
                $this->bot->send_gc($plainLine);
            } else {
                $this->bot->send_pgroup($plainLine);
            }
            $this->securityFileHandler->handle($record, $this->resolveFormatter('security')->format($record));
        }

        if (($this->bot->log == "all")
            || (($this->bot->log == "chat")
                && (($record->first == "GROUP") || ($record->first == "TELL") || ($record->first == "PGRP")))
        ) {
            $this->dailyFileHandler->handle($record, $this->resolveFormatter('file')->format($record));
        }

        if ($record->writeToDb) {
            // DbLogHandler ignores the formatted string and stores the raw message,
            // since the DB row already has separate first/second/timestamp columns.
            $this->dbHandler->handle($record, null);
        }
    }


    /*
    Resolves which formatter applies to $destination ("console"/"file"/"security"),
    re-reading the corresponding Log.* setting on every call so format changes made
    in-game via !settings take effect immediately, with no restart needed. Falls
    back to plain text if the settings module isn't registered yet (early boot,
    before Main/06_Settings.php has loaded), the Log.* setting hasn't been created yet
    (a bot's very first boot, before Main/15_Log.php has run), or the setting isn't
    set to "json".

    The exists() check before get() is load-bearing, not just an optimization: on a
    brand-new bot's first boot, Log.ConsoleFormat doesn't exist yet, and
    Settings_Core::get() logs a Bot->log("ERROR", ...) side effect on a miss (via
    BotError::set()) before returning - which re-enters dispatch() -> resolveFormatter()
    -> get() and recurses without end until the process is killed. get()'s
    `instanceof BotError` check on its return value can't prevent this, since the
    recursion happens inside the call, before it ever returns.
    */
    private function resolveFormatter($destination)
    {
        if (!$this->bot->exists_module('settings')) {
            return $this->plainFormatter;
        }
        $settings = $this->bot->core('settings');
        if (!$settings->exists('Log', self::FORMAT_SETTINGS[$destination])) {
            return $this->plainFormatter;
        }
        $value = $settings->get('Log', self::FORMAT_SETTINGS[$destination]);
        if ($value instanceof BotError) {
            return $this->plainFormatter;
        }
        if (strtolower($value) === 'json') {
            return $this->jsonFormatter;
        }
        return $this->plainFormatter;
    }
}

?>
