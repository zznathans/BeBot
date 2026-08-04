<?php
/*
Minimal stand-in for the "chat" core module, recording pgroup_invite() and
buddy_add() calls so a test can assert on exactly who got invited/buddied.
*/
class FakeChatCore
{
    public $invitedNames = array();
    public $buddies = array();


    function pgroup_invite($name)
    {
        $this->invitedNames[] = $name;
    }


    function buddy_add($uid)
    {
        $this->buddies[$uid] = true;
    }


    function buddy_remove($uid)
    {
        unset($this->buddies[$uid]);
    }


    function buddy_exists($uid)
    {
        return isset($this->buddies[$uid]);
    }


    function __call($name, $args)
    {
        return null;
    }
}
