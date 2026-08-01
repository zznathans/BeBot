<?php
/*
Minimal stand-in for Timer_Core: register_callback() is a no-op (nothing in
Market's constructor path depends on it doing anything observable),
add_timer() just records what it was called with so a test can assert on
which internal timers a module (re)registered and with what interval.
*/
class FakeTimerCore
{
    public $addedTimers = array();


    function register_callback($modulename, &$module)
    {
    }


    function add_timer($relaying, $owner, $duration, $name, $channel, $repeat, $class = "")
    {
        $this->addedTimers[] = array(
            "owner" => $owner,
            "duration" => $duration,
            "name" => $name,
            "channel" => $channel,
            "repeat" => $repeat,
            "class" => $class
        );
        return count($this->addedTimers);
    }
}
