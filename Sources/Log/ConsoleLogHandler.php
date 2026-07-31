<?php
/*
* ConsoleLogHandler.php - Echoes a formatted log line to stdout
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
class ConsoleLogHandler implements LogHandlerInterface
{
    /*
    Echoes $formatted verbatim - it must already be complete for its destination
    (including botname where relevant), since this handler has no format-specific
    knowledge of its own. Used to unconditionally prepend "$botname " here, which
    corrupted JsonLogFormatter's output (a bare word before the opening '{' breaks
    JSON parsers) - PlainTextLogFormatter now includes the botname itself instead.
    */
    function handle(LogRecord $record, $formatted)
    {
        echo $formatted;
    }
}

?>
