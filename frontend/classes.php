<?php

namespace MHL;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class Demo
{
    // Information about our connections (this is preserved in sleep)
    private $environment;
    private $sqlTable;
    private $sqlitedb;
    // The seed for our random numbers (also preserved)
    private $seed;
    // Our connections themselves (not preserved)
    private $memcached;
    private $localmemcached;
    private $redis;
    private $db;
    private $sqlite;
    private $cURL;
    private $api;
    private const MAX_SQL_LENGTH = 1000000;
    private const MICROSECONDS = 1000000;

    public function __construct($count)
    {
        // We have a bunch of information from environment variables - they
        // must all be set. We pull them into a private local array.
        $varnames = ['APIURI', 'MYSQLSERVER', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLDATABASE',
                     'REDISSERVER', 'REDISPORT', 'MEMCACHEDSERVER', 'MEMCACHEDPORT'];
        foreach ($varnames as $var) {
            if (getenv($var) === false) {
                throw new \Exception("Missing environment variable '$var'");
            }
            $this->environment[$var] = getenv($var);
        }

        $this->seed = (int)(MICROSECONDS * microtime(true));

        $this->sqlTable = uniqid('TB');
        $this->sqlitedb = tempnam(sys_get_temp_dir(), 'DB');

        // Now we have all the information, we can make our external connections.
        $this->connectDBs();

        // Create a SQL table with a (hopefully!) unique name
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS $this->sqlTable (
                ID INT UNSIGNED NOT NULL PRIMARY KEY,
                Val INT UNSIGNED NOT NULL
                ) ENGINE=InnoDB"
        );

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
        srand($this->seed);
        while ($i < $count) {
            $val = rand();
            $this->memcached->set($i, $val);
            $this->localmemcached->set($i, $val);
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

    public function __sleep()
    {
        // When Bagpuss goes to sleep, all his friends go to sleep. But some of them
        // aren't as well-behaved as Professor Yaffle, and they don't serialise properly.
        // So we don't try to preserve external connections over a sleep-wake cycle, we
        // just retain the information we need to recreate them when we wake up again.
        return [
            'sqlTable',
            'environment',
            'sqlitedb',
            'seed',
            ];
    }

    public function __wakeup()
    {
        // Bagpuss has woken up, so his friends need to wake up. But since we don't
        // preserve the external connection objects, we need to recreate them.
        $this->connectDBs();
    }

    public function seed()
    {
        // Return our random seed
        return $this->seed;
    }

    public function cleanup()
    {
        $this->db->query("DROP TABLE $this->sqlTable");
        $this->sqlite->close();
        unlink($this->sqlitedb);
    }

    public function getNull($count)
    {
        return 0;
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
            $n = $n + $this->memcached->get($i);
            $i++;
        }
        return $n;
    }

    public function getNFromLocalMemcached($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + $this->localmemcached->get($i);
            $i++;
        }
        return $n;
    }

    public function getNFromRedis($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + $this->redis->get($i);
            $i++;
        }
        return $n;
    }

    public function getNFromAPIcURL($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + json_decode(curl_exec($this->cURL));
            $i++;
        }
        return $n;
    }

    public function getNFromAPIGuzzle($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + (json_decode($this->api->request('GET', $this->environment['APIURI'])->getBody(), true));
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
            $n = $n + $result->fetch_row()[0];
            $i++;
        }
        return $n;
    }

    public function getNFromDBQueryPrepared($count)
    {
        $i = 0;
        $n = 0;
        $sql = "SELECT Val from $this->sqlTable WHERE ID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $i);
        while ($i < $count) {
            $stmt->execute();
            $result = $stmt->get_result();
            $n = $n + $result->fetch_assoc()["Val"];
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
            $n = $n + $data[$i];
            ++$i;
        }
        return $n;
    }

    public function getNFromSQLite($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n + $this->sqlite->querySingle("SELECT Val FROM $this->sqlTable WHERE ID=$i");
            $i++;
        }
        return $n;
    }

    public function getNFromSQLiteInOneGo($count)
    {
        $i = 0;
        $n = 0;
        $resultarray = $this->sqlite->query("SELECT Val FROM $this->sqlTable LIMIT $count");
        while ($thisone = $resultarray->fetchArray()) {
            $n = $n + $thisone[0];
        }
        return $n;
    }

    private function connectDBs()
    {
        // Make all the external connections (not just databases) - this is called to construct
        // the object in the first place, and also to recreate the connections when we're woken up.
        $this->memcached = new \Memcached();
        $this->memcached->addServer($this->environment['MEMCACHEDSERVER'], $this->environment['MEMCACHEDPORT']);
        if (!$this->memcached->set('Test', time())) {
            throw new \Exception('REMOMTE ' . $this->memcached->getResultMessage());
        }

        $this->localmemcached = new \Memcached();
        $this->localmemcached->addServer('/tmp/memcached.sock', '0');
        if (!$this->localmemcached->set('Test', time())) {
            throw new \Exception('LOCAL ' . $this->localmemcached->getResultMessage());
        }

        $this->redis = new \Redis();
        $this->redis->connect($this->environment['REDISSERVER'], $this->environment['REDISPORT']);

        $this->db = new \mysqli(
            $this->environment['MYSQLSERVER'],
            $this->environment['MYSQLUSER'],
            $this->environment['MYSQLPASSWORD'],
            $this->environment['MYSQLDATABASE']
        );

        $this->sqlite = new \SQLite3($this->sqlitedb);

        $this->api = new Client(); // Guzzle client

        $this->cURL = curl_init($this->environment['APIURI']);
        $version = curl_version();
        curl_setopt_array($this->cURL, [CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_HEADER, false,
                                        CURLOPT_USERAGENT => "cURL/$version[version] PHP/" . PHP_VERSION,
                                       ]);
    }
}
