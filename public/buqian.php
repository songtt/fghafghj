<?php
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

try{
    //97  读
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');
	$pdo = new PDO('mysql:host=10.28.206.192;dbname=lz_ad;port=3306','root','a869uP1ykYW1');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
//财务手动已经填入等待支付的
$arr = array(6994,6786,5805,6853,6272,5588,5398,1640,1512,1190,1180,6972,6106,6923,6356,6071,1841);

//查询所有的站长2018-09-24至2018-09-30的钱   应当插入的时间2018-09-30
$star = '2018-09-24';
$end = '2018-09-30';
$sql = $pdo->prepare('SELECT SUM(sumpay) as sumpay,uid FROM lz_stats_new WHERE  day>=? AND day<=? GROUP BY uid');
$sql->execute(array($star,$end));
$stats_new = $sql->fetchAll();

//将查询到的结果插入到财务表中
$insert_sql = 'INSERT INTO lz_paylog (uid,xmoney,type,status,payinfo,day,ctime) VALUES ';
$insert_str = '';
foreach ($stats_new as $key => $value) {
	if(!in_array($value['uid'], $arr)){
		if (empty($insert_str)) {
			$insert_str = "(".$value['uid'].','.$value['sumpay'].",1,3,4,'2018-09-30',".time().")";
		}else{
			$insert_str = $insert_str.','."(".$value['uid'].','.$value['sumpay'].",1,3,4,'2018-09-30',".time().")";
		}
	}
	
}
$sql_res = $insert_sql.$insert_str;
//入库
$insert = $pdo->prepare(''.$sql_res.'');
$insert->execute();
unset($sql_res);
exit;
