<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 调试器
 * 生成并处理系统相关Debug信息
 *
 * 如MYSQL Fend ACL等等
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Debug.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Debug extends Fend
{
    static public $in=null;//保存当前对象

    /**
     * 工厂模式 静态激活对象
     *
     * @return object
    **/
    public static function factory()
    {
        if(!isset(self::$in)){
            self::$in=new self;
        }
        return self::$in;
    }

    /**
     * 格式化并生成Debug
     *
    **/
    public function dump()
    {
        //样式配置
        $item="<style>
                div.FD_bug {margin:40px auto;width:98%;}
                div.FD_bug td,div.FD_bug th{font-family: Courier New, Arial;font-size:11px;}
                div.FD_bug table{text-align:left;width:100%;border:0;border-collapse:collapse;table-layout:fixed;word-wrap: break-word; background:#FFF;}
                div.FD_bug table.FD_table{margin-bottom:5px;}
                div.FD_bug table.FD_table th{border:1px solid #000;background:#CCC;padding: 2px;}
                div.FD_bug table.FD_table td {border:1px solid #000;background:#FFFCCC;padding: 2px;}
                div.FD_bug tr.bg th {background:#D5EAEA;}
                div.FD_bug tr.bg td {background:#FFFFFF;}
                div.FD_bug table.FD_nt1 th{background:#FFFFFF;border-width:0px 1px 1px 0 ;}
                div.FD_bug table.FD_nt1 td{border-width:0px 1px 0px 0;color:#666;}
                div.FD_bug tr.FD_sql td{border-bottom:1px solid #CCC;}
              </style><div class='FD_bug'>";

        //DB
        if(isset($this->DB_debug) && count($this->DB_debug)>0){
            $k=0;
            $item.="<table class='FD_table'>";
            $item.="<tr class='bg'><th width='65'>OutType:</th><td>DateBase Info</td></tr>";
            settype($this->DB_debug,'array');
            foreach($this->DB_debug as $v){
                $item.="<tr><th>#".++$k.":</th><td>";
                $item.="<table class='FD_nt1'>";
                $item.="<tr class='bg'><th width='100'>{$v['time']} ms</th><th>{$v['sql']}</th></tr>";
                if(empty($v['explain'])){
                    $item.="<tr class='bg'><td>info</td><td>{$v['info']}</td></tr>";
                }else{
                    $item.="<tr class='bg'><td>explain</td><td>";
                    $item.="<table>";
                    $item.="<tr><th>id</th><th>select_type</th><th>table</th><th>type</th><th>possible_keys</th><th>key</th><th>key_len</th><th>ref</th><th>rows</th><th>Extra</th></tr>";
                    foreach($v['explain'] as $exp){
                        $item.="<tr class='FD_sql'><td>{$exp['id']}</td><td>{$exp['select_type']}</td><td>{$exp['table']}</td><td>{$exp['type']}</td><td>{$exp['possible_keys']}</td><td>{$exp['key']}</td><td>{$exp['key_len']}</td><td>{$exp['ref']}</td><td>{$exp['rows']}</td><td>{$exp['Extra']}</td></tr>";
                    }
                    $item.="</table>";
                    $item.="</td></tr>";
                }
                $item.="</table>";
                $item.="</td></tr>";
            }
            $item.="</table>";
        }

        //Cookie
        if(isset($_COOKIE) && count($_COOKIE)>0){
            $k=0;
            $item.="<table class='FD_table'>";
            $item.="<tr class='bg'><th width='65'>OutType:</th><td>Cookie Info</td></tr>";
            foreach ($_COOKIE as $key=>$v) {
                $item.="<tr><th>#".++$k.":</th><td>\$_COOKIE['{$key}'] = {$v}</td></tr>";
            }
            $item.="</table>";
        }

        //Session
        if(isset($_SESSION) && count($_SESSION)>0){
            $k=0;
            $item.="<table class='FD_table'>";
            $item.="<tr class='bg'><th width='65'>OutType:</th><td>SESSION Info</td></tr>";
            foreach ($_SESSION as $key=>$v) {
                $item.="<tr><th>#".++$k.":</th><td>\$_SESSION['{$key}'] = {$v}</td></tr>";
            }
            $item.="</table>";
        }


        //当前被载入的文件
        $item.="<table class='FD_table'>";
        $item.="<tr class='bg'><th width='65'>OutType:</th><td>Include Files</td></tr>";
        $ifile = get_included_files();
        foreach ($ifile as $k=>&$v) {
            $item.="<tr><th>#".++$k.":</th><td>{$v}</td></tr>";
        }
        $item.="</table>";

        //SERVER 系统信息
        $k=0;
        $item.="<table class='FD_table'>";
        $item.="<tr class='bg'><th width='65'>OutType:</th><td>SERVER Info</td></tr>";
        unset($_SERVER['HTTP_COOKIE']);
        foreach ($_SERVER as $key=>$v) {
            $item.="<tr><th>#".++$k.":</th><td>\$_SERVER['{$key}'] = {$v}</td></tr>";
        }
        $item.="</table>";

        $item.='</div>';
        echo ($item);
    }
}
?>