<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 删除指定目录
 * 注意: 这将清理目录下所有存在的子目录或文件,慎重执行
 *
 * @param string $sdir 一个目录的物理路径
 * @return boolen false执行失败,true表示清理成功
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.dormdir.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doRmDir($sdir)
{
    if(!is_dir($sdir)) return true;
    $cwd=getcwd();
    if(!chdir($sdir)) return false;

    $fs=dir('./');
    while(false !==($entry=$fs->read())){
        if($entry=='.' || $entry=='..') continue;
        if(is_dir($entry)){
            if(!doRmDir($entry)) return false;
        }else{
            if(!unlink($entry)) return false;
        }
    }
    $fs->close();

    if(!chdir($cwd)) return false;
    if(!rmdir($sdir)) return false;
    return true;
}
?>