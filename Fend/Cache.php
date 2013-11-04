<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 缓存对象 主要用于生产配置文件,缓存数据
 * 如将页面部分信息,数据库查询结果集缓存到文件
 *
 * 缓存类型:
 * 0 文件缓存
 * 1 memcache缓存
 * 2 redis缓存
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Cache.php 4 2011-12-29 11:01:08Z gimoo $
**/

!defined('FD_CACHE_TYPE') && define('FD_CACHE_TYPE',0);//默认选用文件缓存
class Fend_Cache
{
    private static $in=null;
    //预留方法 扩展使用
    public static function factory($t)
    {
        if($t==1){
            self::$in=Fend_Cache_Memcache::Factory();
        }elseif($t==2){
            self::$in=Fend_Cache_Redis::Factory();
        }else{
            self::$in=Fend_Cache_Fcache::Factory();
        }
    }

    /**
     * ----被废弃
     * 文件与内存缓存间切换
     * 当$t为空时,切换为另一缓存模式
     * 当$t非空时,切换指定的缓存
     * 0是文件缓存 1是内存缓存
     *
     * @param  string $t 参数:0文件|1内存|null反向|-1默认
     * @return void
    **/
    public static function Change($t=null)
    {
        if(null==$t){//反切换
            $t= FD_CACHE_TYPE ^ 1;
        }elseif($t==-1){//切换到默认
            $t= FD_CACHE_TYPE;
        }
        Fend_Cache::factory($t);
    }

    /**
     * 设置数据缓存
     * 与add|replace比较类似
     * 唯一的区别是: 无论key是否存在,是否过期都重新写入数据
     *
     * @param  string $key    数据的标识
     * @param  string $value  实体内容
     * @param  string $expire 过期时间[天d|周w|小时h|分钟i] 如:8d=8天 默认为0永不过期
     * @param  bool   $iszip  是否启用压缩
     * @return bool
    **/
    public static function set($key,$value,$expire=0,$iszip=false)
    {
        return self::$in->set($key,$value,$expire,$iszip);
    }

    /**
     * 获取数据缓存
     *
     * @param  string  $key 缓存文件名称包括后缀
     * @param  integer $t   缓存时间设置(单位秒),取得多长时间内写入的缓存
     * @return string|array
    **/
    public static function get($key,$t=0)
    {
        return self::$in->get($key,$t);
    }

    /**
     * 新增数据缓存
     * 只有当key不存,存在但已过期时被设值
     *
     * @param  string $key    数据的标识
     * @param  string $value  实体内容
     * @param  string $expire 过期时间[天d|周w|小时h|分钟i] 如:8d=8天 默认为0永不过期
     * @param  bool   $iszip  是否启用压缩
     * @return bool   操作成功时返回ture,如果存在返回false否则返回true
    **/
    public static function add($key,$value,$expire=0,$iszip=false)
    {
        return self::$in->add($key,$value,$expire,$iszip);
    }

    /**
     * 替换数据缓存
     * 与 add|set 参数相同,与set比较类似
     * 唯一的区别是: 只有当key存在且未过期时才能被替换数据
     *
     * @param  string $key    数据的标识
     * @param  string $value  实体内容
     * @param  string $expire 过期时间[天d|周w|小时h|分钟i] 如:8d=8天 默认为0永不过期
     * @param  bool   $iszip  是否启用压缩
     * @return bool
    **/
    public static function replace($key,$value,$expire=0,$iszip=false)
    {
        return self::$in->replace($key,$value,$expire,$iszip);
    }

    /**
     * 检测缓存是否存在
     *
     * @param  string $key 数据的标识
     * @return bool
    **/
    public static function isKey($key)
    {
        return self::$in->isKey($key);
    }

    /**
     * 删除数据缓存
     *
     * @param  string $key    数据的标识
     * @param  string $expire 删除的等待时间,好像有问题尽量不要使用
     * @return bool
    **/
    public static function del($key,$expire=0)
    {
        return self::$in->del($key,$expire);
    }

    /**
     * 直接访问内部对象
     *
     * @param  int $tp 对象类型,1当前对象,0接口对象
     * @return bool
    **/
    public static function obj($tp=0)
    {
        return $tp ? self::$in : self::$in->mc;
    }
}

Fend_Cache::factory(FD_CACHE_TYPE);
?>