<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Eduu Inc. (http://www.eduu.com)
 *
 * 检测串中是否包含中文
 *
 * @param string $str 需要检测的字符串
 * @return bool 包含中文为true 否则为false
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.isgbk.php 4 2011-12-29 11:01:08Z gimoo $
**/

function isgbk($str)
{
    for($i=0,$j=strlen($str);$i<$j;$i++){
      if(ord($str{$i})>0xa0) return true;
    }
    return false;
}
?>