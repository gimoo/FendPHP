<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * Soap静态激活对象
 * 客户端:
 *  $sp=Fend_Soap_Init::Factory(1);
 *  $sp->__SetCookie('Name','value');//注册COOKIE变量用于验证通行使用
 *  $sp->Init('uri:cmsApi','url:'.Cms_Conf_Bbs::$soapUrl);//初始化服务
 *  $sp->getTx();//调用远程方法
 *
 * 服务端
 *  $sp=Fend_Soap_Init::Factory(0);
 *  $sp->Init('name:cmsApi');//初始化服务
 *  $sp->setClass('cmsApi');//注册一个可用的服务对象
 *  $sp->handle();
 *  注: 服务端注册的函数以及对象中的方法不能以"__"打头,"__"为Fend_Soap的保留字符,否则可能会引起意想不到结果
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Init.php 4 2011-12-29 11:01:08Z gimoo $
**/

require_once('Server.php');
require_once('Client.php');
class Fend_Soap_Init
{
    public static $instance;
    public static $pageType=array(0=>'Fend_Soap_Server',1=>'Fend_Soap_Client');
    public static function factory($tp=0)
    {
        if(!isset(self::$instance)){
           $c=&self::$pageType[$tp];
           self::$instance = new $c;
       }
       return self::$instance;
    }


}


?>