<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 格式化连续的ID集合
 * 格式化连续的ID集合结果集为: 1,2,3,4,5,6...
 *
 * @param string $id 一个字符串ID集合
 * @return string 得到一个使用逗号隔开的ID串集合
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.dosetid.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doSetId($id)
{
    if(!empty($id)){
        $id=preg_replace(array('/[^\d,]/','/[,]{2,}/'),array('',','),$id);
        $id=trim($id,',');
        !$id && $id=0;
    }else{
        $id=0;
    }
    return $id;
}
?>