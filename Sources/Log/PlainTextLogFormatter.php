<?php
/*
* PlainTextLogFormatter.php - Reproduces BeBot's historical log line format
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
class PlainTextLogFormatter implements LogFormatterInterface
{
    private $timestampMode;


    /*
    $timestampMode: one of "date"/"time"/"none", anything else defaults to full datetime.
    */
    function __construct($timestampMode)
    {
        $this->timestampMode = $timestampMode;
    }


    function format(LogRecord $record)
    {
        if ($this->timestampMode == 'date') {
            $timestamp = "[" . gmdate("Y-m-d", $record->timestamp) . "]\t";
        } elseif ($this->timestampMode == 'time') {
            $timestamp = "[" . gmdate("H:i:s", $record->timestamp) . "]\t";
        } elseif ($this->timestampMode == 'none') {
            $timestamp = "";
        } else {
            $timestamp = "[" . gmdate("Y-m-d H:i:s", $record->timestamp) . "]\t";
        }
        return $record->botname . " " . $timestamp . "[" . $record->first . "]\t[" . $record->second . "]\t" . $record->message . "\n";
    }
}

?>
