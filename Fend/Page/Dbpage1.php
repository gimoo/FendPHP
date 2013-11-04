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
 * @version $Id: Dbpage1.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Page_Dbpage1 extends Fend
{
    public $psum=0;//总页数
    public $psize=20;//每页记录数
    public $total=0;//总记录
    public $query=null;//查询SQL指针
    public $sql=null;//查询SQL语句
    public $preg='[*]';//替换母体

    /**
     * 获取分页连接
     * @param integer $_url   URL转化地址
     * @param string  $_cpage 当前页号
     * @param integer $_pin   缩进基数
     * @return string
    **/
    public function getPage($_url,$_cpage,$_pin=10)
    {
        !empty($this->sql) && $this->getSum($this->sql,$this->total);//查询总条目
        $this->psum=$this->total<=$this->psize ? 1 : ceil($this->total/$this->psize);//总页数
        $_cpage=$_cpage<=1 ? 1 : min($this->psum,$_cpage);//当前页号

        //总页数
        $_psum=&$this->psum;
        $_cpage>$_psum && $_cpage=$_psum;//当前页数最大不能超过总页数
        $_cpage<=0 && $_cpage=1;

        //得到查询SQL
        if(!empty($this->sql)){
            $this->sql=$this->sql.' LIMIT '.(($_cpage-1)*$this->psize).','.$this->psize;
            $this->query=$this->db->query($this->sql);
        }else{
            $this->sql=' LIMIT '.(($_cpage-1)*$this->psize).','.$this->psize;
        }

        $stem=null;
        if($_psum<=1) return $stem;//分页总数小于分页基数

        //分布显示方式
        $txpg=empty($this->cfg['sys_pgtx'])?'&lt;&lt;':$this->cfg['sys_pgtx'];
        $nxpg=empty($this->cfg['sys_pgnx'])?'&gt;&gt;':$this->cfg['sys_pgnx'];

        if($_psum<=($_pin+2)){ //不存在缩进-直接显示所有分页
            $stem.=' <a'.(($_cpage-1)>0 ? ' href="'.str_replace($this->preg,$_cpage-1,$_url).'"' : '').">{$txpg}</a>";
            for($i=1;$i<=$_psum;$i++){
                $stem.=($i==$_cpage) ? " <b>{$i}</b>" : ' <a href="'.str_replace($this->preg,$i,$_url)."\">{$i}</a>" ;
            }
            $stem.=' <a'.(($_cpage+1)<=$_psum ? ' href="'.str_replace($this->preg,$_cpage+1,$_url).'"' : '').">{$nxpg}</a>";

        }elseif($_cpage<=($_pin-2)){//尾部缩进
            $stem.=' <a'.(($_cpage-1)>0 ? ' href="'.str_replace($this->preg,$_cpage-1,$_url).'"' : '').">{$txpg}</a>";
            for($i=1;$i<=$_pin;$i++){
                $stem.=($i==$_cpage) ? ' <b>'.$i.'</b>' : ' <a href="'.str_replace($this->preg,$i,$_url).'" >'.$i.'</a>';
            }
            $stem.=' <span>...</span>';
            $stem.=' <a href="'.str_replace($this->preg,$_psum-1,$_url).'" >'.($_psum-1).'</a>';
            $stem.=' <a href="'.str_replace($this->preg,$_psum,$_url).'" >'.$_psum.'</a>';
            $stem.=' <a'.(($_cpage+1)<=$_psum ? ' href="'.str_replace($this->preg,$_cpage+1,$_url).'"' : '').">{$nxpg}</a>";

        }elseif($_cpage>2 && $_cpage<($_psum-$_pin+3)){//首尾双向缩进
            $stem.=' <a'.(($_cpage-1)>0 ? ' href="'.str_replace($this->preg,$_cpage-1,$_url).'"' : '').">{$txpg}</a>";
            $stem.=' <a href="'.str_replace($this->preg,1,$_url).'" >1</a>';
            $stem.=' <a href="'.str_replace($this->preg,2,$_url).'" >2</a>';
            $stem.=' <span>...</span>';
            for($i=($_cpage-(ceil($_pin/2)-1));$i<=($_cpage+(ceil($_pin/2)-2));$i++){
                $stem.=($i==$_cpage) ? ' <b>'.$i.'</b>' : ' <a href="'.str_replace($this->preg,$i,$_url).'" >'.$i.'</a>';
            }
            $stem.=' <span>...</span>';
            $stem.=' <a href="'.str_replace($this->preg,$_psum-1,$_url).'" >'.($_psum-1).'</a>';
            $stem.=' <a href="'.str_replace($this->preg,$_psum,$_url).'" >'.$_psum.'</a>';
            $stem.=' <a'.(($_cpage+1)<=$_psum ? ' href="'.str_replace($this->preg,$_cpage+1,$_url).'"' : '').">{$nxpg}</a>";

        }else{//首部缩进
            $stem.=' <a'.(($_cpage-1)>0 ? ' href="'.str_replace($this->preg,$_cpage-1,$_url).'"' : '').">{$txpg}</a>";
            $stem.=' <a href="'.str_replace($this->preg,1,$_url).'" >1</a>';
            $stem.=' <a href="'.str_replace($this->preg,2,$_url).'" >2</a>';
            $stem.=' <span>...</span>';
            for($i=($_psum-$_pin+1);$i<=$_psum;$i++){
                $stem.=($i==$_cpage) ? ' <b>'.$i.'</b>' : ' <a href="'.str_replace($this->preg,$i,$_url).'" >'.$i.'</a>';
            }
            $stem.=' <a'.(($_cpage+1)<=$_psum ? ' href="'.str_replace($this->preg,$_cpage+1,$_url).'"' : '').">{$nxpg}</a>";

        }
        return $stem;
    }

    /**
     * 取得总数
     * @param string $sql 标准查询SQL语句
     * @return string
    **/
    private function getSum($sql,&$total)
    {
        $tsum=0;
        $coun=$this->db->query(substr_replace($sql,'SELECT COUNT(*)',0,stripos($sql,' from ')));
        while($rs=$this->db->fetchs($coun)) $tsum+=$rs[0];
        $total=$total>0 ? min($total,$tsum) : $tsum;
    }

}

?>