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
    private $bot;


    /*
    Takes $bot rather than $bot->db, and reads ->db fresh on every handle() call
    rather than capturing it once here. LogDispatcher (and this handler with it) is
    constructed lazily on the first ever Bot->log() call, which happens well before
    the database connects (MySQL::get_instance() runs later in boot) - capturing
    $bot->db at construction time would permanently capture null, and since
    LogDispatcher is a singleton, every later write_to_db log call for the rest of
    the bot's lifetime would fatal on a null ->query() call.
    */
    function __construct($bot)
    {
        $this->bot = $bot;
    }


    /*
    Ignores $formatted - the DB row already has separate first/second/timestamp
    columns, so it stores the raw (scrubbed) message rather than a rendered line.
    */
    function handle(LogRecord $record, $formatted)
    {
        $db = $this->bot->db;
        $logmsg = substr($record->message, 0, 500);
        $db->query(
            "INSERT INTO #___log_message (message, first, second, timestamp) VALUES ('" . $db->real_escape_string(
                $logmsg
            ) . "','" . $db->real_escape_string($record->first) . "','" . $db->real_escape_string(
                $record->second
            ) . "','" . $record->timestamp
            . "')"
        );
    }
}

?>
