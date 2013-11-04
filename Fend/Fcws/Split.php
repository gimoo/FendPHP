<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2010 Gimoo Inc. (http://fend.gimoo.net)
 *
 * CMS文章管理公共接口对象
 * 负责获取站点缓存并设置站点的相关参数
 *
 * 对码---
 * ASCII   [1]0x00-0x7F(0-127)
 * GBK     [1]0x81-0xFE(129-254) [2]0x40-0xFE(64-254)
 * GB2312  [1]0xB0-0xF7(176-247) [2]0xA0-0xFE(160-254)
 * Big5    [1]0x81-0xFE(129-255) [2]0x40-0x7E(64-126)| 0xA1－0xFE(161-254)
 * UTF8    单字节 0x00-0x7F(0-127) 多字节 [1]0xE0-0xEF(224-239) [2]0x80-0xBF(128-191) [3]0x80-0xBF(128-191)
 *
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Split.php 4 2011-12-29 11:01:08Z gimoo $
**/

define('FD_SPACESPLIT','#');//标签的间隔符
class Fend_Fcws_Split extends Fend_Fcws_Db
{

    /**
     * 预留方法 扩展使用
     *
     * @return object
    **/
    private static $in=null;
    public static function Factory()
    {
        if(null===self::$in) self::$in = new self;
        return self::$in;
    }

    /**
     * 写入词典库
     *
     * 储存格式为以第一个完整字符开始递拆串例如:
     * 开发工具VC -> 开 开发 开发工 开发工具 开发工具VC
     *
     * @param  string $key   关键词
     * @param  string $value 键对应的值
     * @return string
     */
    public function put($key, $value)
    {
        $key=strtolower($key);//不区分大小写
        $_len=strlen($key);
        for($i=0;$i<$_len;++$i){
            $old=ord($key[$i]);
            $_tmp=null;
            if($old<0x80){
                if(($i+1)<$_len && ord($key[$i+1])<0x80) continue;
                $_tmp=substr($key,0,$i+1);
            }else{
                $old_1=ord($key[++$i]);
                if($old>=0x80 && $old<=0xBF && $old_1>=0x80 && $old_1<=0x80 ){//UTF8
                    $_tmp=substr($key,0,++$i+1);
                }else{
                    $_tmp=substr($key,0,$i+1);
                }
            }
            !parent::get($_tmp) && parent::put($_tmp,FD_SPACESPLIT);
        }
        parent::put($key,$value);
    }

    /**
     * 检测给定的串中是否有关键词包含在字库中
     * 如果找到则返回被找到的第一个关键词,否则返回false
     *
     * @param  str $str  需要处理的字符串
     * @param  int $rank 关键词级别,总是小于等于指定的级别
     * @param  int $mx   当发现包含有低于设定级别的词时,获取指定数量后停止
     * @param  int $item 一般同mx一起使用,mx中设置的词数量被返回到结果中
     * @return string
     */
    public function get($str,$rank=0,$mx=5,&$item=null)
    {
        $str=strtolower($str);//不区分大小写
        $len=strlen($str);
        $_isout=false;//返回值
        $item=array();

        //双重跑马灯模式进行检测
        for($i=0;$i<$len;$i+=$_spa){
            $_c=$str[$i];
            $_o=ord($_c);
            if($_o<0x80){//单字节
                $_spa=1;//起点跑马
                if(self::_is_en_token($_o)) continue; //标点符号英文
            }else{//双字节
                $_spa=2;//起点跑马
                $_c.=$str[$i+1];
                if(false===parent::get($_c)) continue; //不会存在词
            }

            //第二重跑马
            $_len=($len-$i)<$this->_keymax ? ($len-$i) : $this->_keymax;
            for($j=$_spa; $j<$_len ; ++$j ){
                $_o=ord($str[$i+$j]);
                if($_o<0x80){//单字节
                    //当相邻的字符均是单字节字符,且非符号时,进行连串处理
                    if(!self::_is_en_token($_o) && $j<$_len-1 && ord($str[$i+$j+1])<0x80 && !self::_is_en_token(ord($str[$i+$j+1]))) continue;
                    $_spa==1 && $_spa=$j+1;
                }else{//双字节
                    ++$j;
                }
                $_tmp=substr($str,$i,$j+1);
                $_res=parent::get($_tmp);
                if(false===$_res){
                    break;//不存在词
                }elseif($_res==FD_SPACESPLIT){
                    continue;
                }elseif($rank==0 || $_res<=$rank){//找到设定级别的关键词
                    $item[$_tmp]=$_tmp;
                    $_isout=true;
                    break 2;
                }else{//低级别词
                    //$_isout=false===$_isout ? $_res : min($_res,$_isout);
                    $item[$_tmp]=$_tmp;

                    //是否满足了条目
                    if(count($item)>=$mx) break 2;
                    continue;
                }
                break;
            }
        }
        $item=join(' ',$item);
        return $_isout;
    }

    /**
     * 检测是否存在英文标点符号
     *
     * @param  string $str 字符
     * @return bool
    **/
    private function _is_en_token($_o)
    {
        return ($_o<=47 || ($_o>=58 && $_o<=64) || ($_o>=91 && $_o<=96) || $_o>=123);
    }
}
?>