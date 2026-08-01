<?php
use PHPUnit\Framework\TestCase;

class DbLogHandlerTest extends TestCase
{
    /** Category/subtag/message values are SQL-escaped before insertion. */
    public function testHandleEscapesFirstAndSecondAsWellAsMessage()
    {
        $db = new FakeDb();
        $bot = new StubBot();
        $bot->db = $db;
        $handler = new DbLogHandler($bot);
        $record = new LogRecord(1700000000, "Mybot", "GRO'UP", "IN'", "hello 'world'", true);

        $handler->handle($record, "ignored formatted string");

        $this->assertCount(1, $db->queries);
        $query = $db->queries[0];
        $this->assertStringContainsString($db->real_escape_string("GRO'UP"), $query);
        $this->assertStringContainsString($db->real_escape_string("IN'"), $query);
        $this->assertStringContainsString($db->real_escape_string("hello 'world'"), $query);
        $this->assertStringContainsString('1700000000', $query);
    }


    /** The message field is truncated to fit its column width. */
    public function testHandleTruncatesMessageTo500Characters()
    {
        $db = new FakeDb();
        $bot = new StubBot();
        $bot->db = $db;
        $handler = new DbLogHandler($bot);
        $longMessage = str_repeat('a', 600);
        $record = new LogRecord(0, "Mybot", "GROUP", "IN", $longMessage, true);

        $handler->handle($record, "ignored");

        $this->assertStringContainsString(str_repeat('a', 500), $db->queries[0]);
        $this->assertStringNotContainsString(str_repeat('a', 501), $db->queries[0]);
    }


    /** The raw message is stored, not the formatter's rendered/decorated line. */
    public function testHandleStoresRawMessageNotTheFormattedLine()
    {
        $db = new FakeDb();
        $bot = new StubBot();
        $bot->db = $db;
        $handler = new DbLogHandler($bot);
        $record = new LogRecord(0, "Mybot", "GROUP", "IN", "raw message", true);

        $handler->handle($record, "totally different formatted line");

        $this->assertStringContainsString('raw message', $db->queries[0]);
        $this->assertStringNotContainsString('totally different formatted line', $db->queries[0]);
    }


    /**
     * The database reference is read at handle()-time, not captured once at construction -
     * avoids permanently caching a null db from before the bot's DB connection is ready.
     */
    public function testReadsDbLazilyAtHandleTimeNotAtConstruction()
    {
        $bot = new StubBot();
        $bot->db = null;
        $handler = new DbLogHandler($bot);

        $bot->db = new FakeDb();
        $record = new LogRecord(0, "Mybot", "GROUP", "IN", "after connect", true);
        $handler->handle($record, "ignored");

        $this->assertCount(1, $bot->db->queries);
        $this->assertStringContainsString('after connect', $bot->db->queries[0]);
    }
}
