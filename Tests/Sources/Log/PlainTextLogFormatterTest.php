<?php
use PHPUnit\Framework\TestCase;

class PlainTextLogFormatterTest extends TestCase
{
    private function record($timestamp = 0)
    {
        return new LogRecord($timestamp, 'Mybot', 'GROUP', 'IN', 'hello world', false);
    }


    /** "none" timestamp mode omits the timestamp entirely. */
    public function testNoneTimestampModeOmitsTimestamp()
    {
        $formatter = new PlainTextLogFormatter('none');

        $this->assertSame("Mybot [GROUP]\t[IN]\thello world\n", $formatter->format($this->record()));
    }


    /** "date" mode formats as YYYY-MM-DD. */
    public function testDateTimestampMode()
    {
        $formatter = new PlainTextLogFormatter('date');

        $this->assertSame("Mybot [1970-01-01]\t[GROUP]\t[IN]\thello world\n", $formatter->format($this->record()));
    }


    /** "time" mode formats as HH:MM:SS. */
    public function testTimeTimestampMode()
    {
        $formatter = new PlainTextLogFormatter('time');

        $this->assertSame("Mybot [00:00:00]\t[GROUP]\t[IN]\thello world\n", $formatter->format($this->record()));
    }


    /** "datetime" mode formats as YYYY-MM-DD HH:MM:SS. */
    public function testDatetimeTimestampMode()
    {
        $formatter = new PlainTextLogFormatter('datetime');

        $this->assertSame(
            "Mybot [1970-01-01 00:00:00]\t[GROUP]\t[IN]\thello world\n",
            $formatter->format($this->record())
        );
    }


    /** An unrecognized timestamp mode value falls back to full datetime rather than erroring. */
    public function testUnrecognizedTimestampModeDefaultsToFullDatetime()
    {
        $formatter = new PlainTextLogFormatter('bogus-value');

        $this->assertSame(
            "Mybot [1970-01-01 00:00:00]\t[GROUP]\t[IN]\thello world\n",
            $formatter->format($this->record())
        );
    }
}
