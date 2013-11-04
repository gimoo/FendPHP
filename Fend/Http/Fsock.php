<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * Fsock远程通讯模块
 * 比如: 发送伪造的HEADER头至服务器,并实现模拟机器人访问站点
 *
 * Example: Fend_Http_Fsock::Factory('time:5','size:30','loop:1','charset:gb2312')->get('http://fend.gimoo.net');
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Fsock.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Http_Fsock
{
    private $_cfg=array(
        'time'=>30,//连接超时
        'agent'=>'Mozilla/4.0 (Compatible; Msie 6.0; Windows Nt 5.1; Sv1; Mozilla/4.0(Maxthon)',//伪造代理
        'size'=>10000,//连接超时(单位KB)
        'method'=>'GET',//链接类型--GET/HEAD/ POST
        'loop'=>0,//设置重定向深度,0为关闭
        'out'=>'body',//设置返回结果集合,head,body,tohead
        'referer'=>null,//设置来访URL地址
        'ip'=>null,//设置主机/服务器IP地址
        'charset'=>null,//返回结果字符集
    );//fsock配置

    public static $in;
    public static function factory()
    {
        if(null===self::$in) self::$in = new self();

        //获取参数设置val('time:30','agent:get')
        if(func_num_args()>0){
            $_cfg=&self::$in->_cfg;
            $args=func_get_args();
            foreach($args as $v){
                $v=explode(':',$v,2);
                $_cfg[$v[0]]=$v[1];
            }
        }
        return self::$in;
    }

    /**
     * 获取页面信息
     *
     * @param  string  $url Url连接地址
     * @return array|string
    **/
    public function get($url)
    {
        $body=self::getFile($url);//进行sock链接
        !empty($this->_cfg['charset']) && $body['body'] && $body['charset']=self::ckCharset($body['body']);
        return isset($body[$this->_cfg['out']]) ? $body[$this->_cfg['out']] : $body;
    }

    //检测是否是检测远程文件类型
    public function getFile($url)
    {
        $_data=array('body'=>'','head'=>array(),'tohead'=>null);

        //取得格式化URL得到 HOST REQUIST
        $url=str_replace(' ','%20',$url);
        $_url=@parse_url($url);
        if(empty($_url['scheme']) || empty($_url['host'])) return $_data;//非法地址
        switch ($_url['scheme']) {
            case 'https':
                $_url['scpact'] = 'ssl://';
                !isset($_url['port']) && $_url['port'] = 443;
                break;
            case 'http':
            default:
                $_url['scpact'] = '';
                !isset($_url['port']) && $_url['port'] = 80;
        }
        !isset($_url['path']) && $_url['path']='/';
        $_url['uri']= empty($_url['query']) ? $_url['path'] : $_url['path'].'?'.$_url['query'];
        $_url['url']=$_url['scheme'].'://'.$_url['host'];
        $_url['ip']=empty($this->_cfg['ip']) ? $_url['host'] : $this->_cfg['ip'];

        //定制Header头信息
        $http_header = null;
        $http_header.= "{$this->_cfg['method']} {$_url['uri']} HTTP/1.0\r\n";
        $http_header.= "Host: {$_url['host']}\r\n";
        isset($this->_cfg['referer']) && $http_header.= "Referer: {$this->_cfg['referer']} \r\n";
        $http_header.= "Connection: close\r\n";
        $http_header.= "Cache-Control: no-cache\r\n";
        $http_header.= "User-Agent: {$this->_cfg['agent']}\r\n";
        $http_header.= "\r\n";
        $_data['tohead']=&$http_header;//请求头信息

        //fsockopen(链接域名,端口,错误代码,错误详细信息,超时时间秒)
        $fp=@fsockopen($_url['scpact'].$_url['ip'], $_url['port'], $errno, $errstr, $this->_cfg['time']);
        if(!$fp){ $_data['msg']=$errstr;return $_data; }//连接失败
        stream_set_blocking($fp, true);//设置为阻塞模式访问数据流
        socket_set_timeout($fp,$this->_cfg['time']);//设置超时时间

        @fwrite($fp, $http_header);//已经连接上写入头信息//$CRLF="\x0d"."\x0a"."\x0d"."\x0a";
        $thd=true;
        //循环读取文件字节流
        $bodysize=$this->_cfg['size']*1024;//得到字节数
        $info = stream_get_meta_data($fp);
        while( !feof($fp) && (!$info['timed_out']) ){
            $tmp_stream=fgets($fp,128);//读文件字节流
            $info = stream_get_meta_data($fp);
            //是否还在头
            if($thd){

                //获取HTTP的答复代码
                if(!isset($_data['head']['http'])){
                    $_data['head']['http']=trim($tmp_stream);
                    //$_data['head']['url']=$url;
                    continue;
                }

                //检测是否读取头信息结束
                if($tmp_stream == "\r\n"){
                    if(false===stripos($_data['head']['http'],'200') || $this->_cfg['out']=='head') break;
                    $thd=false;continue;
                }

                //分析头信息
                if(!preg_match('/([^:]+):(.*)/i',$tmp_stream,$tmp_hd)){
                    $_data[]=trim($tmp_stream);
                    continue;//取得HEADER 头部分
                }

                $tmp_hd[1]=strtolower(trim($tmp_hd[1]));
                $tmp_hd[2]=trim($tmp_hd[2]);
                $_data['head'][$tmp_hd[1]]=$tmp_hd[2];//保存头信息到结果集

                if($this->_cfg['loop']<=0) continue;//跳转深度

                //检测是否被转向
                if($tmp_hd[1]=='location'){
                    --$this->_cfg['loop'];//深度设置
                    if(false!==stripos($tmp_hd[2],'cncmax.cn')) break;
                    if(substr($tmp_hd[2],0,7) != 'http://'){
                        if(substr($tmp_hd[2],0,1) == '/'){
                            $tmp_hd[2]=$_url['url'].$tmp_hd[2];//---/web/index.html
                        }else{
                            $tmp_hd[2]=$_url['url'].substr($_url['path'],0,strrpos($_url['path'],'/')).'/'.$tmp_hd[2];//--web/index.html
                        }
                    }
                    @fclose($fp);//关闭连接
                    $this->_cfg['referer']=&$url;
                    $this->_cfg['ip']=null;
                    $_data=$this->getFile($tmp_hd[2]);//开始跳转进行第二次尝试连接
                    break;
                }
            }else{
                if($bodysize<=0) break;
                $bodysize=$bodysize-strlen($tmp_stream);
                $_data['body'].=$tmp_stream;
            }
        }
        @fclose($fp);
        return $_data;
    }

    //从网页获取编码类型并进行转换 <meta http-equiv="content-type" content="text/html; charset=gb2312">
    private function ckCharset(&$str)
    {
        //从网页获取编码
        if(preg_match('/<meta[^>]+(?:charset=|encode=)([a-z0-9\-]+)[\'"]/i',$str,$_code)){
            $_code=strtolower(trim($_code[1]));
        }else{
            $_code='';
        }

        //当未获取到编码或编码是UTF8/BGK/GB2312时进行字符集认证,确保字符集的正确性
        if(empty($_code)){
            //$_code='iso-8859-1';
            $len=strlen($str);
            for($i=0;$i<$len;++$i){
                $_c1=ord($str[$i]);
                if($_c1<0x80){//单字节
                    continue;
                }else{//已经找到多字节字符
                    $_c2=ord($str[++$i]);
                    $_c3=++$i<$len ? ord($str[$i]) : 0x00;
                    if($_c1>=0xE0 && $_c1<=0xEF && $_c3>=0x80 && $_c3<=0xBF && $_c3>=0x80 && $_c3<=0xBF){
                        $_code='utf-8';
                    }elseif($_c1>=0x81 && $_c1<=0xFE && $_c2>=0x40 && $_c2<=0xFE){
                        $_code='gbk';
                    }
                    break;
                }
            }
        }

        if($this->_cfg['charset']==$_code || $_code=='iso-8859-1' || empty($_code)){
            return $_code;
        }else{
            $str=mb_convert_encoding($str,$this->_cfg['charset'],$_code);
        }
        return $_code;
    }
}

?>