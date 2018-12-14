<?php
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间
ini_set('memory_limit','4096M');
//连接数据库
try{
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    //97  读
    // $pdo = new PDO('mysql:host=117.34.72.97;dbname=lezunsys;port=3306','username','password');
    //本地
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
echo '1',"\n";
echo memory_get_usage(),"--1--\n";
//删除
$beforDay = date('Y-m-d',strtotime('-2 day')); //前天
$delete = $pdo->prepare('DELETE FROM lz_stats_log WHERE day=?');
$delete->execute(array($beforDay));
echo '2',"\n";
echo memory_get_usage(),"--delete day log 2--\n";

$pdo = null;
exit;
