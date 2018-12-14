<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

// error_reporting(0);
date_default_timezone_set('PRC');//校正时间


//连接数据库
try{
    $spdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');

//    $spdo = new PDO('mysql:host=10.28.206.192;dbname=lz_ad;port=3306','root','a869uP1ykYW1');
    $spdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $spdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

// 减数据    10865
$ad_idA = -time();
$sqlA = $spdo->prepare("INSERT INTO lz_stats_new (adz_id,uid,ad_id,views,sumpay,sumprofit,day) VALUES (?,?,?,?,?,?,?)");
$sqlA->execute(array(10865,7056,$ad_idA,1,-276.16,276.16,'2018-11-21'));

$sqlB = $spdo->prepare("INSERT INTO lz_stats_new (adz_id,uid,ad_id,views,sumpay,sumprofit,day) VALUES (?,?,?,?,?,?,?)");
$sqlB->execute(array(10865,7056,$ad_idA,1,-569.04,569.04,'2018-11-22'));

$sqlEo = $spdo->prepare("INSERT INTO lz_stats_log (adz_id,uid,ad_id,views,sumpay,sumprofit,day) VALUES (?,?,?,?,?,?,?)");
$sqlEo->execute(array(10865,7056,$ad_idA,1,-569.04,569.04,'2018-11-22'));

//减去站长余额
$sqlpay = $spdo->prepare("UPDATE lz_users SET money=money-845.2 WHERE uid=7056");
$sqlpay->execute(array());


echo '6891 done';
$spdo = null;
die;