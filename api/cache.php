<?php
require_once '../predis/autoload.php';

class Cache{
    public $redis;
    public $cacheHead;
    function __construct()
    {
        try {
            $this->cacheHead = "tm.x87pqc.0001.use1.cache.amazonaws.com";
            $this->redis = new Predis\Client('tcp://'.$this->cacheHead.':6379');
        }
        catch (Exception $e) {
            echo "Couldn't connected to Redis";
            echo $e->getMessage();
        }
    }

    function get($var)
    {
        return $this->redis->get($var);
    }

    function incr($var)
    {
        return $this->redis->incr($var);
    }

    function set($var,$val)
    {
        return $this->redis->set($var,$val);
    }

    function del($var)
    {
        return $this->redis->del($var);
    }

    function expire($var,$secs)
    {
        return $this->redis->expire($var,$secs);
    }

    function keys($filter)
    {
        return $this->redis->keys($filter);
    }

    function info()
    {
        return $this->redis->info();
    }

    function exists($var)
    {
        return $this->redis->exists($var);
    }
}
?>
