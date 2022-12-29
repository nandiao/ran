<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author: karl<nandiao@qq.com>
 * Date:   2021/6/23 16:30
 * Desc:   缓存类 PSR-16实现
 */
namespace ran;

use Psr\SimpleCache\CacheInterface;
use ran\cache\CacheNS;
use think\facade\Db;

class Cache
{
    private static $isInit;   //初始化标志
    private static $conf;     //缓存配置
    private static $cache;    //缓存实例

    public function __construct()
    {
        return new CacheNS();
    }
    /*
     * 初始化
     */
    static private function init()
    {
        if (!self::$isInit) { //没有初始化进行初始化
            //加载配置
            $config = require(__dir__."/../config.php");
            self::$conf = $config['base']['cache'];
            //实例化
            $cacheClass = "ran\cache\Cache".ucfirst(self::$conf['type']);
            self::$cache = new $cacheClass;
            self::$isInit = true; //初始化完成标示
        }
    }
    /**
     * 从缓存中取出值
     *
     * @param string $key     该项在缓存中唯一的key值
     * @param mixed  $default key不存在时，返回的默认值
     *
     * @return mixed 从缓存中返回的值，或者是不存在时的默认值
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   如果给定的key不是一个合法的字符串时，抛出该异常
     */
    static public function get($key, $default = null)
    {
        self::init();
        return self::$cache->get($key, $default);
    }
    /**
     * 存储值在cache中，唯一关键到一个key及一个可选的存在时间
     *
     * @param string                 $key   存储项目的key.
     * @param mixed                  $value 存储的值，必须可以被序列化的
     * @param null|int|\DateInterval $ttl   可选项.项目的存在时间，如果该值没有设置，且驱动支持生存时间时，将设置一个默认值，或者驱自行处理。
     *
     * @return bool true 存储成功  false 存储失败
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *  如果给定的key不是一个合法的字符串时，抛出该异常。
     */
    static public function set($key, $value, $ttl = null)
    {
        self::init();
        if (!$ttl) $ttl = self::$conf['ttl']; //默认ttl
        return self::$cache->set($key, $value, $ttl);
    }
    /**
     * 删除指定键值的缓存项
     *
     * @param string $key 指定的唯一缓存key对应的项目将会被删除
     *
     * @return bool 成功删除时返回ture，有其它错误时时返回false
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   如果给定的key不是一个合法的字符串时，抛出该异常。
     */
    static public function delete($key)
    {
        self::init();
        return self::$cache->delete($key);
    }
    /**
     * 清除所有缓存中的key
     *
     * @return bool 成功返回True.失败返回False
     */
    static public function clear()
    {
        self::init();
        return self::$cache->clear();
    }
    /**
     * 根据指定的缓存键值列表获取得多个缓存项目
     *
     * @param iterable $keys   在单次操作中可被获取的键值项
     * @param mixed    $default 如果key不存在时，返回的默认值
     *
     * @return iterable  返回键值对（key=>value形式）列表。如果key不存在，或者已经过期时，返回默认值。
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *  如果给定的keys既不是合法的数组，也不可以被转成数组，或者给得的任何一个key不是一个合法的值时，拖出该异常。
     */
    static public function getMultiple($keys, $default = null)
    {
        self::init();
        return self::$cache->getMultiple($keys, $default);
    }
    /**
     * 存储一个键值对形式的集合到缓存中。
     *
     * @param iterable               $values 一系列操作的键值对列表
     * @param null|int|\DateInterval $ttl     可选项.项目的存在时间，如果该值没有设置，且驱动支持生存时间时，将设置一个默认值，或者驱自行处理。
     *
     * @return bool 成功返回True.失败返回False.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   如果给定的keys既不是合法的数组，也不可以被转成数组，或者给得的任何一个key不是一个合法的值时，拖出该异常.
     */
    static public function setMultiple($values, $ttl = null)
    {
        self::init();
        if (!$ttl) $ttl = self::$conf['ttl']; //默认ttl
        return self::$cache->setMultiple($values, $ttl = null);
    }
    /**
     *  单次操作删除多个缓存项目.
     *
     * @param iterable $keys 一个基于字符串键列表会被删除
     *
     * @return bool True 所有项目都成功被删除时回true,有任何错误时返回false
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   如果给定的keys既不是合法的数组，也不可以被转成数组，或者给得的任何一个key不是一个合法的值时，拖出该异常.
     */
    static public function deleteMultiple($keys)
    {
        self::init();
        return self::$cache->deleteMultiple($keys);
    }
    /**
     * 判断一个项目在缓存中是否存在
     *
     * 注意: has()方法仅仅在缓存预热的场景被推荐使用且不允许的活跃
     * 的应用中场景中对get/set方法使用, 因为方法受竞态条件的限制，当
     * 你调用has()方法时会立即返回true。另一个脚本可以删除它，使应
     * 用状态过期。
     * @param string $key 缓存键值
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *    如果给定的key不是一个合法的字符串时，抛出该异常.
     */
    static public function has($keys)
    {
        self::init();
        return self::$cache->has($keys);
    }
}