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

$sqlB = $spdo->prepare("select ad_id,imageurl from lz_ads where pid=?");
$sqlB->execute(array(7439));
$res = $sqlB->fetchAll();

foreach ($res as $key => $value){
    $value['imageurl'] = str_replace('img.miaoceshi.com','img.mmvbqd.cn',$value['imageurl']);
    $sql = $spdo->prepare("update lz_ads set imageurl=? where ad_id=?");
    $sql->execute(array($value['imageurl'],$value['ad_id']));
}
echo '修改成功';die;