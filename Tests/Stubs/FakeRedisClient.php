<?php
/*
Minimal stand-in for Sources/Redis.php's Redis_Client, covering just the stream_*()
methods Modules/BotPool.php uses. Records every call so tests can assert on what was
published/acked, and lets a test preload what the next stream_read_group() call should
return (simulating entries already sitting in the queue).
*/
class FakeRedisClient
{
    public $enabled = true;

    public $addedEntries = array();
    public $ensuredGroups = array();
    public $readGroupCalls = array();
    public $ackedCalls = array();

    private $nextReadEntries = array();
    private $nextId = 1;


    // Test setup: make the next stream_read_group() call return these entries
    // (field arrays), each auto-assigned a fake stream id.
    function seedReadEntries(array $entryFieldsList)
    {
        $this->nextReadEntries = array();
        foreach ($entryFieldsList as $fields) {
            $this->nextReadEntries[(string) $this->nextId] = $fields;
            $this->nextId++;
        }
    }


    function stream_add($stream, array $fields)
    {
        $id = (string) $this->nextId++;
        $this->addedEntries[] = array("stream" => $stream, "fields" => $fields, "id" => $id);
        return $id;
    }


    function stream_ensure_group($stream, $group)
    {
        $this->ensuredGroups[] = array("stream" => $stream, "group" => $group);
        return true;
    }


    function stream_read_group($stream, $group, $consumer, $count = 10)
    {
        $this->readGroupCalls[] = array("stream" => $stream, "group" => $group, "consumer" => $consumer);
        $entries = $this->nextReadEntries;
        $this->nextReadEntries = array();
        return $entries;
    }


    function stream_ack($stream, $group, $ids)
    {
        $this->ackedCalls[] = array("stream" => $stream, "group" => $group, "ids" => $ids);
        return count($ids);
    }
}
