<?php
/*
Minimal stand-in for the "chat" core module, recording pgroup_invite() calls so a test
can assert on exactly who got invited.
*/
class FakeChatCore
{
    public $invitedNames = array();


    function pgroup_invite($name)
    {
        $this->invitedNames[] = $name;
    }


    function __call($name, $args)
    {
        return null;
    }
}
