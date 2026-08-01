<?php
/*
Minimal stand-in for Settings_Core: create() seeds a default only if the
setting isn't already known (matching the real idempotent-across-reboots
contract modules rely on), get() returns whatever's stored.
*/
class FakeSettingsCore
{
    private $values = array();


    function create($module, $setting, $default, $description = "", $options = "")
    {
        $key = strtolower($module) . "." . strtolower($setting);
        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = $default;
        }
    }


    function get($module, $setting)
    {
        $key = strtolower($module) . "." . strtolower($setting);
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }


    function set($module, $setting, $value)
    {
        $key = strtolower($module) . "." . strtolower($setting);
        $this->values[$key] = $value;
    }
}
