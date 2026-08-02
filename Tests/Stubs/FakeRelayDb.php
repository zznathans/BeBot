<?php
/*
Minimal stand-in for the Mysql class for Modules/Relay.php's connect() regression test:
records every query executed (so a test can assert on the exact SQL text - e.g. that a
table reference actually has its #___ prefix) and lets a test seed canned select()
results per query, matched by a substring of the SQL.
*/
class FakeRelayDb
{
    public $queries = array();
    private $seededResults = array();


    // Test setup: the next select() whose SQL contains $sqlContains returns $rows.
    function seedSelect($sqlContains, array $rows)
    {
        $this->seededResults[] = array($sqlContains, $rows);
    }


    function query($sql)
    {
        $this->queries[] = $sql;
        return true;
    }


    function select($sql)
    {
        $this->queries[] = $sql;
        foreach ($this->seededResults as $seeded) {
            if (strpos($sql, $seeded[0]) !== false) {
                return $seeded[1];
            }
        }
        return array();
    }


    function real_escape_string($string)
    {
        return addslashes($string);
    }


    function define_tablename($table, $useprefix = "true")
    {
        return "#___" . $table;
    }


    function get_version($table)
    {
        return 0;
    }


    function set_version($table, $version)
    {
    }


    function update_table($table, $column, $action, $sql)
    {
        $this->queries[] = $sql;
        return true;
    }
}
