<?php
use PHPUnit\Framework\TestCase;

class FileLogHandlerTest extends TestCase
{
    private $tmpFile;


    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'bebot_log_test_');
        // Let the handler create the file fresh via its own fopen("a").
        unlink($this->tmpFile);
    }


    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }


    public function testHandleAppendsFormattedLineToFixedPath()
    {
        $handler = new FileLogHandler($this->tmpFile);
        $record = new LogRecord(0, 'Mybot', 'GROUP', 'IN', 'hello', false);

        $handler->handle($record, "line one\n");
        $handler->handle($record, "line two\n");

        $this->assertSame("line one\nline two\n", file_get_contents($this->tmpFile));
    }


    public function testHandleResolvesCallablePathPerRecord()
    {
        $tmpFile = $this->tmpFile;
        $handler = new FileLogHandler(
            function (LogRecord $record) use ($tmpFile) {
                return $tmpFile;
            }
        );
        $record = new LogRecord(0, 'Mybot', 'GROUP', 'IN', 'hello', false);

        $handler->handle($record, "via closure\n");

        $this->assertSame("via closure\n", file_get_contents($this->tmpFile));
    }
}
