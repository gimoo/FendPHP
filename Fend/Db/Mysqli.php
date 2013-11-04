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
 * @version $Id: Mysqli.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Db_Mysqli extends Fend
{
    protected $_db=null;//连接池标识
    protected $_cfg=null;//连接配置信息
    public $dbError=false;//是否开启异常抛出

    /**
     * 连接数据库并储存标识,实现多库连接并切换
     *
     * @param integer $r 连接标识随 $_dbcfg配置中的Key变化而变化
    **/
    public function getConn($r)
    {
        $this->_cfg=&$this->dbcfg[$r];
        !isset($this->_cfg['port']) && $this->_cfg['port']=3306;
        $this->_db=new mysqli($this->_cfg['host'],$this->_cfg['user'],$this->_cfg['pwd'],$this->_cfg['name'],$this->_cfg['port']);

        if (mysqli_connect_errno()) {
            self::showMsg("Connect failed: DBA\n");
        }

        $this->_db->query("SET character_set_connection={$this->_cfg['lang']},character_set_results={$this->_cfg['lang']},character_set_client=binary,sql_mode='';");
    }

    /**
     * 选中并打开数据库
     *
     * @param string $name 重新选择数据库,为空时选择默认数据库
    **/
    public function useDb($name=null)
    {
        null===$name && $name=$this->_cfg['name'];
        $this->_db->select_db($name) or self::showMsg("Can't use foo");
    }

    /**
     * 获取记录集合,当记录行为一个字段时输出变量结果 当记录行为多个字段时输出一维数组结果变量
     *
     * @param  string  $sql 标准查询SQL语句
     * @param  integer $r   是否合并数组
     * @return string|array
    **/
    public function get($sql,$r=null)
    {
        $rs=self::fetch(self::query($sql,$r));
        null!==$r AND $rs=join(',',$rs);
        return $rs;
    }

    /**
     * 返回查询记录的数组结果集
     *
     * @param  string  $sql 标准SQL语句
     * @return array
    **/
    public function getall($sql)
    {
        $item=array();
        $q=self::query($sql);
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
        return $this->_db->insert_id;
    }

    /**
     * 发送查询
     *
     * @param  string  $sql 标准SQL语句
     * @param  integer $r   连接标识
     * @return resource
    **/
    public function query($sql)
    {
        if(empty($this->cfg['debug'])){
            $q=$this->_db->query($sql) or self::showMsg("Query to [{$sql}] ");
        }else{
            $stime = $etime = 0;
            $m = explode(' ', microtime());
            $stime = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $q=$this->_db->query($sql) or self::showMsg("Query to [{$sql}] ");

            $m = explode(' ', microtime());
            $etime = number_format(($m[1] + $m[0] - $_SERVER['REQUEST_TIME']), 8) * 1000;
            $sqltime = round(($etime - $stime), 5);

            $explain = array();
            $info = $this->_db->info;
            if($q && preg_match("/^(select )/i", $sql)) {
                $qs=$this->_db->query('EXPLAIN '.$sql);
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
        return $q->fetch_assoc();
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param  string $str 待处理的字符串
     * @return string
    **/
    public function escape($str)
    {
        return $this->_db->real_escape_string($str);
    }

    /**
     * 关闭当前数据库连接
     * 注意: 被设置lock锁定是,跳过关闭
     *
     * @return bool
    **/
    public function close()
    {
        return $this->_db->close();
    }

    /**
     * 取得数据库中所有表名称
     *
     * @param  string $db 数据库名,默认为当前数据库
     * @return array
    **/
    public function getTB($db=null)
    {
        $item=array();
        $q=self::query('SHOW TABLES '.(null==$db ? null : 'FROM '.$db));
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

        $this->_cfg['lang']!='utf-8' && $temTable_sql=iconv($this->_cfg['lang'],'utf-8',$temTable_sql);

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
    public function fetchs($q)
    {
        return $q->fetch_row();
    }

    /**
     * 取得结果集中行的数目
     *
     * @param  resource $query 资源标识指针
     * @return array
    **/
    public function reRows($q)
    {
        return $q->num_rows;
    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
    **/
    public function afrows()
    {
        return $this->_db->affected_rows;
    }

    /**
     * 释放结果集缓存
     *
     * @param  resource $query 资源标识指针
     * @return boolean
    **/
    public function refree($q)
    {
        return $q->free_result();
    }

    /**
     * 设置异常消息 可以通过try块中捕捉该消息
     *
     * @param  resource $query 资源标识指针
     * @return boolean
    **/
    public function showMsg($str)
    {
        if($this->dbError) throw new Fend_Exception($str.$this->_db->error);
    }
}
?>