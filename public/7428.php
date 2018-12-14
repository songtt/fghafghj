<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

// error_reporting(0);
date_default_timezone_set('PRC');//校正时间

//连接数据库
try{
    $spdo = new PDO('mysql:host=10.28.206.192;dbname=lz_ad;port=3306','root','a869uP1ykYW1');
//    $spdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');
    $spdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $spdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

//广告商增加消耗   7428
$ad_idB = -time();
$sqlB = $spdo->prepare("INSERT INTO lz_stats_new (pid,adv_id,ad_id,views,sumadvpay,sumprofit,day) VALUES (?,?,?,?,?,?,?);");
$sqlB->execute(array(7428,6210,$ad_idB,1,519.42,519.42,'2018-12-6'));

$sqlBo = $spdo->prepare("INSERT INTO lz_stats_log (pid,adv_id,ad_id,views,sumadvpay,sumprofit,day) VALUES (?,?,?,?,?,?,?);");
$sqlBo->execute(array(7428,6210,$ad_idB,1,519.42,519.42,'2018-12-6'));

//广告商减余额
$sqlpay = $spdo->prepare("UPDATE lz_users SET money=money-519.42 WHERE uid=6210");
$sqlpay->execute(array());

echo '7428 done';
$spdo = null;
die;