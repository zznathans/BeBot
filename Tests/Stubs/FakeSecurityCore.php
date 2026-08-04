<?php
/*
Minimal stand-in for the "security" core module - just enough of get_gid()/
get_group_members() for Relay's auto-buddy sync to look up a security group's
members without a real database.
*/
class FakeSecurityCore
{
    private $gids = array();
    private $members = array();


    function seedGroup($groupname, $gid, array $memberNames)
    {
        $this->gids[$groupname] = $gid;
        $this->members[$groupname] = $memberNames;
        $this->members[$gid] = $memberNames;
    }


    function get_gid($groupname)
    {
        return isset($this->gids[$groupname]) ? $this->gids[$groupname] : -1;
    }


    function get_group_members($group)
    {
        return isset($this->members[$group]) ? $this->members[$group] : array();
    }
}
