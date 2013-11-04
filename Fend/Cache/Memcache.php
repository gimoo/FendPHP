<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 缓存对象 主要用于生产配置文件,缓存数据
 * 如将页面部分信息,数据库查询结果集缓存到文件
 * delect bug=0
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Memcache.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Cache_Memcache extends Fend
{
    public $mc;//连接成功时的标识
    private static $in=null;

    /**
     * 预留方法 扩展使用
     *
    **/
    public static function Factory()
    {
        if(null===self::$in) self::$in = new self;
        return self::$in;
    }

    /**
     * 初始化MC对象
     *
     * @return void
    **/
    public function __construct()
    {
        $this->mc=new Memcache;
        @$this->mc->connect(
            isset($this->mccfg['host']) ? $this->mccfg['host'] : '127.0.0.1',
            isset($this->mccfg['port']) ? $this->mccfg['port'] : '11211'
        ) or self::showMsg('[MemCache:]Could not connect');

        !isset($this->mccfg['pre']) && $this->mccfg['pre']='';
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
    public function set($key,$value,$expire=0,$iszip=false)
    {
        $expire>0 && $expire=self::setLifeTime($expire);
        return $this->mc->set($this->mccfg['pre'].$key,$value,$iszip,$expire);
    }

    /**
     * 获取数据缓存
     *
     * @param  string $key    数据的标识
     * @return string
    **/
    public function get($key)
    {
        return $this->mc->get($this->mccfg['pre'].$key);
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
    public function add($key,$value,$expire=0,$iszip=false)
    {
        $expire>0 && $expire=self::setLifeTime($expire);
        return $this->mc->add($this->mccfg['pre'].$key,$value,$iszip,$expire);
    }

    /**
     * 替换数据
     * 与 add|set 参数相同,与set比较类似
     * 唯一的区别是: 只有当key存在且未过期时才能被替换数据
     *
     * @param  string $key    数据的标识
     * @param  string $value  实体内容
     * @param  string $expire 过期时间[天d|周w|小时h|分钟i] 如:8d=8天 默认为0永不过期
     * @param  bool   $iszip  是否启用压缩
     * @return bool
    **/
    public function replace($key,$value,$expire=0,$iszip=false)
    {
        $expire>0 && $expire=self::setLifeTime($expire);
        return $this->mc->replace($this->mccfg['pre'].$key,$value,$iszip,$expire);
    }

    /**
     * 检测缓存是否存在
     *
     * @param  string $key 数据的标识
     * @return bool
    **/
    public function isKey($key)
    {
        if($this->mc->add($this->mccfg['pre'].$key,1)){//不存在
            $this->mc->delete($key,0);
            return false;
        }else{//存在
            return true;
        }
    }

    /**
     * 删除一个数据缓存
     *
     * @param  string $key    数据的标识
     * @param  string $expire 删除的等待时间,好像有问题尽量不要使用
     * @return bool
    **/
    public function del($key,$expire=0)
    {
        return $this->mc->delete($this->mccfg['pre'].$key,$expire);
    }


    /**
     * 格式化过期时间
     * 注意: 限制时间小于2592000=30天内
     *
     * @param  string $t 要处理的串
     * @return int
    **/
    private function setLifeTime($t)
    {
        if(!is_numeric($t)){
            switch(substr($t,-1)){
                case 'w'://周
                    $t=(int)$t*7*24*3600;
                    break;
                case 'd'://天
                    $t=(int)$t*24*3600;
                    break;
                case 'h'://小时
                    $t=(int)$t*3600;
                    break;
                case 'i'://分钟
                    $t=(int)$t*60;
                    break;
                default:
                    $t=(int)$t;
                    break;
            }
        }
        if($t>2592000) self::showMsg('Memcached Backend has a Limit of 30 days (2592000 seconds) for the LifeTime');
        return $t;
    }


    /**
     * 设置异常消息 可以通过try块中捕捉该消息
     *
     * @param  resource $query 资源标识指针
     * @return boolean
    **/
    private function showMsg($str)
    {
        throw new Fend_Exception($str);
    }
}

?>