<?php
set_time_limit(0);
// error_reporting(0);
date_default_timezone_set('PRC');//校正时间
// ini_set('memory_limit','4096M');


//连接数据库
try{
    //97  读
    $spdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');
    // $pdo = new PDO('mysql:host=117.34.72.97;dbname=lezunsys;port=3306','username','password');
    //本地
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','');
    $spdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $spdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
function writeFile($file,$str,$mode='a+')
{
    // $oldmask = @umask(0);
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


$day = date('Y-m-d',strtotime('-1 day'));
$day = '2017-08-10';
//查询出stats_log 中合并后数据
$select = $spdo->prepare('SELECT uid,pid,ad_id,adv_id,adtpl_id,site_id,adz_id,tc_id,SUM(views) AS views,SUM(web_num) AS web_num,SUM(adv_num) AS adv_num,SUM(click_num) AS click_num,MAX(web_click_num) AS web_click_num,MAX(adz_click_num) AS adz_click_num,day,SUM(sumprofit) AS sumprofit,SUM(sumpay) AS sumpay,SUM(sumadvpay) AS sumadvpay,SUM(web_deduction) AS web_deduction,SUM(adv_deduction) AS adv_deduction,SUM(uv_web) AS uv_web,MAX(ui_adzone) AS ui_adzone,SUM(ui_web) AS ui_web,plan_type,MAX(heavy_click_num) AS heavy_click_num,ctime FROM lz_stats_log WHERE day=? GROUP BY ad_id,adz_id,site_id');
$select->execute(array($day));
$selectRes = $select->fetchAll();
var_dump($selectRes);exit;

// echo count($selectRes);
$spdo = null;

// 组装sql 
$insert_sql = array();
$per_num = 0;
$sql_num = 0;
$time = time();
foreach ($selectRes as $key => $list) {
   	if($per_num==0){
        $insert_sql[$sql_num] = 'INSERT INTO lz_stats_new (uid,pid,ad_id,tc_id,adv_id,adtpl_id,site_id,adz_id,sumprofit,sumpay,sumadvpay,views,day,web_deduction,adv_deduction,web_num,adv_num,uv_web,ui_web,ui_adzone,web_click_num,adz_click_num,heavy_click_num,click_num,plan_type,ctime) VALUES ';
    }
    
    $insert_sql[$sql_num].= "(".$list['uid'].",".$list['pid'].",".$list['ad_id'].",".$list['tc_id'].",".$list['adv_id'].",".$list['adtpl_id'].",".$list['site_id'].",".$list['adz_id'].",".$list['sumprofit'].",".$list['sumpay'].",".$list['sumadvpay'].",".$list['views'].",'".$list['day']."',".$list['web_deduction'].",".$list['adv_deduction'].",".$list['web_num'].",".$list['adv_num'].",".$list['uv_web'].",".$list['ui_web'].",".$list['ui_adzone'].",".$list['web_click_num'].",".$list['adz_click_num'].",".$list['heavy_click_num'].",".$list['click_num'].",'".$list['plan_type']."',".$time."),";

    if($per_num==3000){
        $per_num=0;
        $sql_num++;
    }else{
        $per_num++;
    }
}
unset($selectRes);
//连接数据库
try{
    //97  读
    // $epdo = new PDO('mysql:host=10.28.206.192;dbname=lz_ad;port=3306','root','a869uP1ykYW1');
    // $pdo = new PDO('mysql:host=117.34.72.97;dbname=lezunsys;port=3306','username','password');
    //本地
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');
    $epdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $epdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
//入lz_stats_new
foreach ($insert_sql as $key => $value) {
    if(empty($value)){
        continue;
    }
    $value = rtrim($value,',');
    $value = $value.';';
    $insert = $epdo->prepare(''.$value.'');
    $insert->execute();
}
unset($insert_sql);
$epdo = null;
echo "MISSTION SUCCESS "."\n";
exit;
