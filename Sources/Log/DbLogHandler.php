<?php
/*
* DbLogHandler.php - Persists a log record to the log_message table
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License only.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
*  USA
*/
class DbLogHandler implements LogHandlerInterface
{
    private $db;


    function __construct($db)
    {
        $this->db = $db;
    }


    /*
    Ignores $formatted - the DB row already has separate first/second/timestamp
    columns, so it stores the raw (scrubbed) message rather than a rendered line.
    */
    function handle(LogRecord $record, $formatted)
    {
        $logmsg = substr($record->message, 0, 500);
        $this->db->query(
            "INSERT INTO #___log_message (message, first, second, timestamp) VALUES ('" . $this->db->real_escape_string(
                $logmsg
            ) . "','" . $this->db->real_escape_string($record->first) . "','" . $this->db->real_escape_string(
                $record->second
            ) . "','" . $record->timestamp
            . "')"
        );
    }
}

?>
