<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 分页对象
 * 适用于动态URL分页 采用当前所在行作为分页标识
 *
 * @Package GimooFend
 * @Support http://bbs.gimoo.net
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Dbpage.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Page_Dbpage extends Fend
{
    public $pagesum=0;//取得查询结果总页数
    public $psize=20;//每页记录数
    public $total=0;//查询总条数
    public $query=null;//返回查询指针
    public $sql=null;//查询语句

    /**
     * 获取分页连接
     * @param integer $psize 每页显示数
     * @param string  $pname 分页标识
     * @param integer $pin   缩进基数
     * @return string
    **/
    public function getPage($psize=20,$pname='pg',$pin=10)
    {
        !empty($this->sql) && $this->getSum($this->sql,$this->total);//查询总条目
        $psum=&$this->total;
        $psize<=0 && $psize=20;

        //得到当前分页游标-并计算当前页号
        $cpage=(int)@$_GET[$pname];
        $cpage=$cpage<=0 ? 1 : ceil($cpage/$psize);

        //总页数
        $total=ceil($psum/$psize);
        $cpage>$total && $cpage=$total;//当前页数最大不能超过总页数
        $cpage<=0 && $cpage=1;

        //得到查询SQL
        if(!empty($this->sql)){
            $this->sql=$this->sql.' LIMIT '.(($cpage-1)*$psize).','.$psize;
            $this->query=$this->db->query($this->sql);
        }else{
            $this->sql=' LIMIT '.(($cpage-1)*$psize).','.$psize;
        }

        $stem=null;
        if($total<=1 || $psum<=$psize) return $stem;//分页总数小于分页基数

        //取得URL QUERY_STRING 所有参数并解码为数组集合
        $url_param=@$_SERVER['QUERY_STRING'];
        parse_str($url_param,$url_param);

        //分布显示方式
        $txpg=empty($this->cfg['sys_pgtx'])?'&lt;&lt;':$this->cfg['sys_pgtx'];
        $nxpg=empty($this->cfg['sys_pgnx'])?'&gt;&gt;':$this->cfg['sys_pgnx'];

        if($total<=($pin+2)){ //不存在缩进-直接显示所有分页
            $stem.=' <a'.(($cpage-1)>0 ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage-1).'"' : '').">{$txpg}</a>";
            for($i=1;$i<=$total;$i++){
                $stem.=($i==$cpage) ? " <b>{$i}</b>" : ' <a href="?'.self::getParam($url_param,$pname,$psize,$i)."\">{$i}</a>" ;
            }
            $stem.=' <a'.(($cpage+1)<=$total ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage+1).'"' : '').">{$nxpg}</a>";

        }elseif($cpage<=($pin-2)){//尾部缩进
            $stem.=' <a'.(($cpage-1)>0 ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage-1).'"' : '').">{$txpg}</a>";
            for($i=1;$i<=$pin;$i++){
                $stem.=($i==$cpage) ? ' <b>'.$i.'</b>' : ' <a href="?'.self::getParam($url_param,$pname,$psize,$i).'" >'.$i.'</a>';
            }
            $stem.=' <span>...</span>';
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,$total-1).'" >'.($total-1).'</a>';
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,$total).'" >'.$total.'</a>';
            $stem.=' <a'.(($cpage+1)<=$total ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage+1).'"' : '').">{$nxpg}</a>";

        }elseif($cpage>2 && $cpage<($total-$pin+3)){//首尾双向缩进
            $stem.=' <a'.(($cpage-1)>0 ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage-1).'"' : '').">{$txpg}</a>";
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,1).'" >1</a>';
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,2).'" >2</a>';
            $stem.=' <span>...</span>';
            for($i=($cpage-(ceil($pin/2)-1));$i<=($cpage+(ceil($pin/2)-2));$i++){
                $stem.=($i==$cpage) ? ' <b>'.$i.'</b>' : ' <a href="?'.self::getParam($url_param,$pname,$psize,$i).'" >'.$i.'</a>';
            }
            $stem.=' <span>...</span>';
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,$total-1).'" >'.($total-1).'</a>';
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,$total).'" >'.$total.'</a>';
            $stem.=' <a'.(($cpage+1)<=$total ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage+1).'"' : '').">{$nxpg}</a>";

        }else{//首部缩进
            $stem.=' <a'.(($cpage-1)>0 ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage-1).'"' : '').">{$txpg}</a>";
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,1).'" >1</a>';
            $stem.=' <a href="?'.self::getParam($url_param,$pname,$psize,2).'" >2</a>';
            $stem.=' <span>...</span>';
            for($i=($total-$pin+1);$i<=$total;$i++){
                $stem.=($i==$cpage) ? ' <b>'.$i.'</b>' : ' <a href="?'.self::getParam($url_param,$pname,$psize,$i).'" >'.$i.'</a>';
            }
            $stem.=' <a'.(($cpage+1)<=$total ? ' href="?'.self::getParam($url_param,$pname,$psize,$cpage+1).'"' : '').">{$nxpg}</a>";
        }
        return $stem;
    }

    /**
     * 取得总数
     * @param string $sql 标准查询SQL语句
     * @return void
    **/
    private function getSum($sql,&$total)
    {
        $tsum=0;
        $l=stripos($this->sql,' from ');$r=strripos($sql,' order by ');
        $coun=$this->db->query('SELECT COUNT(*)'.($r<=0 ? substr($sql,$l) : substr($sql,$l,$r-$l)));
        //$coun=$this->db->query(substr_replace($sql,'SELECT COUNT(*)',0,stripos($sql,' from ')));
        while($rs=$this->db->fetchs($coun)) $tsum+=$rs[0];
        $total=$total>0 ? min($total,$tsum) : $tsum;
    }

    /**
     * 取得分页URL连接
     * @param string $url_param URLparam
     * @param string  $pname    分页标识
     * @param integer $psize    每页显示数
     * @param integer $i        连接增数
     * @return string
    **/
    private function getParam(&$url_param,&$pname,&$psize,$i)
    {
        $url_param[$pname]=$i*$psize;
        return http_build_query($url_param);
    }
}

/***************************

调用方式1
$Pg=Fend_Page_Init::Factory(1);
$Pg->sql="SELECT * FROM site_info {$sql}";
$tmy['bypage']=$Pg->getPage(20,'pg');
while($rs=$this->db->fetch($Pg->query)){
    $tmy['bylist'][]=$rs;
}
$tmy['bytotal']=$Pg->total;


调用方式2
$Pg=Fend_Page_Init::Factory(1);
$Pg->total=500;
$page=$Pg->getPage(20,'pg');
$Pg->sql;//获取LIMIT分页查询

***************************/

?>