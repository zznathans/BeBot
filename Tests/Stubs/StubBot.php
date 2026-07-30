<?php
/*
Minimal stand-in for the Bot class, exposing only what LogDispatcher
reads/calls, so the dispatch fan-out can be tested without booting a
real Bot instance (which requires a live AO/AoC connection and DB).
*/
class StubBot
{
    public $log_timestamp = 'none';
    public $log_path;
    public $log = 'chat';
    public $guildbot = true;
    public $db;

    public $gcMessages = array();
    public $pgroupMessages = array();


    function send_gc($msg, $low = 0, $checksize = true)
    {
        $this->gcMessages[] = $msg;
    }


    function send_pgroup($msg, $group = null, $checksize = true, $parsecolors = true)
    {
        $this->pgroupMessages[] = $msg;
    }
}
