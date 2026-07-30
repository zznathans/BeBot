<?php
use PHPUnit\Framework\TestCase;

class JsonLogFormatterTest extends TestCase
{
    public function testFormatProducesOneJsonObjectPerLine()
    {
        $formatter = new JsonLogFormatter();
        $record = new LogRecord(0, 'Mybot', 'GROUP', 'IN', "hello 'world'", false);

        $line = $formatter->format($record);

        $this->assertStringEndsWith("\n", $line);
        $decoded = json_decode(rtrim($line, "\n"), true);
        $this->assertNotNull($decoded, 'formatted line should be valid JSON');
        $this->assertSame(
            array(
                'timestamp' => '1970-01-01T00:00:00+00:00',
                'bot' => 'Mybot',
                'category' => 'GROUP',
                'subtag' => 'IN',
                'message' => "hello 'world'",
            ),
            $decoded
        );
    }
}
