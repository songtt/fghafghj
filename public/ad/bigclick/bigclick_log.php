<?php
ini_set('memory_limit','4096M');
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

echo microtime();
echo '--start time--','0',"\n";
echo memory_get_usage();
echo '--use memory--','0',"\n";

// $prefix = "/home/www/yanfa/lezun/public";
//获取时间
$daylog = date('Ymd');
//日期目录
$logdate = date('H-i',strtotime("-10 minute"));

//文件目录
$data_str = substr($logdate,0,strlen($logdate)-1);
//文件名字
$file_name = "d".$data_str.".log";
$file_path = "/home/lezunlog/nginx1/".$daylog."/".$file_name;

echo $file_path . "\n";
if(file_exists($file_path)){
    $file_one = file($file_path);
}
@fclose($file_one);

$file_path = "/home/lezunlog/nginx2/".$daylog."/".$file_name;
if(file_exists($file_path)){
    $file_two = file($file_path);
}
@fclose($file_two);

//将两个文件内容合并
$fi = 0;
$file_arr = array();
// $dataArr = array();
foreach ($file_one as &$line) {
    $file_arr[$fi] = $line;
    $fi++;
}

//文件已用完
foreach ($file_two as &$line) {
    $file_arr[$fi] = $line;
    $fi++;
}
unset($file_one);unset($file_two);

//处理数据
$final_res = array();
foreach($file_arr as $key=>$value){
    $data = explode(',',$value);
    $data[0] = substr($data[0],4);  //pid
    $data[1] = substr($data[1],9);  //bigviews
    $final_res[$data['0']]['pid'] = $data['0'];
    $final_res[$data['0']]['bigclick'] = isset($final_res[$data['0']]['bigclick']) ? $final_res[$data['0']]['bigclick'] : 0;
    $final_res[$data['0']]['bigclick'] += (int)$data['1'];
}

//组装sql
$per_num = 0;
$sql_num = 0;
$insert_sql = array();
foreach($final_res as $key=>$value){
    if($per_num==0){
        $insert_sql[$sql_num] = 'INSERT INTO lz_bigclick_log (pid,bigclick,day) VALUES ';
    }
    $insert_sql[$sql_num].= '('.$value['pid'].','.$value['bigclick'].',\''.date('Y-m-d',strtotime("-10 minute")).'\''.'),';
    if($per_num==3000){
        $per_num=0;
        $sql_num++;
    }else{
        $per_num++;
    }
}
unset($final_res);

try{
   $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    // $pdo = new PDO('mysql:host=192.168.63.3;dbname=lezunsys;port=3306','root','xya197');
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('set names utf8');
}catch(PDOException $e){
}

//入库
foreach ($insert_sql as $key => $value) {
    if(empty($value)){
        continue;
    }
    $value = rtrim($value,',');
    $value = $value.';';
    $insert = $pdo->prepare(''.$value.'');
    $insert->execute();
}
unset($insert_sql);

echo microtime();
echo '--end time--','5',"\n";
echo memory_get_usage();
echo '--use memory--','5 done',"\n";


$pdo=null;
exit;
