<?php
ini_set('memory_limit','4096M');
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

echo microtime();
echo '--start time--','0',"\n";
echo memory_get_usage();
echo '--use memory--','0',"\n";

try{
   $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','');
    // $pdo = new PDO('mysql:host=192.168.63.3;dbname=lezunsys;port=3306','root','xya197');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('set names utf8');
}catch(PDOException $e){
}

$time = date("Y-m-d",strtotime("-1 day"));


//查询出log表昨日的数据
$sql = $pdo->prepare("SELECT pid,SUM(bigclick) as bigclick,day FROM lz_bigclick_log WHERE day=? GROUP BY pid");
$sql->execute(array($time));
$data = $sql->fetchAll();

//拼接sql
$per_num = 0;
$sql_num = 0;
$insert_sql = array();
foreach($data as $key=>$value){
    if($per_num==0){
        $insert_sql[$sql_num] = 'INSERT INTO lz_bigclick (pid,bigclick,day) VALUES ';
    }
    $insert_sql[$sql_num].= '('.$value['pid'].','.$value['bigclick'].',\''.$value['day'].'\''.'),';
    if($per_num==3000){
        $per_num=0;
        $sql_num++;
    }else{
        $per_num++;
    }
}

unset($data);
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
?>