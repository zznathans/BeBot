<?php
/*
* FileLogHandler.php - Appends a formatted log line to a file
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
class FileLogHandler implements LogHandlerInterface
{
    private $path;


    /*
    $path can be a fixed string, or a callable receiving the LogRecord and
    returning the path to write to (used for the daily-rotating-by-filename log).
    */
    function __construct($path)
    {
        $this->path = $path;
    }


    function handle(LogRecord $record, $formatted)
    {
        $path = is_callable($this->path) ? call_user_func($this->path, $record) : $this->path;
        $fp = fopen($path, "a");
        fputs($fp, $formatted);
        fclose($fp);
    }
}

?>
