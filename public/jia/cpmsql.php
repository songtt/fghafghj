<?php
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

//连接数据库
try{
    // $pdo = new PDO('mysql:host=101.201.29.182;dbname=mibew;port=3306','root','xya197');
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

$siqiyi = array();
//siqiyi写文件
$sql = $pdo->prepare(' SELECT a.pro_id,b.url_id,b.url,b.percent,b.checkplan,b.delivery_mode FROM
                      lz_bigclick_cpm_product AS a LEFT JOIN  lz_bigclick_cpm_url_copy AS b ON a.pro_id=b.pro_id
                      WHERE a.post_type=1 AND a.status=1 AND b.status=1 ORDER BY a.pro_id');
$sql->execute();
$res = $sql->fetchAll();
foreach ($res as $key => $value) {
  $siqiyi[$key]['pro_id'] = $value['pro_id'];
  $siqiyi[$key]['url_id'] = $value['url_id'];
  $siqiyi[$key]['percent'] = $value['percent'];
  $siqiyi[$key]['checkplan'] = $value['checkplan'];
  $siqiyi[$key]['delivery_mode'] = $value['delivery_mode'];
  $siqiyi[$key]['url'] = base64_decode($value['url']);
}
$siqiyi = base64_encode(serialize($siqiyi));
//文件目录
if (!file_exists(__DIR__.'/datasql')){
    mkdir (__DIR__."/datasql",0755,true);
}
$log_test_file = __DIR__.'/datasql/siqiyisql.log';
$myfile = fopen($log_test_file, "w") or die("Unable to open file!");
fwrite($myfile, $siqiyi);
fclose($myfile);


//hxdq写文件
$hxdq = array();
$sql = $pdo->prepare(' SELECT a.pro_id,b.url_id,b.url,b.percent,b.checkplan,b.delivery_mode FROM
                      lz_bigclick_cpm_product AS a LEFT JOIN  lz_bigclick_cpm_url_copy AS b ON a.pro_id=b.pro_id
                      WHERE a.post_type=2 AND a.status=1 AND b.status=1 ORDER BY a.pro_id');
$sql->execute();
$res = $sql->fetchAll();
foreach ($res as $key => $value) {
  $hxdq[$key]['pro_id'] = $value['pro_id'];
  $hxdq[$key]['url_id'] = $value['url_id'];
  $hxdq[$key]['percent'] = $value['percent'];
  $hxdq[$key]['checkplan'] = $value['checkplan'];
  $hxdq[$key]['delivery_mode'] = $value['delivery_mode'];
  $hxdq[$key]['url'] = base64_decode($value['url']);
}
$hxdq = base64_encode(serialize($hxdq));

//文件目录
if (!file_exists(__DIR__.'/datasql')){
    mkdir (__DIR__."/datasql",0755,true);
}
$log_test_file = __DIR__.'/datasql/hxdqsql.log';
$myfile = fopen($log_test_file, "w") or die("Unable to open file!");
fwrite($myfile, $hxdq);
fclose($myfile);
?>