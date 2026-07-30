<?php
use PHPUnit\Framework\TestCase;

class PlainTextLogFormatterTest extends TestCase
{
    private function record($timestamp = 0)
    {
        return new LogRecord($timestamp, 'Mybot', 'GROUP', 'IN', 'hello world', false);
    }


    public function testNoneTimestampModeOmitsTimestamp()
    {
        $formatter = new PlainTextLogFormatter('none');

        $this->assertSame("[GROUP]\t[IN]\thello world\n", $formatter->format($this->record()));
    }


    public function testDateTimestampMode()
    {
        $formatter = new PlainTextLogFormatter('date');

        $this->assertSame("[1970-01-01]\t[GROUP]\t[IN]\thello world\n", $formatter->format($this->record()));
    }


    public function testTimeTimestampMode()
    {
        $formatter = new PlainTextLogFormatter('time');

        $this->assertSame("[00:00:00]\t[GROUP]\t[IN]\thello world\n", $formatter->format($this->record()));
    }


    public function testDatetimeTimestampMode()
    {
        $formatter = new PlainTextLogFormatter('datetime');

        $this->assertSame(
            "[1970-01-01 00:00:00]\t[GROUP]\t[IN]\thello world\n",
            $formatter->format($this->record())
        );
    }


    public function testUnrecognizedTimestampModeDefaultsToFullDatetime()
    {
        $formatter = new PlainTextLogFormatter('bogus-value');

        $this->assertSame(
            "[1970-01-01 00:00:00]\t[GROUP]\t[IN]\thello world\n",
            $formatter->format($this->record())
        );
    }
}
