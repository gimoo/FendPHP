<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 加载器 负载载入APP相关对象
 * 通常用于载入开发人员编写的非框架模块等
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Acl.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Acl extends Fend
{
    private $_module=array();//被注册的模块
    private $_routeobj=null;//路由器对象
    private $_route=array();//被路由的模块
    private static $in;//内置全局状态变量

    /**
     * 工厂模式: 激活并返回对象
     *
     * @return object
    **/
    public static function Factory()
    {
        if(!isset(self::$in)){
            self::$in=new self();
        }
        return self::$in;
    }

    /**
     * 初始化相关配置
     * 将一些相关配置变量初始化到系统全局变量中
     * 在任何PHP程序中可以通过this或GLOBAL调用
     *
     * @return object
    **/
    public function __construct()
    {
        //初始化默认配置到全局变量
        $this->aclcfg=array(
            'appsDir'=> null,        //APPS目录
            'deFunc'=> 'index',      //默认方法
            'deLib'=> 'index',       //默认Controller对象
            'deMod'=> 'default',     //默认模块
            'sufObj'=> 'lib',        //Controller后缀[对象的后缀]
            'sufLib'=> '_lib',       //Controller文件后缀[对象的后缀]
            'sufFunc'=> 'Fend',      //Action后缀[对象内方法的后缀]
            'isCase'=> 1,            //是否区分大小写-被保留
        );
        $this->uri=array($_SERVER['HTTP_HOST'],null,null,null);
        //$this->getParam();
    }

    /**
     * 设置配置加载器常用配置项
     *
     * @param array $cfg 配置项
    **/
    public function setAcl(array $cfg)
    {
        $this->aclcfg=array_merge($this->aclcfg,$cfg);
    }

    /**
     * 注册模块[模块注册器]
     * 模块注册器也是一种过滤器,当有模块被注册时
     * 且被run的模块不在被注册的范例内则该模块被停止呼叫并送出异常
     *
     * Example:
     * 方式一 $mods=array('sys','news','blog')
     * 方式二 $mods=array('sys'=>array(),'news'=>array('path'=>'/web/cms/news/'),'blog')
     * @param array $mods 被注册的模块
    **/
    public function setModule(array $mods)
    {
        foreach($mods as $k=>&$v){
            if(is_array($v)){
                $this->_module[$k]=$v;
            }else{
                $this->_module[$v]=array();
            }
        }
    }

    /**
     * 设置路由器
     *
     * Example:
     * http://example/news/
     * array('news'=>array('mod'=>'blogs',))
     * @param array $mods 被注册的模块
    **/
    public function setRoute(array $mods)
    {
        $this->_route=$mods;
    }

    /**
     * 获取模块对象并定位模块 ACL_url
     * 考虑到代理器/路由器的正常工作所有的传递进来的模块/控制器/方法都不区分大小写
     *
     * @param array $app 被注册的模块
    **/
    public function run($app)
    {
        if(!is_array($app)){
            $this->getParam($app);
        }

        //不区分大小写
        foreach($app as $k=>&$v){
            $this->uri[$k]=strtolower($v);
        }

        //检测是否存在路由
        if(!empty($this->uri[2]) && isset($this->_route[$this->uri[2]])){//存在路由配置
            $this->_getRoute()->toRoute($this->_route[$this->uri[2]]);
        }

        //将模块变量注入到全局中
        $module=&$this->uri[1];
        $controller=&$this->uri[2];
        $action=&$this->uri[3];
        empty($controller) && $controller=$this->aclcfg['deLib'];
        empty($module) && $module=$this->aclcfg['deMod'];

        //分解并检测模块是否存在
        $path=$this->aclcfg['appsDir'].$module.'/';
        !empty($this->_module) && $this->_isModule($module,$controller,$path);
        $fclass=$path.$controller.$this->aclcfg['sufLib'].'.php';

        //加载类库
        if(is_file($fclass)){
            @include_once($path.'common.php');//加载公共模块
            include_once($fclass);
            $fclass=$controller.$this->aclcfg['sufObj'];
            $c=new $fclass;

            //获取对象的所有Public方法
            $item = array_map('strtolower', get_class_methods($c));

            //检测被指定的方法是否存在不存在则使用Index作为默认方法
            (empty($action) || !in_array($action.strtolower($this->aclcfg['sufFunc']),$item)) && $action=$this->aclcfg['deFunc'];
            unset($item);

            //执行默认Init方法
            call_user_func_array(array($c,'Init'),array());

            //执行指定的方法
            call_user_func_array(array($c,$action.$this->aclcfg['sufFunc']),array());
        }else{
            throw new Fend_Acl_Exception("Not Found Object: {$controller}",404);
        }
        $this->cfg['debug'] && Fend_Debug::factory()->dump();
    }

    /**
     * 获取URL
     *
     * @param int $tp 获取集合还是一个
    **/
    public function getParam(&$app)
    {
        $app=strtok($app, '?');
        $app=explode('/',$app);
        $this->url=$app;
    }

    /**
     * 检查模块是否可执行
     *
     * @param string $module     模块
     * @param string $controller controller
     * @param string $path       Controller物理路径
    **/
    public function _isModule(&$module,&$controller,&$path)
    {
        //未被允许的模块
        if(!isset($this->_module[$module])){
            throw new Fend_Acl_Exception("Not Found Modules: {$module}",403);
        }elseif(isset($this->_module[$module]['controller']) && !in_array($controller,$this->_module[$module]['controller'])){
            throw new Fend_Acl_Exception("Not Found Controller: {$controller}",403);
        }
        isset($this->_module[$module]['path']) && $path=$this->_module[$module]['path'];
    }

    /**
     * 内部激活并返回ACL路由器对象
     * 该对象同Fend_Acl一起灭绝
     *
     * @return object
    **/
    private function _getRoute()
    {
        if(null === $this->_routeobj){
            $this->_routeobj = new Fend_Acl_Router();
        }
        return $this->_routeobj;
    }
}
?>