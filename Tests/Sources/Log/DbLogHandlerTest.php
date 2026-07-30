<?php
use PHPUnit\Framework\TestCase;

class DbLogHandlerTest extends TestCase
{
    public function testHandleEscapesFirstAndSecondAsWellAsMessage()
    {
        $db = new FakeDb();
        $handler = new DbLogHandler($db);
        $record = new LogRecord(1700000000, "Mybot", "GRO'UP", "IN'", "hello 'world'", true);

        $handler->handle($record, "ignored formatted string");

        $this->assertCount(1, $db->queries);
        $query = $db->queries[0];
        $this->assertStringContainsString($db->real_escape_string("GRO'UP"), $query);
        $this->assertStringContainsString($db->real_escape_string("IN'"), $query);
        $this->assertStringContainsString($db->real_escape_string("hello 'world'"), $query);
        $this->assertStringContainsString('1700000000', $query);
    }


    public function testHandleTruncatesMessageTo500Characters()
    {
        $db = new FakeDb();
        $handler = new DbLogHandler($db);
        $longMessage = str_repeat('a', 600);
        $record = new LogRecord(0, "Mybot", "GROUP", "IN", $longMessage, true);

        $handler->handle($record, "ignored");

        $this->assertStringContainsString(str_repeat('a', 500), $db->queries[0]);
        $this->assertStringNotContainsString(str_repeat('a', 501), $db->queries[0]);
    }


    public function testHandleStoresRawMessageNotTheFormattedLine()
    {
        $db = new FakeDb();
        $handler = new DbLogHandler($db);
        $record = new LogRecord(0, "Mybot", "GROUP", "IN", "raw message", true);

        $handler->handle($record, "totally different formatted line");

        $this->assertStringContainsString('raw message', $db->queries[0]);
        $this->assertStringNotContainsString('totally different formatted line', $db->queries[0]);
    }
}
