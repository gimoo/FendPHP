<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 管理文件和文件夹
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Make.php 4 2011-12-29 11:01:08Z gimoo $
**/

Fend_Func::Init('doPath');//注册函数
class Fend_File_Make
{
    static $froot=NULL;//操作基目录
    static $fmod=0775;//建立文件的权限
    static function Init($root,$fmod=0755)
    {
        self::$froot=$root;//直接获取基地
        self::$fmod=$fmod;//直接获取基地
    }

    //创建系列目录-无基底--不安全
    static function PutDir($fdir,$root=NULL)
    {
        $fdir=doPath($fdir);
        empty($root) && $root=self::$froot;
        if(is_dir($root.$fdir)) return TRUE;//目录存在直接返回
        $fdir=explode('/',$fdir);
        $_tem=NULL;
        if(!$fdir[0] || false!==strpos($fdir[0],':')){
            $fdir[0].='/';
            $_tem=$fdir[0];
            unset($fdir[0]);
        }
        foreach($fdir as $v){
            if(!$v) continue;
            $_tem.=$v.'/';
            if(is_dir($root.$_tem)) continue;
            if(!mkdir(doPath($root.$_tem),self::$fmod)){
                return FALSE;
            }
        }
        return TRUE;
    }

    //生成文件
    static function putFile($fpath,$fbody)
    {
        if(!empty(self::$froot) && !is_writable(self::$froot)) return FALSE ;//基地目录不可写
        $dpath=dirname($fpath);
        if($dpath!='.' && $dpath!='..' && $dpath!='\\' && $dpath!='/') self::PutDir($dpath);//有目录存在
        return @file_put_contents(self::$froot.$fpath,$fbody);
    }

    //删除一个文件
    static function DelFile($fpath)
    {
        if(is_file($fpath)) return @unlink($fpath);
        return true;
    }
}




?>