<?php
/*
* LogRecord.php - Plain value object describing a single log event
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
class LogRecord
{
    public $timestamp;
    public $botname;
    public $first;
    public $second;
    public $message;
    public $writeToDb;


    function __construct($timestamp, $botname, $first, $second, $message, $writeToDb)
    {
        $this->timestamp = $timestamp;
        $this->botname = $botname;
        $this->first = $first;
        $this->second = $second;
        $this->message = $message;
        $this->writeToDb = $writeToDb;
    }
}

?>
