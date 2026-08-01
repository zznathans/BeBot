<?php
/*
Minimal stand-in for the Bot class, exposing just enough for
Modules/Ao/Market.php's constructor (register_command()/register_alias()
from BaseActiveModule, settings->create()/get(), db->query() for table(),
timer registration) to run without a real AO/AoC connection or database.
Every module-instantiation call this bot doesn't specifically need to
observe is swallowed by FakeNoOpCore.
*/
class FakeMarketBot
{
    public $db;
    public $settings;
    public $timer;


    function __construct()
    {
        $this->db = new FakeDb();
        $this->settings = new FakeSettingsCore();
        $this->timer = new FakeTimerCore();
    }


    function core($name)
    {
        switch (strtolower($name)) {
            case 'settings':
                return $this->settings;
            case 'timer':
                return $this->timer;
            default:
                return new FakeNoOpCore();
        }
    }


    function exists_command($channel, $command)
    {
        return false;
    }


    function register_command($channel, $command, &$module)
    {
    }
}
