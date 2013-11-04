<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * Fcli客户端扩展对象
 * 负责创建套接字连接,并取回结果集合
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Request.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Fcli_Request
{
    private $_fmod=null;
    private $_client=null;
    private $_cookie=array();
    public function __construct( Fend_Fcli_Client &$client , $fmod)
    {
        $this->_fmod=&$fmod;
        $this->_client=&$client;
        $this->_cookie=$client->_cfg;
        unset($this->_cookie['url'],$this->_cookie['debug']);
    }

    public function __call($fn,$fv)
    {
        $timeout=$this->_client->_cfg['time'];
        $url=$this->_client->_cfg['url'];
        $ps = parse_url($url);
        $host = @$ps['host'];
        $path = !empty($ps['path']) ? $ps['path'].(!empty($ps['query']) ? '?'.$ps['query'] : '') : '/';
        $port = !empty($ps['port']) ? $ps['port'] : $this->_client->_cfg['port'];
        $fkey = uniqid(substr(md5(microtime()),-4));
        unset($ps);

        //设置远程Cookie
        $cookie=$this->_cookie;
        foreach($cookie as $k=>&$v){
            $v="{$k}=".urlencode($v);
        }
        $cookie=join(';',$cookie);

        //定制Header信息
        $fv=http_build_query($fv);
        $out = "POST $path HTTP/1.0\r\n";
        $out .= "Accept: */*\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "User-Agent: GimooFend Rsync/1.0(+Http://s.eduu.com/bbs)\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Fcli-Mod: {$this->_fmod}\r\n";
        $out .= "Fcli-Func: {$fn}\r\n";
        $out .= "Fcli-key: {$fkey}\r\n";
        $out .= 'Content-Length: '.strlen($fv)."\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Cache-Control: no-cache\r\n";
        $out .= "Cookie: {$cookie}\r\n\r\n";
        $out .= $fv;

        //开始与远程建立连接
        !empty($this->_client->_cfg['host']) && $host=$this->_client->_cfg['host'];
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $item=array(
            'status'=>0,//状态
            'type'=>0,//类型
            'head'=>array(),//头信息
            'msg'=>'',//结果集合
            'error'=>''//错误信息
        );

        //连接到服务器失败
        if(!$fp) {
            $item['error']="[{$errno}]{$errstr}";
            return $item;
        }

        //设置读取数据流环境
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);
        fwrite($fp, $out);

        $_thd=false;
        while(!feof($fp)){
            $_buf=fgets($fp,4096);//读文件字节流

            //读取头信息
            if(!isset($_ishd)){

                //读取头信息结束
                if($_buf=="\r\n"){
                    $_ishd=true;
                    continue;
                }

                //分析头信息
                $item['head'][]=trim($_buf);
                if(!preg_match('/^Fend-([A-Z]*):(.*)/i',$_buf,$_rs)) continue;
                switch($_rs[1]){
                    case 'APP'://状态
                        $item['status']=trim($_rs[2]);
                        break;
                    case 'TYPE'://结果类型
                        $item['type']=trim($_rs[2]);
                        break;
                    case 'KEY'://唯一key
                        if(trim($_rs[2])!=$fkey){
                            $item['error']='Authentication FendKey Failed.';
                            break 2;
                        }
                        $_thd=true;
                        break;
                    default:
                        break;
                }
                continue;
            }

            //检查fendkey是否验证通过
            if(!$_thd) break;

            if($item['status']==200){
                $item['msg'].=$_buf;
            }else{
                $item['error'].=$_buf;
            }
        }
        @fclose($fp);

        //失败类型并还原
        if(1==$item['type']) parse_str($item['msg'],$item['msg']);

        if($this->_client->_cfg['debug']==1){
            return $item;
        }else{
            return $item['status']==200 ? $item['msg'] : null;
        }
    }
}

?>