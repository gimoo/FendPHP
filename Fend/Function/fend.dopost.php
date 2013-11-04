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
 * @version $Id: fend.dopost.php 4 2011-12-29 11:01:08Z gimoo $
**/

//$_POST = array_change_key_case($_POST);
if(get_magic_quotes_gpc()){
    function doPost($name) {
        if(isset($_POST[$name])){
            return is_array($_POST[$name]) ? $_POST[$name] : trim($_POST[$name]);
        }else{
            return null;
        }
    }
}else{
    function doPost($name) {
        if(isset($_POST[$name])){
            return is_array($_POST[$name]) ? $_POST[$name] : addslashes(trim($_POST[$name]));
        }else{
            return null;
        }
    }
}

?>