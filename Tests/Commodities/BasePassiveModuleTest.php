<?php
use PHPUnit\Framework\TestCase;

/*
Coverage for output_destination()'s relay-command-output hook
(Commodities/00_BasePassiveModule.php). A bot's own command reply never
passes back through Modules/Relay.php's normal privgroup()/gmsg() event
handlers - Sources/Bot.php's anti-echo-loop guard explicitly ignores a
private-group message whose sender is the bot itself, to avoid feeding a
bot's own relayed output back into its command dispatcher as new input.
output_destination() is therefore the one place command replies can be
hooked to also reach Relay::relay_command_output() - these tests pin which
channels that hook fires for (TELL and PG, both of which a reply can
realistically be sent on) and confirm it never double-fires when a reply
sets more than one of those bits at once.
*/
class BasePassiveModuleTest extends TestCase
{
    /** A tell-channel reply relays its output when the relay module exists. */
    public function testTellReplyRelaysCommandOutputWhenRelayModuleExists()
    {
        $bot = new FakeOutputDestinationBot();
        $bot->relayModuleExists = true;
        $module = new FakeOutputDestinationModule($bot);
        $module->replyText = "market results";

        $module->tell("Dbengineer", "market status");

        $this->assertCount(1, $bot->sentTells);
        $this->assertCount(1, $bot->relay->relayedCommandOutput);
        $this->assertStringContainsString("market results", $bot->relay->relayedCommandOutput[0]['msg']);
    }


    /** A tell-channel reply is still sent locally, but never relayed, when no relay module exists. */
    public function testTellReplyDoesNotRelayWhenRelayModuleMissing()
    {
        $bot = new FakeOutputDestinationBot();
        $bot->relayModuleExists = false;
        $module = new FakeOutputDestinationModule($bot);

        $module->tell("Dbengineer", "market status");

        $this->assertCount(1, $bot->sentTells);
        $this->assertSame(array(), $bot->relay->relayedCommandOutput);
    }


    /** A private-group-channel reply relays its output when the relay module exists. */
    public function testPgmsgReplyRelaysCommandOutputWhenRelayModuleExists()
    {
        $bot = new FakeOutputDestinationBot();
        $bot->relayModuleExists = true;
        $module = new FakeOutputDestinationModule($bot);
        $module->replyText = "market results";

        $module->pgmsg("Dbengineer", "market status");

        $this->assertCount(1, $bot->sentPgroup);
        $this->assertCount(1, $bot->relay->relayedCommandOutput);
        $this->assertStringContainsString("market results", $bot->relay->relayedCommandOutput[0]['msg']);
    }


    /** A guild-chat-channel reply is never relayed by this hook - guild chat already has its own relay path. */
    public function testGcReplyDoesNotRelay()
    {
        $bot = new FakeOutputDestinationBot();
        $bot->relayModuleExists = true;
        $module = new FakeOutputDestinationModule($bot);

        $module->gc("Dbengineer", "market status");

        $this->assertCount(1, $bot->sentGc);
        $this->assertSame(array(), $bot->relay->relayedCommandOutput);
    }


    /** A reply that sets both the TELL and PG bits at once still only relays once, not twice. */
    public function testRelayFiresExactlyOnceWhenBothTellAndPgBitsSet()
    {
        $bot = new FakeOutputDestinationBot();
        $bot->relayModuleExists = true;
        $module = new FakeOutputDestinationModule($bot);

        $module->callOutputDestination("Dbengineer", "market results", TELL | PG);

        $this->assertCount(1, $bot->sentTells);
        $this->assertCount(1, $bot->sentPgroup);
        $this->assertCount(1, $bot->relay->relayedCommandOutput);
    }
}
