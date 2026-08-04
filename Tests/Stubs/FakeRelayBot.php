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
    public $security;
    public $player;
    public $guildname = "TestGuild";
    public $sentPgroup = array();
    public $sentTells = array();
    public $sentGc = array();


    function __construct()
    {
        $this->db = new FakeRelayDb();
        $this->settings = new FakeSettingsCore();
        $this->chat = new FakeChatCore();
        $this->dispatcher = new FakeDispatcher();
        $this->security = new FakeSecurityCore();
        $this->player = new FakePlayerCore($this);
    }


    function send_pgroup($msg, $group = null, $checksize = true, $parsecolors = true)
    {
        $this->sentPgroup[] = array('msg' => $msg, 'group' => $group);
    }


    function send_tell($name, $msg, $low = 0, $checksize = true, $blob = false, $color = false)
    {
        $this->sentTells[] = array('name' => $name, 'msg' => $msg);
    }


    function send_gc($msg, $low = 0, $checksize = true)
    {
        $this->sentGc[] = array('msg' => $msg);
    }


    function exists_module($name)
    {
        return false;
    }


    function core($name)
    {
        switch (strtolower($name)) {
            case "settings":
                return $this->settings;
            case "chat":
                return $this->chat;
            case "security":
                return $this->security;
            case "player":
                return $this->player;
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
