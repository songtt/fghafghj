<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

error_reporting(0);
date_default_timezone_set('PRC');//校正时间
//数据库连接
try{
   $spdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
// $pdo = new PDO('mysql:host=192.168.63.3;dbname=lezunsys;port=3306','root','xya197');
// $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
$spdo->exec('set names utf8');
$spdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
//获取ajax传送过来的本次大点击的广告id
$id = $_GET['id'];
//查询出本次要发送的大点击链接并且发送，并返回其计划id
$pid = getClickUrl($spdo,$id);
//统计数据
echoLog($pid);
$spdo = null;
//筛选出本次要发送的大点击链接并且发送，并返回其计划id
function getClickUrl($spdo,$id)
{
    //查询出符合大点击计划的所有的大点击链接
    $prep= $spdo->prepare("SELECT a.click_url,a.pid,b.percent FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid WHERE ad_id=?");
    $prep->execute(array($id));
    $res = $prep->fetchAll();
    //获取此链接的发送概率，判断本次是否发送链接
    $rand = rand(1,100);
    if($rand <= $res[0]['percent']){
        header('Content-Type:application/json; charset=utf-8');
        $data_front = json_encode($res[0]);
        //执行回调
        $callback = $_GET['callback'];
        echo $callback."($data_front)";
    }else{
        //执行回调
        $callback = $_GET['callback'];
        echo $callback;
        exit;
    }

    return $res[0]['pid'];
}

//统计数据
function echoLog($final_pid)
{
    //大点击统计到log
    $logday = date('Ymd');
    $logdate = date('H-i');

    //文件目录
    $data_str = substr($logdate,0,strlen($logdate)-1);
    if (!file_exists(__DIR__.'/../../test/lezunlog/'.$logday)){
        mkdir (__DIR__."/../../test/lezunlog/".$logday,0755,true);
    }
    //写入log
    $log_test_file = __DIR__."/../../test/lezunlog/".$logday.'/'.'d'.$data_str.'.log';
    $log_test_str = "pid=".$final_pid.",bigclick=1"."\n";
    writeFile($log_test_file,$log_test_str);
}

//写日志函数
function writeFile($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
    $fp = @fopen($file,$mode);
    // @flock($fp, 3);
    if(!$fp){

    } else {
        @fwrite($fp,$str);
        @fclose($fp);
        // @umask($oldmask);
        // Return true;
    }
}