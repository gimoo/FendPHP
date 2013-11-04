<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * Soap服务器扩展对象
 *
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Server.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Fcli_Server
{
    private $_cfg=array();//配置信息
    private $_fmod=array();
    private $_fclass=array();

    private static $in=null;
    public static function Factory()
    {
        if(null===self::$in) self::$in=new self();
        return self::$in;
    }

    /**
     * 初始化服务
     * 设置相关参数与通信接口
     * 必须参数为url
     * 获取参数设置val('time:30','agent:get')
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
    }

    /**
     * 注册一个对象
     * 提供远程调用,多次调用会覆盖之前注册的对象
     *
     * @param string fclass 对象名称
     * @param array func   对象可以提供的方法集合
     * @return void
    **/
    public function regClass($fclass,array $func=array())
    {
        //检测输入类型
        if(is_array($fclass)){
            $_fk=$fclass[0];
            $_fv=$fclass[1];
        }else{
            $_fk=$_fv=$fclass;
        }

        //转换为小写
        $_fk=strtolower($_fk);
        $this->_fclass[$_fk]=$_fv;

        //转换方法大小写
        if(count($func)>0){
            foreach($func as &$v) $v=strtolower($v);
        }
        $this->_fmod[$_fk]=$func;
    }

    /**
     * 注册一个函数
     * 提供远程调用,注册多个可以多次调用
     *
     * @param string func 函数名称
     * @return void
    **/
    public function regfunc($func)
    {
        !isset($this->_fmod[0]) && $this->_fmod[0]=array();
        $this->_fmod[0][]=strtolower($func);
    }

    /**
     * 代理呼叫远程服务
     * //eval("class appfcli{};");
     *
     * @param  string $fc 可选参数
     * @return object Fend_Fcli_Base
    **/
    public function run()
    {
        //认证密钥
        if(!empty($this->_cfg['key'])){
            if(!isset($_COOKIE['key']) || $_COOKIE['key']!==$this->_cfg['key']){
                self::showMSG("Authentication Failed.");
            }
        }

        //检测是否配置API
        if(count($this->_fmod)<=0){
            self::showMSG("Function OR Class is not registered.");
        }

        //接受传递过来的参数集合
        $this->_cfg['_fkey']=&$_SERVER['HTTP_FCLI_KEY'];//唯一KEY
        $fcli_mod=&$_SERVER['HTTP_FCLI_MOD'];//对象
        $fcli_func=&$_SERVER['HTTP_FCLI_FUNC'];//方法
        $fcli_pars=&$_POST;
        $fcli_mod=strtolower($fcli_mod);
        $fcli_func=strtolower($fcli_func);

        //认证模块
        if(empty($this->_cfg['_fkey'])){
            self::showMSG("Authentication Fkey.");
        }

        //检测访问方法
        if($fcli_mod=='#'){//函数调用
            $fcli_mod=0;
            if(!isset($this->_fmod[$fcli_mod])){
                self::showMSG("Function is not registered.");
            }
        }elseif(empty($fcli_mod)){
            $fcli_mod=key($this->_fmod);
        }elseif(!isset($this->_fmod[$fcli_mod])){
            self::showMSG("Class is not registered[$fcli_mod].");
        }

        //认证方法
        try{
            if($fcli_mod===0){//函数调用
                if(!in_array($fcli_func,$this->_fmod[$fcli_mod])){//验证用户权限
                    self::showMSG("Function is not registered[$fcli_func].");
                }elseif(!function_exists($fcli_func)){//验证系统权限
                    self::showMSG("Function not found[$fcli_func].");
                }
                $_fmod=&$fcli_func;
            }else{

                //可能有异常发生
                if(!class_exists($this->_fclass[$fcli_mod])) self::showMSG("Class not found[$fcli_mod].");

                //检测是对象访问权限
                if(!empty($this->_fmod[$fcli_mod]) && !in_array($fcli_func,$this->_fmod[$fcli_mod])){
                    self::showMSG("Class Function is not registered[$fcli_func].ED");
                }

                $fcli_mod=$this->_fclass[$fcli_mod];
                $fcli_mod=new $fcli_mod();
                if(!method_exists($fcli_mod,$fcli_func)){
                    self::showMSG("Class Function not found[$fcli_func].ED");
                }
                $_fmod=array(&$fcli_mod,$fcli_func);
            }

            $res=call_user_func_array($_fmod , $fcli_pars);
            self::showMSG($res,200);
        }catch(Exception $e){
            self::showMSG($e->getMessage(),500);
        }
    }

    /**
     * 送出结果集合
     *
     * @param  string $res   结果集合
     * @param  string $appid 状态
     * @return void
    **/
    private function showMSG($res,$appid=0)
    {
        $_type=0;//数组为1,字符串为0
        if(is_array($res)){
            $_type=1;
            $res=http_build_query($res);
        }

        !isset($this->_cfg['_fkey']) && $this->_cfg['_fkey']=uniqid();

        //发送头信息
        header("Content-Length: ".strlen($res));
        header("Fend-TYPE: {$_type}");//返回类型
        header("Fend-APP: {$appid}");//返回状态
        header("Fend-KEY: {$this->_cfg['_fkey']}");//连接唯一KEY
        die("{$res}");
    }

}

?>