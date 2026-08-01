<?php
/*
Minimal stand-in for the Mysql class, just enough to observe what
DbLogHandler/LogDispatcher would have sent to a real database.
*/
class FakeDb
{
    public $queries = array();
    private $versions = array();


    function query($sql)
    {
        $this->queries[] = $sql;
        return true;
    }


    function select($sql)
    {
        return array();
    }


    function real_escape_string($string)
    {
        return addslashes($string);
    }


    // Minimal stand-ins for the schema-migration helpers table()-style methods lean on
    // (Mysql::define_tablename/get_version/set_version/update_table) - just enough to let a
    // module's table() run against an in-memory version map instead of a real DB connection.
    function define_tablename($table, $useprefix = "true")
    {
        return "#___" . $table;
    }


    function get_version($table)
    {
        return isset($this->versions[$table]) ? $this->versions[$table] : 0;
    }


    function set_version($table, $version)
    {
        $this->versions[$table] = $version;
    }


    function update_table($table, $column, $action, $sql)
    {
        $this->queries[] = $sql;
        return true;
    }
}
