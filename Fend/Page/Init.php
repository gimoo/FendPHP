<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 工厂模式 激活分页对象
 * 用于动态载入及激活对象
 *
 * 注意: 准备废弃 在下一版本即将删除该对象, 请使用Fend_Page对象
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Init.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Page_Init
{
    public static $pageType=array(
        0=>'Fend_Page_Dbpage',
        1=>'Fend_Page_Dbpage1',
    );

    public static function factory($tp=0)
    {
       $c=&self::$pageType[$tp];
       $c=new $c;
       return $c;
    }
}


?>