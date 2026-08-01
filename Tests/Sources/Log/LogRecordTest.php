<?php
use PHPUnit\Framework\TestCase;

class LogRecordTest extends TestCase
{
    /** The constructor assigns timestamp/botname/first/second/message/writeToDb correctly. */
    public function testConstructorAssignsAllFields()
    {
        $record = new LogRecord(12345, 'Mybot', 'GROUP', 'IN', 'hello', true);

        $this->assertSame(12345, $record->timestamp);
        $this->assertSame('Mybot', $record->botname);
        $this->assertSame('GROUP', $record->first);
        $this->assertSame('IN', $record->second);
        $this->assertSame('hello', $record->message);
        $this->assertTrue($record->writeToDb);
    }
}
