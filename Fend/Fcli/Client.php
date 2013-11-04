<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * Soap 客户端扩展对象
 *
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Client.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Fcli_Client
{
    public static $in=null;
    public $_cfg=array(
        'url'=>'',//远程地址
        'host'=>'',//远程地址
        'port'=>80,//远程地址
        'key'=>'',//连接密钥
        'time'=>15,//超时设置
        'debug'=>0,//是否开起调试
    );
    public static function Factory()
    {
        if(null===self::$in) self::$in=new self();
        return self::$in;
    }

    /**
     * 初始化服务
     * 设置相关参数与通信接口
     *
     * @param  mixed 需要设置的参数集合格式:('name:appCMS','user:appCMS','pwd:fendcms')
     * @return void
    **/
    public function Init()
    {
        if(func_num_args()>0){
            $_cfg=&self::$in->_cfg;
            $args=func_get_args();
            foreach($args as $v){
                $v=explode(':',$v,2);
                $_cfg[$v[0]]=$v[1];
            }
        }

        if(empty(self::$in->_cfg['url'])){
            throw new Fend_Acl_Exception("Not Found cfg[url]",404);
        }
    }

    /**
     * 代理呼叫远程服务
     * //eval("class appfcli{};");
     *
     * @param  string $fc 可选参数
     * @return object Fend_Fcli_Base
    **/
    public function run($fc=null)
    {
        return new Fend_Fcli_Request($this,$fc);
    }
}

?>