<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 实例说明
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Example.php 4 2011-12-29 11:01:08Z gimoo $
**/

/**
 * 路由层 router.php
 * 需要载入:
 * 全局配置文件 config.php
 * Fend类库 Fend.php
**/
require_once('config.php');//基本配置
require_once(FD_ROOT.'Fend.php');//Fend框架
class Router extends Fend
{
    /**
     * 处理模板并回显
     * 继承Fend::showView方法
     *
     * @param  string $tplVar 模板标识
     * @param  string $tplDir 模板目录
     * @param  string $tplPre 模板后缀
    **/
    public function showView($tplVar,$tplPre='.tpl')
    {
        //传递一个变量到Smarty模版内部
        parent::regVar($this->dm,'dm');
        $tplVar=empty($this->uri[1]) ? $tplVar : $this->uri[1].'/'.$tplVar;
        parent::showView($tplVar.$tplPre);
    }

    /**
     * 发送提示信息
     * $aUrl 为空直接返回 为1返回根模块 非空返回到指定模块
     *
     * @param  string $txt  提示信息
     * @param  string $aUrl 为空直接返回 为1返回根模块 非空返回到指定模块
    **/
    public function gMsg($txt=null,$aUrl=null)
    {
        die($txt);
        if(!$aUrl){//为空直接返回
            $aUrl=@$_SERVER['HTTP_REFERER'];
        }elseif($aUrl==2){//不做跳转
            $aUrl=null;
        }elseif($aUrl==1){//为1返回根模块
            $aUrl="?gcms=".doget('gcms');
        }else{//非空返回到指定模块
            $aUrl=str_replace('{#gcms}','gcms='.$this->gcms,$aUrl);
            $aUrl="{$aUrl}";
        }
        $tmy['txt']=&$txt;
        $tmy['url']=&$aUrl;
        self::regVar($tmy);
        parent::showView($this->cfg['sys_webtpl'].'index_msg.tpl');exit;
    }


    /**
     * 载入语言包-解析并送出异常
     * 配合Smarty进行中文分离
     *
     * @param string $str 标识
     * @return void
    **/
    public function gLang($str,$reg=null)
    {
        self::regVar($str,'FDmsg');
        is_array($reg) && self::regVar($reg,'FDreg');
    }
}

/**
 * 入口文件
 * 异常处理中可以自定制异常输出
**/
try{
    require_once('../router.php');
    Fend_Acl::Factory()->setAcl($aclcfg);
    isset($aclmod) && Fend_Acl::Factory()->setModule($aclmod);
    Fend_Acl::Factory()->run($mods);

}catch(Fend_Exception $e){
    $e->ShowTry(defined('FD_DEBUG') ? FD_DEBUG : 0);
}

?>