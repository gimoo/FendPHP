<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * Mysql DB对象
 * 配置格式: $_dbcfg[0]=array('dbhost'=>'localhost','dbname'=>'fend','dbuser'=>'fend','dbpwd'=>'fend','lang'=>'GBK');
 * 对象必须符合模板规则: class Fend_Db_Mysql1 implements Fend_Db_Base
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Mysql.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Db_Mysql extends Fend
{
    public $dbLink=array();//连接指针
    public $dbR=0;//当前连接标识
    public $dbLang='gbk';//设置默认数据库编码
    public $dbError=false;//是否开启错误抛出

    /**
     * 连接数据库并储存标识,实现多库连接并切换
     *
     * @param integer $r 连接标识随 $_dbcfg配置中的Key变化而变化
    **/
    public function getConn($r)
    {
        $this->dbR=$r;//设置当前连接
        if(!isset($this->dbLink[$r])){
            $this->dbLink[$r]=@mysql_connect($this->dbcfg[$r]['dbhost'],$this->dbcfg[$r]['dbuser'],$this->dbcfg[$r]['dbpwd']) or
                              self::showMsg('Your Connection Failure ');

            !empty($this->dbcfg[$r]['lang']) && $this->dbLang=$this->dbcfg[$r]['lang'];
            self::query("SET character_set_connection={$this->dbLang},character_set_results={$this->dbLang},character_set_client=binary,sql_mode='';");
        }
        $this->useDb();
    }

    /**
     * 选中并打开数据库
     *
     * @param string $dbname 需要打开的数据库
    **/
    public function useDb($dbname=null)
    {
        !$dbname && $dbname=$this->dbcfg[$this->dbR]['dbname'];
        mysql_select_db($dbname,$this->dbLink[$this->dbR]) or self::showMsg("Can't use foo ");
    }

    /**
     * 获取记录集合,当记录行为一个字段时输出变量结果 当记录行为多个字段时输出一维数组结果变量
     *
     * @param  string  $sql 标准查询SQL语句
     * @param  integer $r   连接标识
     * @return string|array
    **/
    public function get($sql,$r=null)
    {
        $rs=self::fetch(self::query($sql,$r));
        $rs && count($rs)==1 && $rs=join(',',$rs);
        return $rs;
    }

    /**
     * 返回查询记录的数组结果集
     *
     * @param  string  $sql 标准SQL语句
     * @param  integer $r 连接标识
     * @return array
    **/
    public function getall($sql,$r=null)
    {
        $item=array();
        $q=self::query($sql,$r);
        while($rs=self::fetch($q)) $item[]=$rs;
        return $item;
    }

    /**
     * 获取插入的自增ID
     *
     * @return integer
    **/
    public function getId()
    {
        return mysql_insert_id();
    }

    /**
     * 发送查询
     *
     * @param  string  $sql 标准SQL语句
     * @param  integer $r   连接标识
     * @return resource
    **/
    public function query($sql,$r=null)
    {
        $r=isset($r) ? $r : $this->dbR;
        if(empty($this->cfg['debug'])){
            $q=mysql_query($sql,$this->dbLink[$r]) or self::showMsg("Query to [{$sql}] ");
        }else{
            $stime = $etime = 0;
            $m = explode(' ', microtime());
            $stime = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $q=mysql_query($sql,$this->dbLink[$r]) or self::showMsg("Query to [{$sql}] ");

            $m = explode(' ', microtime());
            $etime = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $sqltime = round(($etime - $stime), 5);

            $explain = array();
            $info = mysql_info();
            if($q && preg_match("/^(select )/i", $sql)) {
                $qs=mysql_query('EXPLAIN '.$sql, $this->dbLink[$r]);
                while($rs=self::fetch($qs)){
                    $explain[] = $rs;
                }
            }
            $this->DB_debug[] = array('sql'=>$sql, 'time'=>$sqltime, 'info'=>$info, 'explain'=>$explain);


        }
        return $q;
    }

    /**
     * 返回字段名为索引的数组集合
     *
     * @param  results $q 查询指针
     * @return array
    **/
    public function fetch($q)
    {
        return mysql_fetch_assoc($q);
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param  string $str 待处理的字符串
     * @return string
    **/
    public function escape($str)
    {
        return mysql_escape_string($str);
    }

    /**
     * 关闭当前数据库连接
     * 注意: 被设置lock锁定是,跳过关闭
     *
     * @param  string $str 待处理的字符串
     * @return string
    **/
    public function close()
    {
        if(empty($this->dbLink[$this->dbR]['lock'])){
            mysql_close($this->dbLink[$this->dbR]);
            unset($this->dbLink[$this->dbR]);
            $this->dbR=(int)key($this->dbLink);
        }
    }

    /**
     * 取得数据库中所有表名称
     *
     * @param  string $db 数据库名,默认为当前数据库
     * @return array
    **/
    public function getTB($db=NULL)
    {
        $item=array();
        $q=self::query('SHOW TABLES '.(empty($db) ? null : 'FROM '.$db));
        while($rs=self::fetchs($q)) $item[]=$rs[0];
        return $item;
    }

    /**
     * 根据已知的表复制一张新表,如有自增ID时自增ID重置为零
     * 注意: 仅复制表结构包括索引配置,而不复制记录
     *
     * @param  string  $souTable 源表名
     * @param  string  $temTable 目标表名
     * @param  boolean $isdel    是否在处理前检查并删除目标表
     * @return boolean
    **/
    public function copyTB($souTable,$temTable,$isdel=false)
    {
        $isdel && self::query("DROP TABLE IF EXISTS `{$temTable}`");//如果表存在则直接删除
        $temTable_sql=self::sqlTB($souTable);
        $temTable_sql=str_replace('CREATE TABLE `'.$souTable.'`','CREATE TABLE IF NOT EXISTS `'.$temTable.'`',$temTable_sql);

        $temTable_sql=iconv($this->dbLang,'utf-8',$temTable_sql);

        $result=self::query($temTable_sql);//创建复制表
        stripos($temTable_sql,'AUTO_INCREMENT') && self::query("ALTER TABLE `{$temTable}` AUTO_INCREMENT =1");//更新复制表自增ID
        return $result;
    }

    /**
     * 获取表中所有字段及属性
     *
     * @param  string $tb 表名
     * @return array
    **/
    public function getFD($tb)
    {
        $item=array();
        $q=self::query("SHOW FULL FIELDS FROM {$tb}");//DESCRIBE users
        while($rs=self::fetch($q)) $item[]=$rs;
        return $item;
    }

    /**
     * 生成表的标准Create创建SQL语句
     *
     * @param  string $tb 表名
     * @return string
    **/
    public function sqlTB($tb)
    {
        $q=self::query("SHOW CREATE TABLE {$tb}");
        $rs=self::fetchs($q);
        return $rs[1];
    }

    /**
     * 如果表存在则删除
     *
     * @param  string $tables 表名称
     * @return boolean
    **/
    public function delTB($tables)
    {
        return self::query("DROP TABLE IF EXISTS `{$tables}`");
    }

    /**
     * 整理优化表
     * 注意: 多个表采用多个参数进行传入
     *
     * Example: setTB('table0','table1','tables2',...)
     * @param string 表名称可以是多个
     * @return boolean
    **/
    public function setTB()
    {
        $args=func_get_args();
        foreach($args as &$v) self::query("OPTIMIZE TABLE {$v};");
    }

    /**
     * 生成REPLACE|UPDATE|INSERT等标准SQL语句
     *
     * @param  string $arr    操纵数据库的数组源
     * @param  string $dbname 数据表名
     * @param  string $type   SQL类型 UPDATE|INSERT|REPLACE|IFUPDATE
     * @param  string $where  where条件
     * @return string         一个标准的SQL语句
    **/
    public function subSQL($arr,$dbname,$type='update',$where=NULL)
    {
        $tem=array();
        foreach($arr as $k=>$v) $tem[$k]="`{$k}`='{$v}'";
        switch(strtolower($type)){
            case 'insert'://插入
                $sql="INSERT INTO {$dbname} SET ".join(',',$tem);
                break;
            case 'replace'://替换
                $sql="REPLACE INTO {$dbname} SET ".join(',',$tem);
                break;
            case 'update'://更新
                $sql="UPDATE {$dbname} SET ".join(',',$tem)." WHERE {$where}";
                break;
            case 'ifupdate'://存在则更新记录
                $tem=join(',',$tem);
                $sql="INSERT INTO {$dbname} SET {$tem} ON DUPLICATE KEY UPDATE {$tem}";
                break;
            default:
                $sql=null;
                break;
        }
        return $sql;
    }

    /**
     * 生成REPLACE|UPDATE|INSERT等标准SQL语句 同subsql函数相似但该函数会直接执行不返回SQL
     *
     * @param  string $arr 操纵数据库的数组源
     * @param  string $dbname 数据表名
     * @param  string $type SQL类型 UPDATE|INSERT|REPLACE|IFUPDATE
     * @param  string $where where条件
     * @return boolean
    **/
    public function doQuery($arr,$dbname,$type='update',$where=NULL)
    {
        $sql=self::subSQL($arr,$dbname,$type,$where);
        return self::query($sql);
    }

    /**
     * 返回键名为序列的数组集合
     *
     * @param  resource $query 资源标识指针
     * @return array
    **/
    public function fetchs($query)
    {
        return mysql_fetch_row($query);
    }

    /**
     * 取得结果集中行的数目
     *
     * @param  resource $query 资源标识指针
     * @return array
    **/
    public function reRows($query)
    {
        return mysql_num_rows($query);
    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
    **/
    public function afrows($r=null)
    {
        $r=isset($r) ? $r : $this->dbR;
        return mysql_affected_rows($this->dbLink[$r]);
    }

    /**
     * 释放结果集缓存
     *
     * @param  resource $query 资源标识指针
     * @return boolean
    **/
    public function refree($query)
    {
        return @mysql_free_result($query);
    }

    /**
     * 设置异常消息 可以通过try块中捕捉该消息
     *
     * @param  resource $query 资源标识指针
     * @return boolean
    **/
    public function showMsg($str)
    {
        if($this->dbError) throw new Fend_Exception($str.mysql_error());
    }
}
?>