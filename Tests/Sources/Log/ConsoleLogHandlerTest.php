<?php
use PHPUnit\Framework\TestCase;

class ConsoleLogHandlerTest extends TestCase
{
    public function testHandlePrefixesFormattedLineWithBotname()
    {
        $handler = new ConsoleLogHandler();
        $record = new LogRecord(0, 'Mybot', 'GROUP', 'IN', 'hello', false);

        ob_start();
        $handler->handle($record, "[GROUP]\t[IN]\thello\n");
        $output = ob_get_clean();

        $this->assertSame("Mybot [GROUP]\t[IN]\thello\n", $output);
    }
}
