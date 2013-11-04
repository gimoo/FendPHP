<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Eduu Inc. (http://www.eduu.com)
 *
 * 截取字符串
 * 通常用于截取一段正文的前面字符做为简介
 *
 * @param string $str  字符串文本
 * @param int    $mx   截取的长度
 * @param string $code 当前字符编码
 * @return string
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.strcut.php 4 2011-12-29 11:01:08Z gimoo $
**/

function strcut($str,$mx=10,$code='gbk')
{
    $str=strip_tags($str);
    $str=str_replace(array('　','“','”'),'',$str);//双字节的字符处理
    $str=preg_replace('/[\s]+|(&[#0-9a-z]+;)+/is','',$str);//过滤空白字符
    if(strlen($str)>$mx) $str=mb_strcut($str,0,$mx,$code);
    return $str;
}
?>