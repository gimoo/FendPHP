<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 存储引擎的干扰码 [COOKIE]
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Token.php 4 2011-12-29 11:01:08Z gimoo $
**/

Fend_Func::Init('fdcode');
class Fend_Check_Token
{
    public static $CodeMax=5;//settings Code length
    public static $CodeStr='__FD__token';
    public static $CodeItem=null;

    public static function Init()
    {
        return self::toCode(1);
    }

    /**
     * 生成加密后的C串
     * @param   integer $t 是否重新生成串 1为重新生成
     * @return  string 随机加密串
    **/
    public static function toCode($t=0)
    {
        if($t && (!empty(self::$CodeItem) || !empty($_COOKIE[self::$CodeStr]))){
            return fdcode(empty(self::$CodeItem) ? $_COOKIE[self::$CodeStr] : self::$CodeItem);
        }
        $item=$SecCode=self::Random(self::$CodeMax);
        !empty($_COOKIE[self::$CodeStr]) && $item=fdcode($_COOKIE[self::$CodeStr]);
        self::$CodeItem=fdcode($SecCode,'encode');
        setcookie(self::$CodeStr,self::$CodeItem);//设置储存
        return $item;
    }

    /**
     * 验证输入的串是否正确
     * @param   string $str 被验证的字符串
     * @return  Boolean 是否通过验证
    **/
    public static function isCode($str)
    {
        return $str===self::toCode() ? true : false;
    }

    /**
     * 随机获取验证字符集合
     * @param   integer $length 验证串长度
     * @return  string 随机获取的验证串
    **/
    public static function Random($length)
    {
        $hash=null;
        $chars='qwertyuiopasdfghjklzxcvbnmQWERTYUIOPLKJHGFDSAZXCVBNM0123456789';
        $max=strlen($chars)-1;
        for($i=0;$i<$length;$i++){
            $hash.=$chars{mt_rand(0,$max)};
        }
        return $hash;
    }
}
//Fend_Check_Token::Init();
?>
