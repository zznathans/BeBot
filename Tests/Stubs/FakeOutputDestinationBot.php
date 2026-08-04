<?php
/*
Minimal stand-in for the Bot class, exposing just enough for
Commodities/00_BasePassiveModule.php's output_destination() (send_tell(),
send_gc(), send_pgroup(), exists_module(), core("relay")) to be exercised
through a concrete BaseActiveModule subclass's tell()/gc()/pgmsg() entry
points, without a real AO/AoC connection.
*/
class FakeOutputDestinationBot
{
    public $botname = "TestBot";
    public $sentTells = array();
    public $sentGc = array();
    public $sentPgroup = array();
    public $relayModuleExists = false;
    public $relay;


    function __construct()
    {
        $this->relay = new FakeMarketRelayCore();
    }


    function send_tell($name, $msg, $low = 0, $checksize = true, $blob = false, $color = false)
    {
        $this->sentTells[] = array('name' => $name, 'msg' => $msg);
    }


    function send_gc($msg, $low = 0, $checksize = true)
    {
        $this->sentGc[] = array('msg' => $msg);
    }


    function send_pgroup($msg, $group = null, $checksize = true, $parsecolors = true)
    {
        $this->sentPgroup[] = array('msg' => $msg, 'group' => $group);
    }


    function exists_module($name)
    {
        return $this->relayModuleExists;
    }


    function core($name)
    {
        switch (strtolower($name)) {
            case 'relay':
                return $this->relay;
            default:
                return new FakeNoOpCore();
        }
    }


    function log($first, $second, $msg, $write_to_db = false)
    {
    }
}


/*
Minimal concrete BaseActiveModule so tell()/gc()/pgmsg() (and therefore
output_destination()) can be exercised without any real command-handling
logic - command_handler() just echoes back whatever reply text the test set.
*/
class FakeOutputDestinationModule extends BaseActiveModule
{
    public $replyText = "reply text";


    function __construct(&$bot)
    {
        parent::__construct($bot, "TestModule");
    }


    protected function command_handler($name, $msg, $origin)
    {
        return $this->replyText;
    }


    // Exposes the protected output_destination() directly so tests can drive
    // arbitrary channel bitmasks (e.g. TELL|PG at once) that reply()'s
    // SAME-only entry points can't produce on their own.
    public function callOutputDestination($name, $msg, $channel)
    {
        $this->output_destination($name, $msg, $channel);
    }
}
