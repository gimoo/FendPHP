<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 获取GET参数
 * 检测并增加addslashes
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.doget.php 4 2011-12-29 11:01:08Z gimoo $
**/

//$_GET = array_change_key_case($_GET);
if(get_magic_quotes_gpc()){
    function doGet($name) {
        if(isset($_GET[$name])){
            return is_array($_GET[$name]) ? $_GET[$name] : trim($_GET[$name]);
        }else{
            return null;
        }
    }
}else{
    function doGet($name) {
        if(isset($_GET[$name])){
            return is_array($_GET[$name]) ? $_GET[$name] : addslashes(trim($_GET[$name]));
        }else{
            return null;
        }
    }
}
?>