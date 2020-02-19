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

    public function __construct($count)
    {
        $this->memcached = new \Memcached();
        $this->memcached->addServer(self::MEMCACHEDSERVER, self::MEMCACHEDPORT);
        $this->redis = new \Redis();
        $this->redis->connect(self::REDISSERVER, self::REDISPORT);

        $this->api = new Client();
        $i = 0;
        while ($i<$count) {
            $val = 1;
            $this->memcached->set($i, $val);
            $this->redis->set($i, $val);
            $i++;
        }
    }

    public function getN($count)
    {
        $i = 0;
        $n = 0;
        while ($i < $count) {
            $n = $n+rand();
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
    }
}
