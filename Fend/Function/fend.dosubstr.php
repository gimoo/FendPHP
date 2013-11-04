<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Eduu Inc. (http://www.eduu.com)
 *
 * 字符串截取
 * 将一个字符串从指定的起始字符开始截取到指定的结束字符
 *
 * @param string $str     字符串
 * @param string $b_start 起始串
 * @param string $b_end   结束串
 * @return string|void
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.dosubstr.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doSubStr($str,$b_start,$b_end)
{
    //计算起始位置
    $s_pos=stripos($str,$b_start);
    if(false===$s_pos) return null;
    $s_pos+=strlen($b_start);

    //计算结束位置
    $e_pos=stripos($str,$b_end,$s_pos);
    if(false===$e_pos) return null;

    return substr($str,$s_pos,$e_pos-$s_pos);
}

?>