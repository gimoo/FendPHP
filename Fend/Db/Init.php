<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 自动载入函数模块 [Auto Load Function]
 * 函数处理在框架中开发,动态的加载外部函数
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Init.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Db_Init
{
    static public $in=NULL;
    public static function factory($dbClass)
    {
        if(!isset(self::$in)){
            $dbClass='Fend_Db_'.$dbClass;
            self::$in=new $dbClass;
        }
        return self::$in;
    }
}


?>