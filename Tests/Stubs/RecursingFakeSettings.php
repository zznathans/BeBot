<?php
/*
Reproduces the one behavior FakeSettings deliberately simplifies away: the real
Settings_Core::get() logs a Bot->log("ERROR", ...) side effect (via BotError::set())
on a miss, before returning - it doesn't fail silently. That side effect is what makes
LogDispatcher::resolveFormatter() calling get() on a never-created Log.* setting
recurse without end (get() -> log() -> dispatch() -> resolveFormatter() -> get() -> ...).
getCallCount() lets a test assert get() was never reached at all, rather than letting
that recursion actually happen.
*/
class RecursingFakeSettings
{
    private $bot;
    private $values = array();
    private $getCallCount = 0;


    function __construct($bot)
    {
        $this->bot = $bot;
    }


    function set($module, $setting, $value)
    {
        $this->values[strtolower($module)][strtolower($setting)] = $value;
    }


    function get($module, $setting)
    {
        $this->getCallCount++;
        $module = strtolower($module);
        $setting = strtolower($setting);
        if (isset($this->values[$module][$setting])) {
            return $this->values[$module][$setting];
        }
        $error = new BotError($this->bot, $module);
        $error->set("The setting named " . $setting . " for setting group " . $module . " does not exist.");
        return $error;
    }


    function exists($module, $setting)
    {
        return isset($this->values[strtolower($module)][strtolower($setting)]);
    }


    function getCallCount()
    {
        return $this->getCallCount;
    }
}
