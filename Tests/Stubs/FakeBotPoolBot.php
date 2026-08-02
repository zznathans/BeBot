<?php
/*
Minimal stand-in for the Bot class, exposing just enough for
Modules/BotPool.php's constructor and behavior (settings->create()/get(), redis
stream_*() calls, send_gc()/send_pgroup()/send_tell(), event/module registration) to run
without a real AO/AoC connection, database, or Redis server.
*/
class FakeBotPoolBot
{
    public $botname = "TestBot";
    public $redis;
    public $settings;

    public $gcMessages = array();
    public $pgroupMessages = array();
    public $tellMessages = array();


    function __construct()
    {
        $this->redis = new FakeRedisClient();
        $this->settings = new FakeSettingsCore();
    }


    function core($name)
    {
        if (strtolower($name) === "settings") {
            return $this->settings;
        }
        return new FakeNoOpCore();
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


    function send_gc($msg, $low = 0, $checksize = true)
    {
        $this->gcMessages[] = $msg;
    }


    function send_pgroup($msg, $group = null, $checksize = true, $parsecolors = true)
    {
        $this->pgroupMessages[] = array("msg" => $msg, "group" => $group);
    }


    function send_tell($name, $msg, $priority = 0, $window = false, $checksize = true, $color = null)
    {
        $this->tellMessages[] = array("name" => $name, "msg" => $msg, "color" => $color);
    }
}
