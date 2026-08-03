<?php
/*
Minimal stand-in for the "player" core module - just enough of id() for Relay's
auto-buddy sync to resolve a character name to a numeric id without a real AO
connection. Unknown names resolve to a BotError, matching the real
Security_Core::id()/add_group_member() contract.
*/
class FakePlayerCore
{
    private $bot;
    private $ids = array();


    function __construct(&$bot)
    {
        $this->bot = $bot;
    }


    function seedId($name, $id)
    {
        $this->ids[$name] = $id;
    }


    function id($name)
    {
        if (isset($this->ids[$name])) {
            return $this->ids[$name];
        }
        return new BotError($this->bot, "player");
    }
}
