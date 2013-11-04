<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://Fend.Gimoo.Net)
 *
 * 检测并解析URL
 * 分解URL地址获取相关详细信息
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
 * @version $Id: fend.dourl.php 4 2011-12-29 11:01:08Z gimoo $
**/

function doUrl($url,$type=null)
{
    $url=strtolower($url);
    $tem=array('http'=>'',//HTTP协议类型
        'url'=>'',//输入的全部URL串
        'turl'=>'',//顶级URL
        'uri'=>'',//URI部分
        'domain'=>'',//输入的完整域名
        'tdomain'=>'',//顶级域名
        );
    $netKo=array('cn','com','org','gov','mobi','me','net','info','name','biz','cc','tv','asia','hk');
    if(preg_match('/^(http:\/\/|https:\/\/)?([a-z0-9\-_\.]+)(.*)/i',$url,$url)){
        list($url,$http,$domain, $uri) = $url;
        //过滤非法输入
        $domain{0}=='.' && $domain=substr($domain,1,strlen($domain));
        empty($uri) && $uri='/';

        //检测域名是否正确
        $domain=preg_replace('/[\.]{1,}/','.',$domain);//去掉非法输入
        $tdomain=explode('.',$domain);
        if(count($tdomain)>1){
            $tm=array();
            for($i=count($tdomain)-1;$i>=0;$i--){
                array_unshift($tm,$tdomain[$i]);
                if(!in_array($tdomain[$i],$netKo)){break;}
            }
            if(count($tm)>1){//进行赋值
                $tem['http']=empty($http) ? 'http://' : $http;
                $tem['turl']=$tem['http'].$domain;
                $tem['url']=$tem['turl'].$uri;
                $tem['uri']=$uri;
                $tem['domain']=$domain;
                $tem['tdomain']=join('.',$tm);
            }
        }
    }
    return isset($tem[$type]) ? $tem[$type] : $tem;
}

?>