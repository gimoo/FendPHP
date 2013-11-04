<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 上载图片
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Upload.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_File_Upload
{
	//--------------------
	public $fmsg=array();
	public $upfsize=1048576;//允许上传文件大小小于等于1Mb
	public $upftype='gif|jpe|jpg|jpeg|png';//允许上传格式
	public $upfroot=NULL;//文件存放根路径
	public $upfsons=NULL;//文件存放子目录(<Y/m/d>)
	public $upfname='<rand.5>';//文件名称-用于覆盖已存在的图片(<rand.5><ucid>)

	//附件上传
	public function StartUp($str)
	{
		if(!isset($_FILES[$str])){
			return self::_Echo();//没有上传
		}elseif(!is_uploaded_file($_FILES[$str]['tmp_name'])){
			return self::_Echo($_FILES[$str]['error']);//没有上传
		}

		self::toExt();//获取文件扩展名
		self::toInfo($str);//获取上传文件相关信息

		//大小限制
		if($this->fmsg['size'] > $this->upfsize){
			return self::_Echo('102');//超过大小了
		}

		//扩展名限制
		if(!in_array(strtolower($this->fmsg['fext']),$this->upftype)){
			return self::_Echo('100');//不允许的文件扩展名
		}

		$this->upfname=self::toFileName($this->upfname);//生成一个随机的文件名称
		if(@move_uploaded_file($this->fmsg['tmp_name'],$this->upfname)){
			self::_Echo('0');
			return $this->fmsg['fpath'];
		}else{
			return self::_Echo('101');
		}
	}

	//获取上载附件的内容
	public function getBlob($str)
	{
		if(!isset($_FILES[$str])){
			return self::_Echo();//没有上传
		}elseif(!is_uploaded_file($_FILES[$str]['tmp_name'])){
			return self::_Echo($_FILES[$str]['error']);//没有上传
		}

		self::toExt();
		self::toInfo($str);//获取上传文件相关信息

		//大小限制
		if($this->fmsg['size'] > $this->upfsize){
			return self::_Echo('102');//超过大小了
		}

		//扩展名限制
		if(!in_array(strtolower($this->fmsg['fext']),$this->upftype)){
			return self::_Echo('100');//不允许的文件扩展名
		}

		self::_Echo();//上传成功
		$this->fmsg['fblob']=@file_get_contents($this->fmsg['tmp_name']);
		return $this->fmsg['fblob'];
	}

	private function toExt()
	{
		//fomart URL
		if(!function_exists('_sPath')){
			function _sPath($url){
				$url=preg_replace('/[\/\\\\]+/','/',$url);
				return $url;
			}
		}
		$this->upfroot=_sPath($this->upfroot);
		$this->upfsons=_sPath($this->upfsons);
		$this->upfname=_sPath($this->upfname);
		//处理允许上传扩展名后缀
		if(!is_array($this->upftype)){
			$this->upftype=str_replace(',','|',$this->upftype);
			$this->upftype=explode('|',$this->upftype);
		}

	}

	//取得上传文件信息
	private function toInfo($str)
	{
		$this->fmsg=$_FILES[$str];
		$this->fmsg['name']=strtolower($this->fmsg['name']);
		$this->fmsg['fext']=pathinfo($this->fmsg['name'],PATHINFO_EXTENSION);
	}

	//获取文件路径
	private function toFileName($str)
	{
		//转换路径格式
		!$str && $str=$this->fmsg['name'];
		$str=self::toPregPath($str);
		$this->upfsons=self::toPregPath($this->upfsons);//转换为/
		$this->upfroot=self::toPregPath($this->upfroot);

		//格式化子目录路径
		!empty($this->upfsons) && $this->upfsons=self::toPregBack($this->upfsons);
		!empty($str) && $str=self::toPregBack($str);

		if(!empty($this->upfsons)){
			$str=$this->upfsons.'/'.$str;
			$str=self::toPregPath($str);
		}

		//当根路径存在时,检测并加上必要的斜杠
		if(!empty($this->upfroot)){
			if(substr($this->upfroot,-1)=='/'){
				$str{0}=='/' && $str=substr($str,1);
			}else{
				$str{0}!='/' && $str='/'.$str;
			}
		}
		self::toMakeDir($str);//建立所需目录
		!pathinfo($str,PATHINFO_EXTENSION) && $str.='.'.$this->fmsg['fext'];

		$this->fmsg['fpath']=$str;
		$this->fmsg['tpath']=$this->upfroot;
		$this->fmsg['ftpath']=$this->upfroot.$str;
		return $this->fmsg['ftpath'];
	}

	//上传文件过程中发生的错误信息
	private function _Echo($str=NULL)
	{
		switch($str){
			case '0':
				$Ful='上传成功';
				break;
			case '1':
				//$Ful="上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值.";
				$Ful="上传的文件超过了服务器配置选项限制的值.";
				break;
			case '2':
				$Ful='上传文件的大小超过了HTML表单中MAX_FILE_SIZE选项指定的值。';
				break;
			case '3':
				$Ful='文件不完整,只有部分被上传';
				break;
			case '4':
				$Ful='没有任何文件被上传';
				break;
			case '6':
				$Ful='临时文件被丢失,可能系统缓存目录不可写';//找不到临时文件夹
				break;
			case '7':
				$Ful='文件写入失败';
				break;
			case '100':
				$Ful='上传失败,文件类型不允许';
				break;
			case '101':
				$Ful="复制文件失败!";
				break;
			case '102':
				$this->upfsize=ceil($this->upfsize/1024);
				$Ful="文件大小不能超过：{$this->upfsize}Kb.";
				break;
			case '103':
				$Ful="根目录不可写或不存在";
				break;
			case '104':
				$Ful="文件名称不能为空";
				break;
			default:
				$Ful='没有任何可上传的项';
				break;
		}
		$this->fmsg['msg']=$Ful;
		return FALSE;
	}

	private function toPregBack($str)
	{
		//处理替换搜索
		if(!function_exists('_toPregBack')){
			function _toPregBack($par){
				if(!$par[1]){
					return NULL;
				}elseif(FALSE===stripos($par[1],'rand')){
					//路径处理
					$par[1]=date(trim($par[1]),time());
				}else{
					//文件名称处理
					$length=pathinfo($par[1],PATHINFO_EXTENSION);
					if($length=='id'){
						return uniqid();
					}
					$length=(int)$length;
					$par[1] = NULL;
					$chars = '0123456789abcdefghigklmnopqrstuvwxyz';
					$max = strlen($chars) - 1;
					for($i = 0; $i < $length; $i++){
						$par[1] .= $chars{mt_rand(0, $max)};
					}
				}
				return $par[1];
			}
		}
		return preg_replace_callback('/<([^<>]*)>/i','_toPregBack',$str);
	}

	//转换路径
	private function toPregPath($str=NULL)
	{
		//DIRECTORY_SEPARATOR
		return preg_replace('/[\/]+/','/',$str);
	}

	//创建路径中所需的目录
	private function toMakeDir($fpath)
	{
		$DIrPar=$this->upfroot;
		$fpath=dirname($fpath);
		if(is_dir($DIrPar.$fpath)){return TRUE;}
		$fpath=explode('/',$fpath);

		//循环建立目录
		//print_r($fpath);
		substr($DIrPar,-1)=='/' && $DIrPar=substr($DIrPar,0,-1);
		foreach($fpath as $v){
			if(!$v) continue;
			$DIrPar.='/'.$v;
			if(is_dir($DIrPar)) continue;
			if(@mkdir($DIrPar,0777)){
				continue;
			}else{
				self::_Echo('103');
				break;
			}
		}
	}

}



/*
结果集数组:
$up->fmsg['msg']	//提示信息
$up->fmsg['fpath']	//文件地址,不包括基路径
$up->fmsg['tpath']	//基路径,不包括文件地址
$up->fmsg['ftpath']	//文件全路径

$up->fmsg['name']	//原上传文件名称
$up->fmsg['fext']	//文件后缀
$up->fmsg['type']	//文件类型
$up->fmsg['tmp_name']	//缓存临时文件路径
$up->fmsg['error']	//上传错误代码
$up->fmsg['size']	//上传文件大小


echo '<pre>';
$up=new UpFile;
$up->upfroot='D:\sss2008';
$up->upfname='<rand.5>';
$up->upfsons='';
echo $up->StartUp('mfile');
print_r($up->fmsg);

<form method="post" class="Pform" enctype="multipart/form-data">
<input type="file" name="mfile" />
<input type='submit' class='PSub' value=' 提 交 表 单 '>
</form>

*/
?>
