<?php
/**
 * Fend Framework
 * [Gimoo!] (C)2006-2009 Eduu Inc. (http://www.eduu.com)
 *
 * 消息接受器
 * 守护进程模式启动
 *
 *
 * @Package GimooFend
 * @Support http://bbs.eduu.com
 * @Author  Gimoo <gimoohr@gmail.com>
 * @version $Id: Server.php 4 2011-12-29 11:01:08Z gimoo $
**/

class Fend_Pcntl_Server
{
    public $pcfg=array(
        'access_log'=>'/var/log/fdpcntl-access.log',//处理日志
        'error_log'=>'/var/log/fdpcntl-error.log',//异常日志
        'server_ip'=>'127.0.0.1',//服务器IP
        'port'=>'8031',//监听端口
        'user'=>'root',//运行用户
        'pidfile'=>'/var/run/fdpcntl.pid',//PID文件地址
        'fptype'=>'debug',//启动模式
        'fpdir'=>'',//运行的根目录
    );
    private $_pid=0;//当前服务的PID
    static $in=null;

    /**
     * 工厂模式: 激活当前对象
     *
     * @return object $in  模板对象
    **/
    public static function Factory()
    {
        if(PHP_SAPI!=='cli') die('Not CLI');//只有在CLI模式下才能运行

        if(null===self::$in){
            self::$in=new self();
            if(func_num_args()>=1){
                self::$in->loadCfg(func_get_arg(0));
            }
        }

        self::$in->getPID();//查找当前PID
        $mod=@$_SERVER['argv'][1];
        switch($mod){
            case 'start'://启动服务
                self::$in->start();
                break;
            case 'stop'://停止服务
                self::$in->stop();
                break;
            case 'reload'://重新载入配置文件
                self::$in->reload();
                break;
            case 'restart'://重新启动
                self::$in->restart();
                break;
        }

        return self::$in;
    }

    /**
     * 启动服务
     * 当服务已启动时,直接返回并送出异常
     * 当服务未被启动时,直接启动
     *
     * @return void
    **/
    public function start()
    {
        if($this->_pid==0){
            $this->showMsg("Starting FDpcntl.");
            $this->run();
        }else{
            $this->showMsg("FDpcntl already running? (pid={$this->_pid}).");
        }
    }

    /**
     * 重启服务
     * 当服务已被启动时,先停止服务器任何在启动服务器
     * 当服务未被启动时,直接启动服务
     *
     * @return void
    **/
    public function restart()
    {
        if($this->_pid==0){
            $this->showMsg("Starting FDpcntl.");
            $this->run();
        }else{
            $this->stop();//先停止服务
            sleep(1);
            $this->start();//启动服务
        }
    }

    /**
     * 重新载入配置文件
     * 保留方法
     *
     * @return void
    **/
    public function reload()
    {

    }

    /**
     * 停止服务
     * 当服务器被启动时才能被叫停
     * 否则直接发送消息,不做任何操作
     *
     * @return void
    **/
    public function stop()
    {
        if($this->_pid==0){
            $this->showMsg("FDpcntl not running? (check {$this->pcfg['pidfile']})");
        }else{
            $this->showMsg("Stopping FDpcntl.");
            $this->showMsg("Waiting for PIDS: {$this->_pid}.");
            posix_kill($this->_pid, SIGTERM);//停止服务并发送请求头
            $this->_pid=0;//重置PID
            @unlink($this->pcfg['pidfile']);//清理PID文件
        }
    }

    /**
     * 开始运行服务器
     *
     * @return void
    **/
    private function run()
    {
        set_time_limit(0);
        //ob_implicit_flush();

        declare(ticks = 1);
        $this->sigDaemon();//采用Daemon模式运行
        pcntl_signal(SIGTERM, array($this, 'sigHandler'));
        pcntl_signal(SIGINT,  array($this, 'sigHandler'));
        pcntl_signal(SIGCHLD, array($this, 'sigHandler'));

        $sock=$this->startSocket();
        if(false===$sock){
            $this->showMsg('Waiting for clients Not connect.');//写入日志已经成功创建Socket
            return;
        }else{
            $this->showMsg('Waiting for clients to connect.');//写入日志已经成功创建Socket
        }
        $this->setPID();//缓存当前PID以便复用

        //Daemon方式检测客户端的输入
        while(true){
            $csock=@socket_accept($sock);//检测客户端是否有信号连接
            if(false===$csock){
                usleep(1000);//1ms
            }elseif($csock>0){
                if($this->sigClient($csock)) break;
            }else{
                $this->ErrorLog("Error: ".socket_strerror($csock));
            }
        }
        @socket_close($csock);
        @socket_close($sock);
    }

    /**
     * 创建一个Socket终端
     * 成功返回Socket资源标识
     * 失败返回false
     *
     * @return results|bool
    **/
    public function startSocket()
    {
        //创建一个socket终端
        if(($sock=socket_create(AF_INET, SOCK_STREAM,SOL_TCP))===false){
            $this->showMsg('Failed to create socket: '.socket_strerror($sock));
            return false;
        }

        if (($ret=socket_bind($sock,$this->pcfg['server_ip'],$this->pcfg['port']))===false){
            socket_close($sock);
            $this->showMsg('Failed to bind socket: '.socket_strerror($ret));
            return false;
        }

        if (($ret = socket_listen($sock, 0)) === false){
            $this->showMsg('Failed to listen to socket: '.socket_strerror($ret));
            return false;
        }
        unset($ret);
        socket_set_nonblock($sock);
        return $sock;
    }



    /**
     * Daemon守护程方式运行服务
     * 在启动服务时使用Daemon方式放入后台守护运行
     *
     * @return void
    **/
    public function sigDaemon()
    {
        $this->ErrorLog("Begin parent daemon pid({$this->_pid}).");
        $pid = pcntl_fork();
        if ($pid == - 1){//启动新进程失败
            $this->showMsg("Fork failure pid({$this->_pid}).");exit;
        }else if ($pid){//关闭当前的父进程
            $this->ErrorLog("End parent daemon pid({$this->_pid}) exit.");exit;
        }else{//将派生的子进程设置为守护进程
            posix_setsid();

            //设置运行的用户
            $pw=posix_getpwnam($this->pcfg['user']);
            if(is_array($pw) && count($pw)>0){
                posix_setuid($pw['uid']);
                posix_setgid($pw['gid']);
            }

            $this->_pid = posix_getpid();
            $this->ErrorLog("Begin child daemon pid({$this->_pid}).");
        }
    }

    /**
     * 信号处理
     * 当服务启动、停止、重新启动等动作时自动调用该信号处理
     *
     * @param  string $sig 信号常量
     * @return void
    **/
    public function sigHandler($sig)
    {
        //$this->RunLog("EXIT PID($this->_pid) $sig - ".posix_getpid().".");
        switch ($sig){
            case SIGINT://2,中断服务 interrupt prog (term) (ctl-c)
            case SIGTERM://15,终止进程 terminate process (term)
                exit;
                break;

            case SIGHUP://1,退出终端 terminal line hangup (term)
            case SIGQUIT://3,退出程序 quit program (core)

                break;

            case SIGCHLD://20,子进程改变 child status changed (ign)
                pcntl_waitpid(- 1, $status);
                break;

            case SIGUSR1:// 30, user defined signal 1 (term)
            case SIGUSR2:// 31, user defined signal 2 (term)
                echo "Caught SIGUSR1...\n";
                break;

            default://handle all other signals
                break;
        }
    }

    /**
     * 终端处理
     * 呼叫的SIG系列处理方法: sigDebug sigFend sigFunction 时返回值只能为0-2之间的整数型数值
     * 0: 抛出消息到终端,继续等待输入
     * 1: 抛出消息到终端,并终止输入关闭进程
     * 2: 隐藏消息的抛出,并终止输入关闭经常
     *
     * @param  string $csock  Socket资源
     * @return bool
    **/
    public function sigClient($csock)
    {
        ob_start();
        $this->ErrorLog('Begin client.');
        $pid=pcntl_fork();//派生一个新的进程
        if ($pid==-1){
            $this->ErrorLog("Fock clinet child error.");
        }elseif($pid==0){//子进程成功派生
            $pid = posix_getpid();//获取当前进程的PID
            $this->ErrorLog("Begin client child pid({$pid}).");
            $this->ErrorLog("Begin handle user logic.");

            //--------------获取客户端的输入
            empty($this->pcfg['fpmsg']) && socket_write($csock, $this->pcfg['fpmsg']."\r\n", strlen($this->pcfg['fpmsg']."\r\n"));
            socket_set_block($csock);//未知

            if($this->pcfg['fptype']=='debug'){
                $this->sigDebug($csock);//呼叫处理方法
            }else{//以下所有返回必须都为true
                $this->sigFend($csock);//呼叫处理方法
            }
            //--------------获取客户端的输入
            $this->ErrorLog("End handle user logic.");
            $this->ErrorLog("End client");
            return true;
        }else{
            $this->ErrorLog("Close csock in child pid({$pid}).");
        }
        return false;
    }

    /**
     * Debug调试模式
     *
     * @param  string $str  客户端传递过来的字符
     * @return int
    **/
    public function sigDebug($csock)
    {
        while(true){
            $nbuf=null;
            if(false===($nbuf=socket_read($csock,2048,PHP_NORMAL_READ))){//读取失败直接跳出循环
                $this->ErrorLog("socket_read() failed: reason: ".socket_strerror(socket_last_error($csock)));
                break;
            }
            $nbuf=trim($nbuf);//去掉空白字符
            if(empty($nbuf)) continue;//没有任何可打印字符时返回到等待
            if($nbuf=='quit') break;
            if($nbuf{0}=='$'){
                eval('$nbuf=@print_r('.$nbuf.',true);');
            }
            $nbuf="#-result: $nbuf\r\n#";

            //$tmsg=$isloop!==2 ? ob_get_contents() : null;//取得缓冲
            //ob_clean();//清除缓冲
            socket_write($csock, $nbuf, strlen($nbuf));
        }
    }

    /**
     * Fend模式
     *
     * @param  string $csock 终端输入监控资源
     * @return bool
    **/
    public function sigFend($csock)
    {
        if(false===($nbuf=socket_read($csock,12000))){//读取失败直接跳出循环
            $this->ErrorLog("Socket_read() failed reason: ".socket_strerror(socket_last_error($csock)));
            return true;
        }

        //分析被读取的信息
        $nbuf=explode("\r\n",$nbuf);
        $item=array('FP-Close'=>0,'FDTCP'=>null);
        foreach($nbuf as &$v){
            if(empty($v) || false===strpos($v,':')) continue;
            list($key,$value)=explode(':',$v,2);

            if($key=='GET'){
                parse_str($value,$_GET);
            }elseif($key=='POST'){
                parse_str($value,$_POST);
            }else{
                $item[$key]=$value;
            }
        }

        //动态载入函数文件
        is_file($this->pcfg['function']['apps_php']) && include($this->pcfg['function']['apps_php']);
        if(!function_exists($this->pcfg['function']['apps_mod'])){
            $this->ErrorLog("ERROR: Call_user_func_array({$this->pcfg['function']['apps_mod']}).");
            return true;
        }

        //$this->runLog("GET:{$item['FDTCP']}");
        call_user_func_array($this->pcfg['function']['apps_mod'],array(&$item,&$this));

        if($item['FP-Close']){
            $tmsg=ob_get_contents();//取得缓冲
            @socket_write($csock, $tmsg, strlen($tmsg));
        }
        ob_clean();//清除缓冲
    }

    /**
     * 获取当前正在运行服务PID
     * 当服务器成功运行时返回大于0的整数
     * 当服务器失败运行或未运行时返回一个小于等于0的整数
     *
     * @return int
    **/
    private function getPID()
    {
        if(is_file($this->pcfg['pidfile'])){
            $this->_pid=(int)file_get_contents($this->pcfg['pidfile']);
        }else{
            $this->_pid=0;
        }
    }

    /**
     * 设置当前服务PID
     *
     * @return void
    **/
    private function setPID()
    {
        $this->_pid = posix_getpid();
        $this->ErrorLog("Begin child daemon pid($this->_pid).");
        file_put_contents($this->pcfg['pidfile'],"{$this->_pid}");
        $this->ErrorLog("Write pidfile({$this->pcfg['pidfile']}).");
    }

    /**
     * 发送异常到终端并记录日志
     *
     * @param  string $str 异常消息
     * @param  string $t   是否终止
     * @return void
    **/
    private function showMsg($str)
    {
        echo $str."\n";
        $this->ErrorLog($str);
    }

    /**
     * 记录异常日志
     * 包括DEBUG相关信息
     *
     * @param  string $str    异常消息
    **/
    private function ErrorLog($str)
    {
        $str=date("[Y-m-d H:i:s]").' '.$str."\n";
        file_put_contents($this->pcfg['error_log'],$str,FILE_APPEND);
    }

    /**
     * 记录运行日志
     *
     * @param  string $str    异常消息
    **/
    public function RunLog($str)
    {
        $str=date("[Y-m-d H:i:s]").' '.$str."\n";
        file_put_contents($this->pcfg['access_log'],$str,FILE_APPEND);
    }

    /**
     * 载入配置文件
     *
     * @param  string $str    异常消息
    **/
    public function loadCfg($fini)
    {
        $fini=parse_ini_file($fini,true);
        is_array($fini) && $this->pcfg=array_merge($this->pcfg,$fini);
        //if(!in_array($this->pcfg['fptype'],array('debug','fend','function'))) $this->pcfg['fptype']='debug';

        //处理运行宿主目录
        !is_dir($this->pcfg['fpdir']) && $this->pcfg['fpdir']=dirname(__FILE__).'/';
        chdir($this->pcfg['fpdir']);//切换到宿主目录

        empty($this->pcfg['function']['apps_mod']) && $this->pcfg['function']['apps_mod']='sigFunction';
    }

    /**
     * 说明
     *
     * @param  string $var1    参数说明
     * @param  string $var2    参数说明
     * @return array  $tplPre  模板后缀
    **/
    public function __destruct()
    {

    }

}
?>