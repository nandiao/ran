<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author: karl<nandiao@qq.com>
 * Date:   2021/6/23 16:30
 * Desc:   缓存（文件方式）
 */
namespace ran\cache;

use Psr\SimpleCache\CacheInterface;
use think\db\exception\InvalidArgumentException;
use think\facade\Db;

class CacheFile implements CacheInterface
{
    public $defaultTtl = 3600;     //默认ttl
    private $folder = __DIR__. "/../../runtime/cache/";
    //private $keyArray = [];         //键名和过期时间数组

    public function __construct()
    {
        if (!is_dir($this->folder))
            mkdir($this->folder, 0777, true);
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
    public function get($key, $default = null)
    {
        if (preg_match("/[\{,\},\(,\),\/,\\,\@,\:]/", $key))    //键名非法报错
            throw new InvalidArgumentException('缓存key非法');
        if ($this->has($key)) {
            $keyMd5 = md5($key);
            $keyArray = $this->getKeyArray();
            if ($c = file_get_contents($this->folder.$keyMd5 .'_' .$keyArray[$keyMd5]))
                return unserialize($c)['value'];
        }
        return $default;
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
    public function set($key, $value, $ttl = null)
    {
        if (preg_match("/[\{,\},\(,\),\/,\\,\@,\:]/", $key))    //键名非法报错
            throw new InvalidArgumentException('缓存key非法');
        $ttl = $ttl ? $ttl : $this->defaultTtl;
        $deadLine = time() + $ttl; //过期时间
        $keyMd5 = md5($key);
        $fileName = $keyMd5 .'_' .$deadLine;
        $keyArray = $this->getKeyArray();
        if (isset($keyArray[$keyMd5])) //删除之前缓存
            $r = unlink($this->folder.$keyMd5. "_". $keyArray[$keyMd5]);
        if (!isset($r) || (isset($r) && $r))
            if (file_put_contents($this->folder.$fileName, serialize(['key' => $key, 'value' => $value])))  //更新缓存
                return true;
        return false;
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
    public function delete($key)
    {
        if (preg_match("/[\{,\},\(,\),\/,\\,\@,\:]/", $key))    //键名非法报错
            throw new InvalidArgumentException('缓存key非法');
        $keyMd5 = md5($key);
        $keyArray = $this->getKeyArray();
        if (isset($keyArray[$keyMd5]) && $r = unlink($this->folder.$keyMd5. "_". $keyArray[$keyMd5]))
            return $r;
        return false;
    }
    /**
     * 清除所有缓存中的key
     *
     * @return bool 成功返回True.失败返回False
     */
    public function clear()
    {
        $result = true;
        $keyArray = $this->getKeyArray();
        foreach ($keyArray as $k => $v) {
            if (!unlink($this->folder.$k. "_". $keyArray[$k]))
                $result = false;
        }
        return $result;
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
    public function getMultiple($keys, $default = null)
    {
        $return = [];
        foreach ($keys as $key)
            $return[$key] = $this->get($key, $default);
        return $return;
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
    public function setMultiple($values, $ttl = null)
    {
        $return = true;
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl);
            if ($return && !$result) $return = false;
        }
        return $return;
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
    public function deleteMultiple($keys)
    {
        $return = true;
        foreach ($keys as $key) {
            $result = $this->delete($key);
            if ($return && !$result) $return = false;
        }
        return $return;
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
    public function has($key)
    {
        $keyArray = $this->getKeyArray();
        if (preg_match("/[\{,\},\(,\),\/,\\,\@,\:]/", $key))    //键名非法报错
            throw new InvalidArgumentException('缓存key非法');
        if (isset($keyArray[md5($key)]) && $keyArray[md5($key)] > time())
            return true;
        elseif (isset($keyArray[md5($key)]) && $keyArray[md5($key)] > time())  //有缓存过期,删除
            $this->delete($key);
        return false;
    }
    /*************************************************
     * 非接口方法
     *************************************************/
    /*
     * getKeyArray
     * 得到键的相关信息数组
     * @return array
     */
    private function getKeyArray() : array
    {
        $result = [];
        $fileNameList = scandir($this->folder);
        foreach ($fileNameList as $k => $f) { //缓存文件遍历
            if (!in_array(",", ['.', '..'])) {
                $t = explode("_", $f);
                if (isset($t[1]))  $result[$t[0]] = $t[1];
            }
        }
        return $result;
    }
}