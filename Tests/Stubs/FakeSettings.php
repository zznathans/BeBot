<?php
/*
Minimal stand-in for Settings_Core, just enough to drive
LogDispatcher's format resolution: get() returns a stored value, or a
BotError if it was never set (mirroring Settings_Core::get()'s real
behavior for an unknown setting).
*/
class FakeSettings
{
    private $bot;
    private $values = array();


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
        $module = strtolower($module);
        $setting = strtolower($setting);
        if (isset($this->values[$module][$setting])) {
            return $this->values[$module][$setting];
        }
        return new BotError($this->bot, $module);
    }
}
