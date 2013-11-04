<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 正确解析路径
 * 解析为符合当前系统的路径表示
 *
 * @param string $str 物理路径字符串
 * @return string 得到当前系统规范的物理路径
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.dopath.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doPath($str)
{
    $str=preg_replace('/[\/\\\\]+/',DIRECTORY_SEPARATOR,$str);
    substr($str,-1)!=DIRECTORY_SEPARATOR && $str.=DIRECTORY_SEPARATOR;
    return $str;
}
?>