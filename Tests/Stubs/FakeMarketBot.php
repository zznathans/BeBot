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
    public $botname = "TestBot";
    public $sentPgroup = array();
    public $relayModuleExists = false;
    public $relay;


    function __construct()
    {
        $this->db = new FakeDb();
        $this->settings = new FakeSettingsCore();
        $this->timer = new FakeTimerCore();
        $this->relay = new FakeMarketRelayCore();
    }


    function core($name)
    {
        switch (strtolower($name)) {
            case 'settings':
                return $this->settings;
            case 'timer':
                return $this->timer;
            case 'relay':
                return $this->relay;
            default:
                return new FakeNoOpCore();
        }
    }


    function exists_command($channel, $command)
    {
        return false;
    }


    function exists_module($name)
    {
        return $this->relayModuleExists;
    }


    function register_command($channel, $command, &$module)
    {
    }


    function send_pgroup($msg, $group = null, $checksize = true, $parsecolors = true)
    {
        $this->sentPgroup[] = array('msg' => $msg, 'group' => $group);
    }
}


/*
Records calls to relay_command_output() so tests can assert Market's
announce()/announce_background() reach the relay module (or don't) without
needing a real Modules/Relay.php instance.
*/
class FakeMarketRelayCore
{
    public $relayedCommandOutput = array();


    function relay_command_output($name, $msg)
    {
        $this->relayedCommandOutput[] = array('name' => $name, 'msg' => $msg);
    }
}
