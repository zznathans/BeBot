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
}
