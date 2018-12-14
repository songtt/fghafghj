<?php
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间
//连接数据库
try{
//    $pdo = new PDO('mysql:host=101.201.29.182;dbname=mibew;port=3306','root','xya197');
     // $pdo = new PDO('mysql:host=127.0.0.1;dbname=leznsys;port=3306','root','123456');
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
//获取url列表
$sql = $pdo->prepare(' SELECT a.pro_id,a.post_type,a.status,b.url_id,b.url,b.percent,b.checkplan,b.delivery_mode FROM
                      lz_bigclick_product AS a LEFT JOIN  lz_bigclick_url_copy AS b ON a.pro_id=b.pro_id
                      WHERE a.status=1 AND b.status=1 AND b.url_id=691 ORDER BY a.pro_id');
$sql->execute();
$res = $sql->fetchAll();
foreach ($res as $key => $value) {
	$res[$key]['url'] = base64_decode($value['url']);
}
$res = serialize($res);

//文件目录
if (!file_exists(__DIR__.'/datasql')){
    mkdir (__DIR__."/datasql",0755,true);
}
$log_test_file = __DIR__.'/datasql/sql.log';
$myfile = fopen($log_test_file, "w") or die("Unable to open file!");
fwrite($myfile, $res);
fclose($myfile);

?>