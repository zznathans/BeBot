<?php
use PHPUnit\Framework\TestCase;

/*
Coverage for Modules/BotPool.php's competing-consumers work queue: role gating
(main/slave/both control whether a bot joins the consumer group and/or polls it),
dispatch()'s published payload shape, and the consume loop's channel-type dispatch +
ack-everything-read behavior.
*/
class BotPoolTest extends TestCase
{
    /** Default role (slave) joins the consumer group on construction. */
    public function testDefaultRoleJoinsConsumerGroupOnConstruct()
    {
        $bot = new FakeBotPoolBot();
        new BotPool($bot);

        $this->assertCount(1, $bot->redis->ensuredGroups);
        $this->assertSame("botpool:dispatch", $bot->redis->ensuredGroups[0]["stream"]);
        $this->assertSame("botpool-workers", $bot->redis->ensuredGroups[0]["group"]);
    }


    /** A main-only bot never joins the consumer group - it only publishes. */
    public function testMainOnlyRoleDoesNotJoinConsumerGroup()
    {
        $bot = new FakeBotPoolBot();
        $bot->settings->set("BotPool", "Role", "main");
        new BotPool($bot);

        $this->assertCount(0, $bot->redis->ensuredGroups);
    }


    /** "both" joins the consumer group same as "slave". */
    public function testBothRoleJoinsConsumerGroup()
    {
        $bot = new FakeBotPoolBot();
        $bot->settings->set("BotPool", "Role", "both");
        new BotPool($bot);

        $this->assertCount(1, $bot->redis->ensuredGroups);
    }


    /** dispatch() XADDs the message fields, converting null channelArg/color to "". */
    public function testDispatchPublishesExpectedFields()
    {
        $bot = new FakeBotPoolBot();
        $botpool = new BotPool($bot);

        $botpool->dispatch("tell", "SomePlayer", "hello", "FF0000");
        $botpool->dispatch("gc", null, "broadcast", null);

        $this->assertCount(2, $bot->redis->addedEntries);

        $first = $bot->redis->addedEntries[0];
        $this->assertSame("botpool:dispatch", $first["stream"]);
        $this->assertSame(
            array("channelType" => "tell", "channelArg" => "SomePlayer", "msg" => "hello", "color" => "FF0000"),
            $first["fields"]
        );

        $second = $bot->redis->addedEntries[1];
        $this->assertSame(
            array("channelType" => "gc", "channelArg" => "", "msg" => "broadcast", "color" => ""),
            $second["fields"]
        );
    }


    /** dispatch() respects a custom StreamKey setting. */
    public function testDispatchUsesConfiguredStreamKey()
    {
        $bot = new FakeBotPoolBot();
        $bot->settings->set("BotPool", "StreamKey", "custom:stream");
        $botpool = new BotPool($bot);

        $botpool->dispatch("gc", null, "hi");

        $this->assertSame("custom:stream", $bot->redis->addedEntries[0]["stream"]);
    }


    /** cron(2) on a slave reads the group, dispatches each entry via the right channel, and acks all of them. */
    public function testCronTwoSecReadsDispatchesAndAcksEntries()
    {
        $bot = new FakeBotPoolBot();
        $botpool = new BotPool($bot);

        $bot->redis->seedReadEntries(array(
            array("channelType" => "gc", "channelArg" => "", "msg" => "gc message", "color" => ""),
            array("channelType" => "pgroup", "channelArg" => "MyGroup", "msg" => "pg message", "color" => ""),
            array("channelType" => "tell", "channelArg" => "SomePlayer", "msg" => "tell message", "color" => "00FF00"),
        ));

        $botpool->cron(2);

        $this->assertSame(array("gc message"), $bot->gcMessages);
        $this->assertCount(1, $bot->pgroupMessages);
        $this->assertSame("pg message", $bot->pgroupMessages[0]["msg"]);
        $this->assertSame("MyGroup", $bot->pgroupMessages[0]["group"]);
        $this->assertCount(1, $bot->tellMessages);
        $this->assertSame("SomePlayer", $bot->tellMessages[0]["name"]);
        $this->assertSame("tell message", $bot->tellMessages[0]["msg"]);
        $this->assertSame("00FF00", $bot->tellMessages[0]["color"]);

        $this->assertCount(1, $bot->redis->ackedCalls);
        $this->assertCount(3, $bot->redis->ackedCalls[0]["ids"]);
    }


    /** A main-only bot never even reads the group on a cron tick. */
    public function testCronTwoSecDoesNothingForMainOnlyRole()
    {
        $bot = new FakeBotPoolBot();
        $bot->settings->set("BotPool", "Role", "main");
        $botpool = new BotPool($bot);

        $bot->redis->seedReadEntries(array(
            array("channelType" => "gc", "channelArg" => "", "msg" => "should not be sent", "color" => ""),
        ));

        $botpool->cron(2);

        $this->assertCount(0, $bot->redis->readGroupCalls);
        $this->assertCount(0, $bot->gcMessages);
    }


    /** Other cron intervals (e.g. 5min = 300) are ignored. */
    public function testCronIgnoresOtherIntervals()
    {
        $bot = new FakeBotPoolBot();
        $botpool = new BotPool($bot);

        $bot->redis->seedReadEntries(array(
            array("channelType" => "gc", "channelArg" => "", "msg" => "should not be sent", "color" => ""),
        ));

        $botpool->cron(300);

        $this->assertCount(0, $bot->redis->readGroupCalls);
        $this->assertCount(0, $bot->gcMessages);
    }


    /** No entries read means no ack call at all (not an ack with an empty id list). */
    public function testCronDoesNotAckWhenNoEntriesRead()
    {
        $bot = new FakeBotPoolBot();
        $botpool = new BotPool($bot);

        $botpool->cron(2);

        $this->assertCount(1, $bot->redis->readGroupCalls);
        $this->assertCount(0, $bot->redis->ackedCalls);
    }
}
