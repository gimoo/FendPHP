<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 处理字符集格式化为TAG标签可用字符
 * 准备废弃了
 *
 * @param string $string    需要处理的字符串
 * @param string $operation 加解类型,de解密|en加密
 * @param string $key       计算基数,默认为FDKEY
 * @param string $expiry    过期时间
 * @return string
 * @--------------------------------
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: fend.dotag.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doTag($str,$t='all',$spStr=' ',$unStr='+#')
{
    $Len=strlen($str);
    $item=array('max'=>0,'min'=>$Len,'tag'=>null,'sum'=>0);
    $lx=0;//标签长度
    $okstr=&$item['tag'];
    for($i=0;$i<$Len;$i++){
        $od=ord($str{$i});
        if($od>=0x81){//双字节
            $c=$str{$i}.$str{++$i};
            $n=hexdec(bin2hex($c));
            if($n>0xA13F && $n<0xAA40){//是符号
                if(false===stripos($unStr,$c)){//被召回
                    if($lx<=0) continue;
                    $okstr.=$spStr;
                    $item['sum']++;
                    $item['min']=min($item['min'],$lx);
                    $item['max']=max($item['max'],$lx);
                    $lx=0;
                    continue;
                }
            }
            $lx+=2;
            $okstr.=$c;
            continue;
        }elseif(($od>=48 && $od<=57) || ($od>=65 && $od<=90) || ($od>=97 && $od<=122) || false!==stripos($unStr,$str{$i})){//单字节
            $okstr.=$str{$i};
            $lx++;
            continue;
        }else{
            if($lx<=0) continue;
            $okstr.=$spStr;
            $item['sum']++;
            $item['min']=min($item['min'],$lx);
            $item['max']=max($item['max'],$lx);
            $lx=0;
        }
    }
    if($lx>0){
        $item['sum']++;
        $item['min']=min($item['min'],$lx);
        $item['max']=max($item['max'],$lx);
    }else{
        $okstr=substr($okstr,0,-1);
    }
    return isset($item[$t]) ? $item[$t] : $item;
}

?>