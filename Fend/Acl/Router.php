<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 路由器
 * 重新定位模块 进行软路由
 * 同时可以定制路由表权限
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Router.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Acl_Router extends Fend_Acl
{
    public function __construct(){}
    /**
     * 开始路由
     *
     * @param integer $tp 报告级别,1-3
     * @param integer $sp 是否返回串
     * @return string|echo
    **/
    public function toRoute(array &$route)
    {
        //指针向后移动一位
        $this->uri[1]=$this->uri[0];
        array_shift($this->uri);
        $this->uri[1]=$route['module'];
        $controller=&$this->uri[2];

        if($controller && isset($route['controller']) && is_array($route['controller']) && !in_array($controller,$route['controller'])){
            //请求的方法未被允许无权访问
            throw new Fend_Acl_Exception("Not Found Modules: {$controller}",403);
        }
    }
}
?>