<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 数据库加载器,通过该模块进行加载与切换数据库应用
 * 如MYSQL MYSQLI Sqlserver等
 * 注意: 所有对象必须符合Fend_Db_Base标准
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Db.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Db
{
    static public $in=null;//保存当前对象

    /**
     * 工厂模式 静态激活对象
     * @param  string $dbClass  数据库类型
     * @return object $in       数据库对象
    **/
    public static function factory($dbClass)
    {
        if(!isset(self::$in)){
            $dbClass='Fend_Db_'.ucfirst(strtolower($dbClass));
            self::$in=new $dbClass;
        }
        return self::$in;
    }
}
?>