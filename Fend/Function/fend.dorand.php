<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 获取一个自定义字符集合的随机数
 *
 * @param int    $len  随机取得长度
 * @param string $chr  只能为单字节字符
 * @return string
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.dorand.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doRand($len,$chr = '0123456789abcdefghigklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWSYZ')
{
    $hash = null;
    $max = strlen($chr) - 1;
    for($i = 0; $i < $len; $i++){
        $hash .= $chr{mt_rand(0, $max)};
    }
    return $hash;
}
?>