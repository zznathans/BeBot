<?php
use PHPUnit\Framework\TestCase;

class LogDispatcherTest extends TestCase
{
    private $bot;
    private $logDir;


    protected function setUp(): void
    {
        $this->bot = new StubBot();
        $this->bot->db = new FakeDb();
        $this->logDir = sys_get_temp_dir() . '/bebot_dispatcher_test_' . uniqid();
        mkdir($this->logDir);
        $this->bot->log_path = $this->logDir;
    }


    protected function tearDown(): void
    {
        foreach (glob($this->logDir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->logDir);
    }


    /** With Log = off, a record is echoed to console but never written to any file or relayed. */
    public function testUnloggedCategoryOnlyEchoesToConsole()
    {
        $this->bot->log = 'off';
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'starting up', false));
        $output = ob_get_clean();

        $this->assertStringContainsString('starting up', $output);
        $this->assertEmpty(glob($this->logDir . '/*'));
        $this->assertEmpty($this->bot->gcMessages);
        $this->assertEmpty($this->bot->pgroupMessages);
    }


    /** A "security" category record is relayed to guild chat and appended to security.txt when the bot is a guild bot. */
    public function testSecurityCategoryRelaysToGuildChatAndWritesSecurityFile()
    {
        $this->bot->guildbot = true;
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'AUTH', 'security', 'unauthorized attempt', false));
        ob_get_clean();

        $this->assertCount(1, $this->bot->gcMessages);
        $this->assertStringContainsString('unauthorized attempt', $this->bot->gcMessages[0]);
        $this->assertFileExists($this->logDir . '/security.txt');
        $this->assertStringContainsString(
            'unauthorized attempt',
            file_get_contents($this->logDir . '/security.txt')
        );
    }


    /** The same relay goes to the private group instead when the bot isn't a guild bot. */
    public function testSecurityCategoryRelaysToPrivateGroupWhenNotGuildbot()
    {
        $this->bot->guildbot = false;
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'AUTH', 'SECURITY', 'blocked', false));
        ob_get_clean();

        $this->assertCount(1, $this->bot->pgroupMessages);
        $this->assertEmpty($this->bot->gcMessages);
    }


    /** With Log = chat, only chat-ish categories (group/tell/pgrp) get written to the daily log file. */
    public function testChatModeOnlyWritesGroupTellPgrpCategoriesToDailyFile()
    {
        $this->bot->log = 'chat';
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'GROUP', 'MSG', 'hi there', false));
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'not a chat category', false));
        ob_get_clean();

        $dailyFile = $this->logDir . '/' . gmdate('Y-m-d', 0) . '.txt';
        $this->assertFileExists($dailyFile);
        $contents = file_get_contents($dailyFile);
        $this->assertStringContainsString('hi there', $contents);
        $this->assertStringNotContainsString('not a chat category', $contents);
    }


    /** With Log = all, every category is written to the daily log file. */
    public function testAllModeWritesEveryCategoryToDailyFile()
    {
        $this->bot->log = 'all';
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'anything', false));
        ob_get_clean();

        $dailyFile = $this->logDir . '/' . gmdate('Y-m-d', 0) . '.txt';
        $this->assertStringContainsString('anything', file_get_contents($dailyFile));
    }


    /** A record with writeToDb = true results in a database insert. */
    public function testWriteToDbFlagInsertsIntoDatabase()
    {
        $this->bot->log = 'off';
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'db me', true));
        ob_get_clean();

        $this->assertCount(1, $this->bot->db->queries);
        $this->assertStringContainsString('db me', $this->bot->db->queries[0]);
    }


    /** Mirrors early boot (before Main/06_Settings.php exists): output defaults to plain text, not JSON. */
    public function testDefaultsToPlainTextWhenSettingsModuleIsNotRegisteredYet()
    {
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'anything', false));
        $output = ob_get_clean();

        $this->assertStringContainsString("[CORE]\t[STATUS]\tanything\n", $output);
        $this->assertStringNotContainsString('{', $output);
    }


    /** Settings module is registered, but Log.ConsoleFormat itself was never created/set - still defaults to plain text. */
    public function testDefaultsToPlainTextWhenSettingIsUnset()
    {
        $this->bot->setSettingsModule(new FakeSettings($this->bot));
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'anything', false));
        $output = ob_get_clean();

        $this->assertStringContainsString("[CORE]\t[STATUS]\tanything\n", $output);
    }


    /**
     * Regression test: on a bot's very first boot, Log.ConsoleFormat doesn't exist yet, and a
     * naive settings lookup that itself logs on a miss would recurse forever -
     * resolveFormatter() must never trigger that.
     */
    public function testDoesNotRecurseWhenLogSettingHasNeverBeenCreated()
    {
        // RecursingFakeSettings::get() reproduces Settings_Core::get()'s real behavior:
        // logging a Bot->log("ERROR", ...) side effect on a miss before returning. On a
        // bot's very first boot, Log.ConsoleFormat doesn't exist yet - resolveFormatter()
        // must never call get() in that case, or that side effect re-enters dispatch()
        // and recurses without end (StubBot has no log() method, so if this regresses,
        // the recursive call fatals immediately instead of hanging).
        $settings = new RecursingFakeSettings($this->bot);
        $this->bot->setSettingsModule($settings);
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'anything', false));
        $output = ob_get_clean();

        $this->assertStringContainsString("[CORE]\t[STATUS]\tanything\n", $output);
        $this->assertSame(0, $settings->getCallCount());
    }


    /** Log.ConsoleFormat = json switches console output to JSON. */
    public function testConsoleFormatSettingSwitchesConsoleOutputToJson()
    {
        $settings = new FakeSettings($this->bot);
        $settings->set('Log', 'ConsoleFormat', 'json');
        $this->bot->setSettingsModule($settings);
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'anything', false));
        $output = ob_get_clean();

        $this->assertStringContainsString('"category":"CORE"', $output);
        $this->assertStringContainsString('"message":"anything"', $output);
    }


    /** Log.FileFormat can be set to json while Log.ConsoleFormat stays unset/plain - the two are independent. */
    public function testFileFormatSettingIsIndependentOfConsoleFormat()
    {
        $this->bot->log = 'all';
        $settings = new FakeSettings($this->bot);
        $settings->set('Log', 'FileFormat', 'json');
        // ConsoleFormat deliberately left unset - should stay plain.
        $this->bot->setSettingsModule($settings);
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'CORE', 'STATUS', 'anything', false));
        $consoleOutput = ob_get_clean();

        $dailyFile = $this->logDir . '/' . gmdate('Y-m-d', 0) . '.txt';
        $fileContents = file_get_contents($dailyFile);

        $this->assertStringContainsString("[CORE]\t[STATUS]\tanything\n", $consoleOutput);
        $this->assertStringContainsString('"category":"CORE"', $fileContents);
    }


    /** Log.SecurityFileFormat = json only affects security.txt - the in-game relay text stays plain. */
    public function testSecurityFileFormatCanBeJsonWhileInGameRelayStaysPlain()
    {
        $settings = new FakeSettings($this->bot);
        $settings->set('Log', 'SecurityFileFormat', 'json');
        $this->bot->setSettingsModule($settings);
        $dispatcher = new LogDispatcher($this->bot);

        ob_start();
        $dispatcher->dispatch(new LogRecord(0, 'Mybot', 'AUTH', 'security', 'unauthorized attempt', false));
        ob_get_clean();

        $securityFileContents = file_get_contents($this->logDir . '/security.txt');
        $this->assertStringContainsString('"message":"unauthorized attempt"', $securityFileContents);
        $this->assertCount(1, $this->bot->gcMessages);
        $this->assertStringContainsString("[AUTH]\t[security]\tunauthorized attempt\n", $this->bot->gcMessages[0]);
    }
}
