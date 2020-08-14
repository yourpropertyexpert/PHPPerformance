<?php

namespace MHL;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class Demo
{
    private $memcached;
    private const REDISPORT = 6379;
    private const REDISSERVER = "redis";
    private const MEMCACHEDPORT = 6379;
    private const MEMCACHEDSERVER = "memcached";
    private const DBSERVER = "db";
    private const MAX_SQL_LENGTH = 1000000;
    private $db;
    private $sqlTable;
    private $sqlitedb;
    private $sqlite;

    public function __construct($count)
    {
        $this->memcached = new \Memcached();
        $this->memcached->addServer(self::MEMCACHEDSERVER, self::MEMCACHEDPORT);
        $this->redis = new \Redis();
        $this->redis->connect(self::REDISSERVER, self::REDISPORT);
        $this->db = new \mysqli(self::DBSERVER, "root", "mypwd", "test_db");

        $this->api = new Client(); // Guzzle client

        // Create a SQL table with a (hopefully!) unique name
        $this->sqlTable = uniqid('TB');
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS $this->sqlTable (
                ID INT UNSIGNED NOT NULL PRIMARY KEY,
                Val INT UNSIGNED NOT NULL
                ) ENGINE=InnoDB"
        );

        $this->sqlitedb = tempnam(sys_get_temp_dir(), 'DB');
        $this->sqlite = new \SQLite3($this->sqlitedb);
        $this->sqlite->exec(
            "CREATE TABLE IF NOT EXISTS $this->sqlTable (
                ID INTEGER PRIMARY KEY,
                Val INTEGER
                )"
        );

        // For speed, we build big  multi-value INSERTs, though we need to
        // be careful the statements don't get too big - we assume a
        // conservative maximum of 1MB
        $sql = '';
        $i = 0;
        while ($i < $count) {
            $val = rand();
            $this->memcached->set($i, $val);
            $this->redis->set($i, $val);
            if ($sql) {
                $sql .= ",($i,$val)";
            } else {
                $sql = "INSERT INTO $this->sqlTable (ID,Val) VALUES ($i,$val)";
            }
            if (strlen($sql) >= self::MAX_SQL_LENGTH) {
                $this->db->query($sql);
                $this->sqlite->exec($sql);
                $sql = '';
            }
            $i++;
        }
        if ($sql) {
            $this->db->query($sql);
            $this->sqlite->exec($sql);
        }
    }

    public function __destruct()
    {
        $this->db->query("DROP TABLE $this->sqlTable");
        $this->sqlite->close();
        unlink($this->sqlitedb);
    }

    public function getN($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + rand();
            $i++;
        }
        return $n;
    }

    public function getNFromMemcached($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $this->memcached->get($i);
            $i++;
        }
        return $n;
    }
    public function getNFromRedis($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $this->redis->get($i);
            $i++;
        }
        return $n;
    }

    public function getNFromAPI($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + (json_decode($this->api->request('GET', 'api/index.php')->getBody(), true));
            $i++;
        }
        return $n;
    }

    public function getNFromDBQuery($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $result = $this->db->query("SELECT Val FROM $this->sqlTable WHERE ID=$i");
            $number = $result->fetch_row()[0];
            $n = $n + $number;
            $i++;
        }
        return $n;
    }

    public function getNFromDBQueryInOneGo($count)
    {
        // Get all the data from the database
        $data = [];
        $result = $this->db->query("SELECT ID, Val FROM $this->sqlTable", MYSQLI_USE_RESULT);
        while ($row = $result->fetch_row()) {
            $data[$row[0]] = $row[1];
        }
        $result->free();

        // Now read and sum the numbers
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n += $data[$i];
            ++$i;
        }
        return $n;
    }

    public function getNFromSQLite($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n += $this->sqlite->querySingle("SELECT Val FROM $this->sqlTable WHERE ID=$i");
            $i++;
        }
        return $n;
    }
}
