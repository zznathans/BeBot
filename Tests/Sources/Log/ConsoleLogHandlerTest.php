<?php
use PHPUnit\Framework\TestCase;

class ConsoleLogHandlerTest extends TestCase
{
    /** handle() echoes the pre-formatted line unchanged. */
    public function testHandleEchoesFormattedLineVerbatim()
    {
        $handler = new ConsoleLogHandler();
        $record = new LogRecord(0, 'Mybot', 'GROUP', 'IN', 'hello', false);

        ob_start();
        $handler->handle($record, "Mybot [GROUP]\t[IN]\thello\n");
        $output = ob_get_clean();

        $this->assertSame("Mybot [GROUP]\t[IN]\thello\n", $output);
    }


    /** handle() doesn't prefix/corrupt an already-JSON-formatted log line (regression test). */
    public function testHandleDoesNotMangleJsonOutput()
    {
        $handler = new ConsoleLogHandler();
        $record = new LogRecord(0, 'Mybot', 'GROUP', 'IN', 'hello', false);

        ob_start();
        $handler->handle($record, "{\"bot\":\"Mybot\"}\n");
        $output = ob_get_clean();

        $this->assertSame("{\"bot\":\"Mybot\"}\n", $output);
    }
}
