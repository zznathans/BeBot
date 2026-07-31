<?php
/*
* Redis.php - Optional Redis-backed cache
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
*
* See Credits file for all acknowledgements.
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
/*
Unlike Mysql.php, this is a pure cache layer with no source-of-truth data, so it is
designed to fail soft everywhere: a missing config, a missing phpredis extension, or an
unreachable server all just leave the client disabled (get()/set()/delete() become
no-ops) rather than raising an error or exiting - callers must always keep their
existing DB fallback and should never assume a cached value will be there.
*/
class Redis_Client
{
    var $bot;
    var $botname;
    var $CONN;
    var $enabled = false;
    public static $instance;


    public static function get_instance($bothandle)
    {
        if (!isset(self::$instance[$bothandle])) {
            $class = __CLASS__;
            self::$instance[$bothandle] = new $class($bothandle);
        }
        return self::$instance[$bothandle];
    }


    private function __construct($bothandle)
    {
        $this->bot = Bot::get_instance($bothandle);
        $this->botname = $this->bot->botname;

        if (!class_exists('Redis')) {
            // phpredis extension not installed - stay disabled.
            return;
        }

        $botname_redis_conf = "Conf/" . $this->botname . ".Redis.conf";
        if (file_exists($botname_redis_conf)) {
            include $botname_redis_conf;
        } elseif (file_exists("Conf/Redis.conf")) {
            include "Conf/Redis.conf";
        } else {
            // No Redis config deployed for this bot - caching is entirely optional,
            // so treat "not configured" the same as "disabled", not an error.
            return;
        }

        if (empty($host)) {
            return;
        }

        $port = isset($port) && $port !== "" ? intval($port) : 6379;
        $timeout = isset($timeout) && $timeout !== "" ? floatval($timeout) : 1.0;

        try {
            $conn = new Redis();
            $ok = @$conn->connect($host, $port, $timeout);
            if (!$ok) {
                $this->bot->log("REDIS", "START", "Could not connect to Redis at $host:$port, caching disabled.", false);
                return;
            }
            if (!empty($password)) {
                $conn->auth($password);
            }
            if (isset($database) && $database !== "") {
                $conn->select(intval($database));
            }
            $this->CONN = $conn;
            $this->enabled = true;
            $this->bot->log("REDIS", "START", "Connected to Redis at $host:$port.", false);
        } catch (\Throwable $e) {
            $this->bot->log("REDIS", "START", "Redis connection failed (" . $e->getMessage() . "), caching disabled.", false);
        }
    }


    // Namespace a cache key to this bot instance, so pfs/tickr (separate
    // #___users/#___whois data per instance) can't collide on a shared Redis.
    function key($key)
    {
        return strtolower($this->botname) . ":" . $key;
    }


    function get($key)
    {
        if (!$this->enabled) {
            return false;
        }
        try {
            return $this->CONN->get($this->key($key));
        } catch (\Throwable $e) {
            return false;
        }
    }


    function set($key, $value, $ttl = 0)
    {
        if (!$this->enabled) {
            return false;
        }
        try {
            if ($ttl > 0) {
                return $this->CONN->setex($this->key($key), $ttl, $value);
            }
            return $this->CONN->set($this->key($key), $value);
        } catch (\Throwable $e) {
            return false;
        }
    }


    function delete($key)
    {
        if (!$this->enabled) {
            return false;
        }
        try {
            return $this->CONN->del($this->key($key));
        } catch (\Throwable $e) {
            return false;
        }
    }
}

?>
