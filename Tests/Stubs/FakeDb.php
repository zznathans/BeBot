<?php
/*
Minimal stand-in for the Mysql class, just enough to observe what
DbLogHandler/LogDispatcher would have sent to a real database.
*/
class FakeDb
{
    public $queries = array();


    function query($sql)
    {
        $this->queries[] = $sql;
        return true;
    }


    function real_escape_string($string)
    {
        return addslashes($string);
    }
}
