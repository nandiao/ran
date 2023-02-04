<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author: karl<nandiao@qq.com>
 * Date:   2021/6/23 16:30
 * Desc:   缓存类待实例化对象
 */
namespace ran\cache;

use Psr\SimpleCache\CacheInterface;
use think\facade\Db;

class CacheNS implements CacheInterface
{
    private $cache;

    public function __construct()
    {
        //加载配置
        $config = require(__dir__."/../../config.php");
        $cacheType = $config['base']['cache']['type'];
        //实例化
        $cacheClass = "ran\cache\Cache".ucfirst($cacheType);
        $this->cache = new $cacheClass;
    }

    public function get($key, $default = null)
    {
        return $this->cache->get($key, $default = null);
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->cache->set($key, $value, $ttl = null);
    }

    public function delete($key)
    {
        return $this->cache->delete($key);
    }

    public function clear()
    {
        return $this->cache->clear();
    }

    public function getMultiple($keys, $default = null)
    {
        return $this->cache->getMultiple($keys, $default = null);
    }

    public function setMultiple($values, $ttl = null)
    {
        return $this->cache->setMultiple($values, $ttl = null);
    }

    public function deleteMultiple($keys)
    {
        return $this->cache->deleteMultiple($keys);
    }

    public function has($keys)
    {
        return $this->cache->has($keys);
    }
}