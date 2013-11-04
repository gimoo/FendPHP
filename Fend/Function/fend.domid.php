<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 检测是否在设定的两个数之间
 * 结果总是出现在边界
 * 例如:
 * domid(985,0,100)=100 无边界设置
 * domid(985,0,100,20,96)=96 大边界
 * domid(0,0,100,20,96)=20 小边界
 *
 * @param int $it     一个整数
 * @param int $min    边界,较小的数
 * @param int $max    边界,较大的数
 * @param int $min_de 小边界的默认数值
 * @param int $max_de 大边界的默认数值
 * @return int
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.domid.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doMid($it,$min,$max,$min_de=null,$max_de=null)
{
    if(null!==$min_de){
        $it<=$min && $it=$min_de;
    }else{
        $it=max($it,$min);
    }

    if(null!==$max_de){
        $it>=$max && $it=$max_de;
    }else{
        $it=min($it,$max);
    }
    return $it;
}
?>