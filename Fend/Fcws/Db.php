<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2010 Gimoo Inc. (http://fend.gimoo.net)
 *
 * 词库查询遍历对象
 * 负责查询和编辑FendDB(FDB)库数据
 *
 * ------------------------
 *  写入数据库:
 *  $fp=new FDB;
 *  $fp->open('dict.xdb',1);
 *  $fp->put('那里','0');
 *  $fp->put('这里','0');
 * ------------------------
 *  调用查询:
 *  $fp=new FDB;
 *  $fp->open('dict.xdb');
 *  $buf=$fp->get('壁纸图库');
 *  //D:\soft_info\php5.24\php.exe make_xdb_file.php t.xdb db.txt
 * ------------------------
 *
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Db.php 4 2011-12-29 11:01:08Z gimoo $
**/

define ('FDB_KEY_MAXLEN', 0xf0);//键的最大长度
define ('FDB_INDEX_HASH_BASE', 0xf422f);//索引运算基数-Hash数据计算的基数, 建议使用默认值. [h = ((h << 5) + h) ^ c]
define ('FDB_INDEX_HASH_PRIME', 2047);//索引运算质数-求模的基数, 建议选一个质数大约为总记录数的1/10即可.
define ('FDB_INDEX_LEN', 40);//文件头长度
define ('FDB_TAGNAME', 'FDB');//文件的所属
define ('FDB_VERSION', '1.0' );//程序版本
define ('FDB_FLOAT_CHECK', 3.14);
//define ('FDB_SYSLEN', 32);//32位系统

class Fend_Fcws_Db
{
    private $_fp=false;//数据库指针
    private $_fmode=0;//连接数据库方式,0为只读,1为只写
    private $_hbase=FDB_INDEX_HASH_BASE;
    private $_hprime=FDB_INDEX_HASH_PRIME;
    private $_version=34;//词典版本
    protected $_fsize=0;//词典文件大小(最大字节数)
    protected $_keymin=FDB_KEY_MAXLEN;//词典文件中建的最小长度(因文件而异)
    protected $_keymax=0;//词典文件中建的最da长度(因文件而异)

    /**
     * 打开数据库
     *
     * @param  string $fpath 文件物理路径
     * @param  string $wp 打开方式,0为只读方式,1为写方式
     * @return void
    **/
    public function open($fpath,$fm=0)
    {
        if($fm){
            $fp = @fopen($fpath, 'wb+');
            //写入文件头
            fseek($fp, 0, SEEK_SET);
            fwrite($fp, pack('a3CiiIIIfa12', FDB_TAGNAME, $this->_version,$this->_hbase, $this->_hprime, 0, $this->_keymin, $this->_keymax,  FDB_FLOAT_CHECK, ''), FDB_INDEX_LEN);
            $this->_fsize = FDB_INDEX_LEN + 8 * $this->_hprime;
            flock($fp, LOCK_EX);
        }else{
            $fp = @fopen($fpath, 'rb');
        }

        //检测文件是否为FDB文件
        if(!$fm && !$this->_getHeader($fp)){
            fclose($fp);
            trigger_error("FDB::open(".basename($fpath)."), invalid FDB format.", E_USER_WARNING);
            return false;
        }
        $this->_fp=&$fp;
        $this->_fmode=&$fm;
    }

    /**
     * 读取KEY的值
     * 并进行检测返回true or false
     *
     * @param  string $key 一个字符串
     * @return string
    **/
    public function get($key)
    {
        //检测key是否合法
        $_len=strlen($key);
        if($_len==0 || $_len>FDB_KEY_MAXLEN) return false;
        $rs=$this->_get($key,$_len);
        if(!isset($rs['vlen']) || $rs['vlen'] == 0) return false;
        return $rs['value'];
    }

    /**
     * 遍历数据库
     * 多次调用可以列表所有记录集
     *
     * @param  string $key 一个字符串
     * @return bool   0|1
    **/
    public function next()
    {
        static $_stack=array(),$_index=-1;
        if(!($_tmp=array_pop($_stack))){
            do{
                if(++$_index >= $this->_hprime) break;

                fseek($this->_fp,$_index * 8 + FDB_INDEX_LEN,SEEK_SET);
                $buf=fread($this->_fp, 8);
                if(strlen($buf)!= 8){$_tmp=false;break;}

                $_tmp=unpack('Ioff/Ilen',$buf);
            }while($_tmp['len']==0);
        }

        //检测是否读取已结束
        if(!$_tmp || $_tmp['len']==0) return false;

        //读取记录集
        $rs=$this->_getTree($_tmp['off'],$_tmp['len']);

        //检测是否具有左节点
        if($rs['llen'] != 0){
            array_push($_stack,array('off'=>$rs['loff'],'len'=>$rs['llen']));
        }

        //检测是否具有右节点
        if($rs['rlen'] != 0){
            array_push($_stack,array('off'=>$rs['roff'],'len'=>$rs['rlen']));
        }

        //结果集合: WORD\tTF\tIDF\tATTR\n
        //$rs['value']=unpack('ftf/fidf/Cflag/a3attr',$rs['value']);
        return $rs;
    }

    /**
     * 写入词库信息
     * 往词库中写入词信息
     *
     * @param  string $key   一个字符串
     * @param  string $value 对应的值
     * @return bool   0|1
    **/
    public function put($key, $value)
    {
        //检测是否有可写入环境
        if(!$this->_fp || !$this->_fmode){
            trigger_error("FDB::put(), null db handler or readonly.", E_USER_WARNING); return false;
        }

        //验证数据是否合法
        $klen=strlen($key);
        $vlen=strlen($value);
        if(!$klen || $klen > FDB_KEY_MAXLEN) return false;

        $klen<$this->_keymin && $this->_keymin=$klen;
        $klen>$this->_keymax && $this->_keymax=$klen;

        //检测数据是否存在
        $rs=$this->_get($key,$klen);
        if(isset($rs['vlen']) && ($vlen <= $rs['vlen'])){

            if($vlen > 0){//更新记录集
                fseek($this->_fp, $rs['voff'], SEEK_SET);
                fwrite($this->_fp, $value, $vlen);
            }

            if ($vlen < $rs['vlen']){
                $newlen = $rs['len'] + $vlen - $rs['vlen'];
                fseek($this->_fp, $rs['ioff'] + 4, SEEK_SET);
                fwrite($this->_fp, pack('I', $newlen), 4);
            }
            return true;
        }

        //构造数据结构
        $new = array('loff' => 0, 'llen' => 0, 'roff' => 0, 'rlen' => 0);
        if(isset($rs['vlen'])){
            $new['loff'] = $rs['loff'];
            $new['llen'] = $rs['llen'];
            $new['roff'] = $rs['roff'];
            $new['rlen'] = $rs['rlen'];
        }
        $buf=pack('IIIIC', $new['loff'], $new['llen'], $new['roff'], $new['rlen'], $klen).$key.$value;
        $len=$klen + $vlen + 17;

        //写入数据块
        $off=$this->_fsize;
        fseek($this->_fp, $off, SEEK_SET);
        fwrite($this->_fp, $buf, $len);
        $this->_fsize += $len;

        //更新索引
        fseek($this->_fp, $rs['ioff'], SEEK_SET);
        fwrite($this->_fp, pack('II', $off, $len), 8);
        return true;
    }

    /**
     * 整理优化库结构
     * 重写头信息,保证文件的正确性
     *
     * @return void
    **/
    public function optimize()
    {
        if(!$this->_fp || !$this->_fmode) return false;
        static $_cmpfunc=false;

        //获取索引区域块
        $i=-1;
        if($i<0 || $i>=$this->_hprime){
            $i=0;$j=$this->_hprime;
        }else{
            $j=$i+1;
        }

        //重建索引
        while($i<$j){
            $ioff=$i++ * 8 + FDB_INDEX_LEN;

            //取得所有索引关节位置
            $_syncTree=array();
            $this->_loadTree($ioff,$_syncTree);
            $count=count($_syncTree);
            if($count < 3) continue;

            if($_cmpfunc == false) $_cmpfunc = create_function('$a,$b', 'return strcmp(@$a[key],@$b[key]);');
            usort($_syncTree, $_cmpfunc);
            $this->_resetTree($_syncTree,$ioff, 0, $count - 1);
            unset($_syncTree);
        }
        fseek($this->_fp,12,SEEK_SET);
        fwrite($this->_fp, pack('III',$this->_fsize,$this->_keymin,$this->_keymax), 12);
        flock($this->_fp,LOCK_UN);
    }

    /**
     * 关闭数据库连接
     * PHP5以上版本通常由__destruct自动调用,无需自己调用
     *
     * @return void
    **/
    public function close()
    {
        if(!$this->_fp) return;
        fclose($this->_fp);
        $this->_fp=false;
    }

    /**
     * 读取版本信息
     *
     * @return string
    **/
    public function version()
    {
        return sprintf("%s/%d.%d", FDB_VERSION, ($this->_version >> 5), ($this->_version & 0x1f));
    }

    /**
     * 根据KEY取得相关value
     *
     * @param  string $key 一个字符串
     * @return string
    **/
    private function _get(&$key,&$len)
    {
        $ioff=($this->_hprime > 1 ? $this->_getIndex($key,$len) : 0) * 8 + FDB_INDEX_LEN;
        fseek($this->_fp, $ioff, SEEK_SET);
        $buf=fread($this->_fp, 8);

        if(strlen($buf)==8) $_tmp=unpack('Ioff/Ilen',$buf);
        else $_tmp=array('off'=>0,'len'=>0);
        return $this->_getTree($_tmp['off'], $_tmp['len'], $ioff, $key);
    }

    /**
     * 根据KEY取得相关value
     *
     * @param  string $key 一个字符串
     * @return string
    **/
    private function _getIndex(&$key,$l)
    {
        $h=$this->_hbase;
        while ($l--){
            $h += ($h << 5);
            $h ^= ord($key[$l]);
            $h &= 0x7fffffff;
        }
        return ($h % $this->_hprime);
    }

    /**
     * 读取文件头信息
     *
     * @param  string $key 一个字符串
     * @return string
    **/
    private function _getHeader(&$fp)
    {
        fseek($fp, 0, SEEK_SET);
        $buf=fread($fp, FDB_INDEX_LEN);
        if(strlen($buf) !== FDB_INDEX_LEN) return false;
        $rs=unpack('a3tag/Cver/Ibase/Iprime/Ifsize/Ikeymin/Ikeymax/fcheck/a12reversed', $buf);
        if($rs['tag'] != FDB_TAGNAME) return false;

        //读取文件信息,检测文件是否损坏
        $fs=fstat($fp);
        if($fs['size'] != $rs['fsize']) return false;
        $this->_hbase = $rs['base'];
        $this->_hprime = $rs['prime'];
        $this->_version = $rs['ver'];
        $this->_fsize = $rs['fsize'];
        $this->_keymax = $rs['keymax'];
        $this->_keymin = $rs['keymin'];
        return true;
    }

    /**
     * 递归的读取记录集合
     *
     * @param  int $off  起始位置
     * @param  int $len  可读取的长度
     * @param  int $ioff 索引起始位置
     * @param  string $key  键名称
     * @return array
    **/
    private function _getTree(&$off,&$len,$ioff=0,$key=null)
    {
        if($len==0) return array('ioff'=>$ioff);

        //读取记录集
        fseek($this->_fp,$off,SEEK_SET);
        $rlen = FDB_KEY_MAXLEN + 17; $rlen>$len && $rlen=$len;

        $buf = fread($this->_fp, $rlen);
        $rs = unpack('Iloff/Illen/Iroff/Irlen/Cklen', substr($buf, 0, 17));

        //校验key是否读取完整
        $_key = substr($buf, 17, $rs['klen']);
        $cmp = $key ? strcmp($key, $_key) : 0;

        unset($buf);
        if($cmp > 0){// --> right
            return $this->_getTree($rs['roff'], $rs['rlen'], $off + 8, $key);
        }elseif($cmp < 0){// <-- left
            return $this->_getTree($rs['loff'], $rs['llen'], $off, $key);
        }else{//返回结果集合
            $rs['ioff']=$ioff;
            $rs['off']=$off;
            $rs['len']=$len;
            $rs['voff']=$off+17+$rs['klen'];
            $rs['vlen']=$len-17-$rs['klen'];
            $rs['key']=&$_key;
            fseek($this->_fp,$rs['voff'],SEEK_SET);
            $rs['value']=fread($this->_fp,$rs['vlen']);
            //$rs['value']=unpack('fss/fcc/Ctt/a3ds',$rs['value']);
            return $rs;
        }
    }

    /**
     * 递归的读取顺序读取集合
     *
     * @param  int $ioff 起始位置
     * @return array
    **/
    private function _loadTree($ioff,&$_syncTree)
    {
        fseek($this->_fp,$ioff,SEEK_SET);
        $buf=fread($this->_fp,8);
        if(strlen($buf)!=8) return;

        $tmp=unpack('Ioff/Ilen',$buf);

        if($tmp['len']==0) return;
        fseek($this->_fp,$tmp['off'],SEEK_SET);

        $rlen = FDB_KEY_MAXLEN + 17;
        if($rlen > $tmp['len']) $rlen = $tmp['len'];
        $buf = fread($this->_fp, $rlen);

        $rec = unpack('Iloff/Illen/Iroff/Irlen/Cklen', substr($buf, 0, 17));
        $rec['off'] = $tmp['off'];
        $rec['len'] = $tmp['len'];
        $rec['key'] = substr($buf, 17, $rec['klen']);
        $_syncTree[] = $rec;
        unset($buf);

        if($rec['llen'] != 0) $this->_loadTree($tmp['off'],$_syncTree);
        if($rec['rlen'] != 0) $this->_loadTree($tmp['off'] + 8,$_syncTree);
    }

    /**
     * 重新建立起数结构
     *
     * @param  int $ioff 索引偏移量
     * @param  int $low
     * @param  int $high
     * @return void
    **/
    private function _resetTree(&$_syncTree,$ioff, $low, $high)
    {
        if($low<=$high){
            $mid=($low+$high)>>1;
            $node=$_syncTree[$mid];
            $buf=pack('II',$node['off'],$node['len']);

            $this->_resetTree($_syncTree,$node['off'], $low, $mid - 1);
            $this->_resetTree($_syncTree,$node['off'] + 8, $mid + 1, $high);
        }else{
            $buf=pack('II', 0, 0);
        }
        fseek($this->_fp, $ioff, SEEK_SET);
        fwrite($this->_fp, $buf, 8);
    }

    /**
     * 自动关闭及销毁变量
     *
     * @return void
    **/
    public function __destruct()
    {
        $this->optimize();//自动优化
        $this->close();
    }
}
?>