<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 数据库模板基类 [Interface]
 * 框架内只要通过Fend_Db静态激活的对象必须符合该对象指定的标准
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Base.php 4 2011-12-29 11:01:08Z gimoo $
**/

interface Fend_Db_Base
{
    //连接数据库并保存一个链接标识
    public function getConn($r);

    //选择并打开数据库
    public function getDb($str=NULL);

    //取得一行数据集
    public function get($sql);

    //返回查询的所有记录数组结果集
    public function getall($sql);

    //返回插入的自增ID
    public function getid();

    //发送查询
    public function query($sql,$r=null);

    //返回键名为字段名的数组集合
    public function fetch($query);

    //格式化MYSQL查询字符串
    public function escape($str);

    //关闭当前数据库连接
    public function close();

    //--结构应用--------------------

    //取得当前数据库中所有数据表名称
    public function getTB($db=NULL);

    //复制一张表 (源表,目标表,如果存在是否删除目标表1为自动删除0为跳过)
    public function copyTB($souTable,$temTable,$isdel=FALSE);

    //取得数据表的所有字段以及相关属性;
    public function getFD($table);

    //取得一个数据表的Create标准SQL
    public function sqlTB($table);

    //删除表
    public function delTB($tables);

    //优化表
    public function setTB($tables);

    //生成SELECT,REPLACE,UPDATE可查询的标准SQL语句
    public function subSQL($arr,$dbname,$type='update',$where=NULL);

    //返回键名为序列数字的数组集合
    public function fetchs($query);

    //取得 RESULT 后的结果条目
    public function rerows($query);

    //返回被INSERT、UPDATE、DELETE查询所影响的记录行数
    public function afrows();

    //释放结果集缓存
    public function refree($query);

    //送出异常
    public function showMsg($str);
}


?>