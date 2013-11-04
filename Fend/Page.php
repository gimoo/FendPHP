<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 工厂模式 激活分页对象
 * 用于动态载入及激活对象
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Page.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Page
{
    /**
     * 工厂模式 静态激活对象
     * @param  string $name  数据库类型
     * @return object $in       数据库对象
    **/
    public static function factory($name)
    {
        $obj='Fend_Page_'.ucfirst($name);
        $obj=new $obj;

        $args=func_get_args();unset($args[0]);
        foreach($args as $v){
            $v=explode(':',$v,2);
            if(!isset($v[1])) continue;
            $obj->$v[0]=$v[1];
        }
        return $obj;
    }
}

?>