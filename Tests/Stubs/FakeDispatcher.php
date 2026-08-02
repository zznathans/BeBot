<?php
/*
Minimal stand-in for $bot->dispatcher, just enough for BasePassiveModule-derived
constructors that hook an event via $this->bot->dispatcher->connect(...).
*/
class FakeDispatcher
{
    function connect($event, $callback)
    {
    }
}
