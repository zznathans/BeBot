<?php
/*
Minimal stand-in for the Bot class, exposing just enough for Modules/Relay.php's
constructor and connect() (register_command()/register_event()/dispatcher->connect()
from BaseActiveModule/BasePassiveModule, settings->create()/get(), db
query()/select(), core("chat")->pgroup_invite()) to run without a real AO/AoC
connection or database.
*/
class FakeRelayBot
{
    public $botname = "TestBot";
    public $db;
    public $settings;
    public $chat;
    public $dispatcher;
    public $guildname = "TestGuild";


    function __construct()
    {
        $this->db = new FakeRelayDb();
        $this->settings = new FakeSettingsCore();
        $this->chat = new FakeChatCore();
        $this->dispatcher = new FakeDispatcher();
    }


    function core($name)
    {
        switch (strtolower($name)) {
            case "settings":
                return $this->settings;
            case "chat":
                return $this->chat;
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


    function register_event($event, $target, &$module)
    {
    }


    function register_module($module, $name)
    {
    }
}
