<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Eduu Inc. (http://www.eduu.com)
 *
 * Js的Escape函数编码的字符进行解码
 *
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.unescape.php 4 2011-12-29 11:01:08Z gimoo $
**/

function unEscape($str)
{
    $str = rawurldecode($str);
    preg_match_all('/%u.{4}|&#x.{4};|&#d+;|.+/U',$str,$r);
    $ar = $r[0];
    foreach($ar as $k=>$v){
        if(substr($v,0,2) == '%u'){
            $ar[$k] = iconv('UCS-2','GBK',pack('H4',substr($v,-4)));
        }elseif(substr($v,0,3) == '&#x'){
            $ar[$k] = iconv('UCS-2','GBK',pack('H4',substr($v,3,-1)));
        }elseif(substr($v,0,2) == '&#'){
            $ar[$k] = iconv('UCS-2','GBK',pack('n',substr($v,2,-1)));
        }
    }
    return join('',$ar);
}
?>