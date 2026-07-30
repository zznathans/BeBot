<?php
/*
* LogDispatcher.php - Resolves formatters/handlers and fans a LogRecord out
* to the console, security file, daily file, and database as appropriate.
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
class LogDispatcher
{
    private $bot;
    private $plainFormatter;
    private $consoleHandler;
    private $securityFileHandler;
    private $dailyFileHandler;
    private $dbHandler;


    function __construct($bot)
    {
        $this->bot = $bot;
        $this->plainFormatter = new PlainTextLogFormatter($bot->log_timestamp);
        $this->consoleHandler = new ConsoleLogHandler();
        $this->securityFileHandler = new FileLogHandler($bot->log_path . "/security.txt");
        $this->dailyFileHandler = new FileLogHandler(
            function (LogRecord $record) use ($bot) {
                return $bot->log_path . "/" . gmdate("Y-m-d", $record->timestamp) . ".txt";
            }
        );
        $this->dbHandler = new DbLogHandler($bot->db);
    }


    function dispatch(LogRecord $record)
    {
        $formatted = $this->plainFormatter->format($record);

        $this->consoleHandler->handle($record, $formatted);

        // We have a possible security related event.
        // Log to the security log and notify guildchat/pgroup.
        if (preg_match("/^security$/i", $record->second)) {
            if ($this->bot->guildbot) {
                $this->bot->send_gc($formatted);
            } else {
                $this->bot->send_pgroup($formatted);
            }
            $this->securityFileHandler->handle($record, $formatted);
        }

        if (($this->bot->log == "all")
            || (($this->bot->log == "chat")
                && (($record->first == "GROUP") || ($record->first == "TELL") || ($record->first == "PGRP")))
        ) {
            $this->dailyFileHandler->handle($record, $formatted);
        }

        if ($record->writeToDb) {
            $this->dbHandler->handle($record, $formatted);
        }
    }
}

?>
