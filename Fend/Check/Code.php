<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 生成验证码对象
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Code.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Check_Code
{
    public static $CodeMax=4;//验证码长度
    public static $CodeStr='__FD__code';

    /**
     * 设置验证码并已图片方式发送
    **/
    public static function Index()
    {
        $SecCode=self::Random(self::$CodeMax);
        setcookie(self::$CodeStr,md5($SecCode));//设置储存
        //---------------------------------------------------------bg
        $im = imagecreate(64, 20);
        $background_color = imagecolorallocate ($im, 220, 220, 220);
        for ($i=0; $i <= 128; $i++){
            $point_color = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, 64), mt_rand(0, 25), $point_color);
        }

        for($i = 0; $i < self::$CodeMax; $i++){
            $text_color = imagecolorallocate($im, mt_rand(0,255), mt_rand(0,128), mt_rand(0,255));
            $x = 5 + $i * 15;
            $y = mt_rand(0, 7);
            imagechar($im, 5, $x, $y,  $SecCode{$i}, $text_color);
        }

        header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        header("Pragma: no-cache");
        header('Content-type: image/png');
        header( "Content-Disposition:attachment;filename=chackcode.png ");
        //header( "Content-Disposition:inline;filename=chackcode.gif ");
        imagepng($im);
        imagedestroy($im);
    }

    /**
     * 检测验证码
     * @param integer $str 验证串
     * @return boolean 是否通过验证
    **/
    public static function isCode($str)
    {
        $item=@$_COOKIE[self::$CodeStr];
        return md5($str)==$item ? true : false;
    }

    /**
     * 随机获取验证字符集合
     * @param integer $length 验证串长度
     * @return string 随机获取的验证串
    **/
    public static function Random($length)
    {
        $hash = null;
        $chars = '0123456789';
        $max = strlen($chars) - 1;
        for($i = 0; $i < $length; $i++){
            $hash .= $chars{mt_rand(0, $max)};
        }
        return $hash;
    }
}
?>
