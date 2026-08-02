<?php
use PHPUnit\Framework\TestCase;

/*
Coverage for Sources/Redis.php's stream_*() methods (added for Modules/BotPool.php's
work queue). Redis_Client's constructor requires a live Bot instance and either connects
to a real Redis server or leaves $enabled false - there's no existing seam for injecting
a fake CONN, so this exercises the real class via
ReflectionClass::newInstanceWithoutConstructor() (leaving $enabled at its default false,
same as a bot with no/unreachable Redis) to verify the actual fail-soft contract every
caller (BotPool included) relies on, rather than testing a mock.
*/
class RedisStreamTest extends TestCase
{
    private function disabledClient()
    {
        $reflection = new ReflectionClass("Redis_Client");
        return $reflection->newInstanceWithoutConstructor();
    }


    public function testStreamAddIsNoOpWhenDisabled()
    {
        $client = $this->disabledClient();
        $this->assertFalse($client->stream_add("stream", array("a" => "b")));
    }


    public function testStreamEnsureGroupIsNoOpWhenDisabled()
    {
        $client = $this->disabledClient();
        $this->assertFalse($client->stream_ensure_group("stream", "group"));
    }


    public function testStreamReadGroupReturnsEmptyArrayWhenDisabled()
    {
        $client = $this->disabledClient();
        $this->assertSame(array(), $client->stream_read_group("stream", "group", "consumer"));
    }


    public function testStreamAckIsNoOpWhenDisabled()
    {
        $client = $this->disabledClient();
        $this->assertFalse($client->stream_ack("stream", "group", array("1-0")));
    }
}
