<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 缓存对象 主要用于生产配置文件,缓存数据
 * 如将页面部分信息,数据库查询结果集缓存到文件
 *
 * 配置文件的格式如下:
 * ================== 范例 ==========================
 *   Fend_dir    =/fend/
 *   Fend_Cache  =/Fend/Cache
 *   [scfg]
 *   isStart     =yes
 *   isMake      =1
 *
 *   以上通过get载入方式可以得到
 *   $var=array(
 *       'Fend_dir'=>'/fend/',
 *       'Fend_Cache'=>'/Fend/Cache',
 *       'scfg'=>array(
 *           'isStart'=>'yes',
 *           'isMake'=>'1',
 *       ),
 *   )
 *   通过load方式引入
 *   $_Fend_dir='/fend/'
 *   $_Fend_Cache='/Fend/Cache'
 *   $_scfg['isStart']='yes'
 *   $_scfg['isMake']='1'
 * ================== 范例 ==========================
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Conf.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Conf
{
    /**
     * 预留方法 扩展使用
     *
    **/
    public static function factory()
    {
    }

    /**
     * 载入配置
     * 作为数组返回
     *
     * @param  string  $fname 配置文件路径以及名称
     * @return array
    **/
    static function get($fname)
    {
        $item=array();
        $fs=fopen($fname,'rb');
        if(!$fs) return $item;
        //开始读取文件
        $k=null;//组的临时变量
        while($buf=fgets($fs,128)){
            $buf=trim($buf);
            if(empty($buf)) continue;
            //过滤注释
            $s=substr($buf,0,1);
            if($s==';' || $s=='#') continue;
            //组载入
            if($s=='[' && substr($buf,-1)==']'){
                $k=substr($buf,1,-1);
                continue;
            }

            list($key,$value)=explode('=',$buf);
            if(isset($k)){
                $item[$k][rtrim($key)]=ltrim($value);
            }else{
                $item[rtrim($key)]=ltrim($value);
            }
        }
        return $item;
    }

    /**
     * 载入配置
     * 作为数组返回
     *
     * @param  string  $fname 配置文件路径以及名称
     * @return bool
    **/
    static function load($fname)
    {
        $fs=fopen($fname,'rb');
        if(!$fs) return false;
        $item=&$GLOBALS;
        //开始读取文件
        $k=null;//组的临时变量
        while($buf=fgets($fs,128)){
            $buf=trim($buf);
            if(empty($buf)) continue;
            //过滤注释
            $s=substr($buf,0,1);
            if($s==';' || $s=='#') continue;
            //组载入
            if($s=='[' && substr($buf,-1)==']'){
                $k=substr($buf,1,-1);
                continue;
            }

            list($key,$value)=explode('=',$buf);
            if(isset($k)){
                $GLOBALS['_'.$k][rtrim($key)]=ltrim($value);
            }else{
                $GLOBALS['_'.rtrim($key)]=ltrim($value);
            }
        }
        return true;
    }
}

?>