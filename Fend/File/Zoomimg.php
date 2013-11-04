<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 缩放图片
 * 保存最佳精度缩放图片
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Zoomimg.php 4 2011-12-29 11:01:08Z gimoo $
**/

//生成缩略图
class Fend_File_Zoomimg
{
    static $_Swidth=0;//图片实际的宽度
    static $_Sheight=0;//图片实际的高度

    //创建新图象*适合拉伸和缩放
    //toZoom(文件绝对地址,缩放宽度,缩放高度,缩放后文件后缀)
    static function toZoom($filename,$W=0,$H=0,$fgoal=null)
    {
        if($W==0 && $H==0) return FALSE;//没有设置宽和高时不予处理
        if(false===($im = self::GetImgType($filename))) return FALSE;
        $filename=self::getImgSmall($filename,$fgoal);//得到小图地址

        if($W==0){
            //当宽为0时自适应高
            if($H>self::$_Sheight){
                //超过原图大小不欲缩放
                ImageDestroy($im);//销毁临时图象释放内存
                return FALSE;
            }else{
                $W=ceil(self::$_Swidth*$H/self::$_Sheight);
                $newim = imagecreatetruecolor($W,$H);
                imagecopyresampled($newim,$im,0,0,0,0,$W,$H,self::$_Swidth,self::$_Sheight);
                ImageJpeg($newim,$filename);//创建处理后的图片
            }

        }elseif($H==0){
            //当高等于0时自动使用宽
            if($W>self::$_Swidth){
                //超过原图大小不欲缩放
                ImageDestroy($im);//销毁临时图象释放内存
                return FALSE;
            }else{
                $H=ceil(self::$_Sheight*$W/self::$_Swidth);
                $newim = imagecreatetruecolor($W,$H);
                imagecopyresampled($newim,$im,0,0,0,0,$W,$H,self::$_Swidth,self::$_Sheight);
                ImageJpeg ($newim,$filename);//创建处理后的图片
            }
        }else{
            //定宽高缩放
            $ImgWall=self::MakeZoom(self::$_Swidth,self::$_Sheight,$W,$H);
            $newim = imagecreatetruecolor($W,$H);
            //处理背景------------------------------------------//
            $back = imagecolorallocate($newim, 255, 255, 255);//
            imagefilledrectangle($newim, 0, 0, $W, $H, $back);//
            //处理背景------------------------------------------//
            imagecopyresampled($newim,$im,$ImgWall[2],$ImgWall[3],0,0,$ImgWall[0],$ImgWall[1],self::$_Swidth,self::$_Sheight);
            ImageJpeg($newim,$filename);//创建处理后的图片
            ImageDestroy($im);//销毁临时图象释放内存
        }
        return TRUE;
    }

    //添加水印文字适合英文字符
    static function toSeal($filename,$ImgText=NULL)
    {
        if(!$ImgText) return FALSE;
        if(false===($im = self::GetImgType($filename))) return false;

        $bg=imagecolorallocate($im, 218, 218, 218);//设置水印背景色
        $textcolor=imagecolorallocate($im, 0, 0, 0);//文字颜色
        imagefilledrectangle($im,self::$_Swidth-180,self::$_Sheight-16,self::$_Swidth-2,self::$_Sheight-2,$bg);
        //$string = iconv('gb2312','utf-8',$ImgText);
        //$fon=imagettftext($im, 12, 0, 11, 21, $textcolor, 'simhei.ttf', $string);
        imagestring($im,4, self::$_Swidth-175, self::$_Sheight-18,$ImgText, $textcolor);
        ImageJpeg ($im,$filename);
        //header("Content-type: image/png");
        //imagepng($im);
        return true;
    }

    //得到小图的地址
    static function getImgSmall($filename,$fgoal=null)
    {
        if(!empty($fgoal)){
            if(false===($fed=strripos($filename,'.',strlen($filename)-5))){
                $filename.=$fgoal;
            }else{
                $fed=substr($filename,$fed);
                $filename=str_replace($fed,$fgoal,$filename);
            }
        }
        return $filename;
    }

    ///计算缩放
    static function MakeZoom(&$Sw,&$Sh,&$Dw,&$Dh)
    {
        $Ful=array(0=>0,1=>0,2=>0,3=>0);
        if($Sw<=$Dw && $Sh<=$Dh){
            $W=$Sw;
            $H=$Sh;
            $Ful[2]=($W< $Dw) ? ceil(($Dw-$W)/2) : 0;
            $Ful[3]=($H< $Dh) ? ceil(($Dh-$H)/2) : 0;
        }else{
            //保持图象最佳效果
            $W=ceil($Dh*$Sw/$Sh);
            $W=($W>=$Dw-1 && $W<=$Dw+1) ? $Dw : $W;
            if($W<$Dw){
                $H=ceil($Dw*$Sh/$Sw);
                $H=($H>=$Dh-1 && $H<=$Dh+1) ? $Dh : $H;
                $W=$Dw;
                $Ful[3]=ceil(($Dh-$H)/2);
            }else{
                $H=ceil($W*$Sh/$Sw);
                $H=($H>=$Dh-1 && $H<=$Dh+1) ? $Dh : $H;
                $Ful[2]=ceil(($Dw-$W)/2);
            }
        }
        $Ful[0]=$W;
        $Ful[1]=$H;
        return $Ful;
    }

    //取得源图片的高/宽并建立同类型临时底图
    static function GetImgType($filename)
    {
        $name = getimagesize($filename); //取得图象信息
        self::$_Swidth = $name[0];	//取得图象实际宽度
        self::$_Sheight = $name[1];	//取得图象实际高度
        //根据实际类型创建临时图象
        switch($name[2]){
            case 1:
                $ImageTemp = imagecreatefromgif($filename);
                break;
            case 2:
                $ImageTemp = imagecreatefromjpeg($filename);
                break;
            case 3:
                $ImageTemp = imagecreatefrompng($filename);
                break;
            default:
                return false;
        }
        return $ImageTemp;
    }
}

/*
  使用实例------------------------------------------------------
  Fend_File_Zoomimg::toZoom('bb.jpg',130,0,'_small');//缩放图片,高自适应
  Fend_File_Zoomimg::toZoom('bb.jpg',0,500,'_small');//缩放图片,宽自适应
  Fend_File_Zoomimg::toZoom('bb.jpg',200,100,'_small');//缩放图片,固顶宽高
  Fend_File_Zoomimg::toSeal('bb.jpg','hello');//增加水印
*/
?>