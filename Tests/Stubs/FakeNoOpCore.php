<?php
/*
Swallow-anything stand-in for core modules a constructor touches only in
passing (access_control, command_alias, logon_notifies, ...) where no test
in this suite cares what happens - every method call just returns null.
*/
class FakeNoOpCore
{
    function __call($name, $args)
    {
        return null;
    }
}
