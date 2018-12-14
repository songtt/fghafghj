<?php
ini_set('memory_limit','4096M');
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

//连接redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
//redis切库 若是手动执行的情况下根据时间调整库
$week = date('w');
if(date('Y-m-d') != '2018-01-02'){
    switch($week){
    case 1:
        $redis->select(1);
        break;
    case 2:
        $redis->select(2);
        break;
    case 3:
        $redis->select(3);
        break;
    case 4:
        $redis->select(4);
        break;
    case 5:
        $redis->select(5);
        break;
    case 6:
        $redis->select(6);
        break;
    case 0:
        $redis->select(7);
    }
}

echo microtime();
echo '----','0',"\n";
echo memory_get_usage();
echo '----','0',"\n";

$canshu = $_GET['ag'];
//文件名字
$file_name = "c".$canshu.".log";

$daylog = date('Ymd');
$file_path = "/home/www/lezun/public/test/lezunlog/".$daylog."/".$file_name;
if(file_exists($file_path)){
    $file_one = file($file_path);
}
@fclose($file_one);

// echo memory_get_usage(),'---00memory--'."\n";

// $file_path = "nginx2/".$daylog."/".$file_name;
// if(file_exists($file_path)){
//     $file_two = file($file_path);
// }

// @fclose($file_two);
// echo count($file_two),"\n";


$fi = 0;
$file_arr = array();
// $dataArr = array();
foreach ($file_one as &$line) {
    $file_arr[$fi] = $line;
    $fi++;
}

//文件已用完
unset($file_one);
// foreach ($file_two as &$line) {
//     $file_arr[$fi] = $line;
//     $fi++;
// }

// unset($file_two);


echo memory_get_usage(),'---333memory--'."\n";

// exit;

//开始合并
echo count($file_arr),"\n";
// $res = read_Pvarray($file_arr);

echo memory_get_usage(),'---444memory--'."\n";

// exit;

// unset($file_arr);

echo microtime();
echo '----','1',"\n";
echo memory_get_usage(),'---222memory--'."\n";
echo '----','1读文件',"\n";

// function read_Pvarray($data){
    //处理数据
    $array = array();
    $x = 0;
    $i = 0;
    $res = array();

    $final_res = array();
    echo memory_get_usage(),'---0001--'."\n";
    foreach ($file_arr as $key => $value) {
        $value = trim($value);

        //如果不为空才进去
        if(!empty($value)){

            //以逗号分割字符串变成数组  结构是  0 =》 ad_id=12
            $array[$x] = explode(',', $value);
                
            //处理合并数组，得到最终的每一条sql值  二维数组
            if(!empty($array[$x][0])){
                // echo memory_get_usage(),'---111--'."\n";
                // $array[$x][0] = !isset($value[0])?0:$value[0];//ad_id
                $array[$x][1] = !isset($array[$x][1])?0:$array[$x][1];//adz_id
                $array[$x][2] = !isset($array[$x][2])?0:$array[$x][2];//site_id
                $array[$x][3] = !isset($array[$x][3])?0:$array[$x][3];//uid
                $array[$x][4] = !isset($array[$x][4])?0:$array[$x][4];//pid
                $array[$x][5] = !isset($array[$x][5])?0:$array[$x][5];//views
                $array[$x][6] = !isset($array[$x][6])?0:$array[$x][6];//click
                $array[$x][7] = !isset($array[$x][7])?0:$array[$x][7];//sumprofit
                $array[$x][8] = !isset($array[$x][8])?0:$array[$x][8];//sumpay
                $array[$x][9] = !isset($array[$x][9])?0:$array[$x][9];//sumadvpay
                $array[$x][10] = !isset($array[$x][10])?0:$array[$x][10];//uv_web
                $array[$x][11] = !isset($array[$x][11])?0:$array[$x][11];//ui_web
                $array[$x][12] = !isset($array[$x][12])?0:$array[$x][12];//web_deduction
                $array[$x][13] = !isset($array[$x][13])?0:$array[$x][13];//adv_deduction
                $array[$x][14] = !isset($array[$x][14])?0:$array[$x][14];//web_num
                $array[$x][15] = !isset($array[$x][15])?0:$array[$x][15];//adv_num
                $array[$x][16] = !isset($array[$x][16])?0:$array[$x][16];//day
                $array[$x][17] = !isset($array[$x][17])?0:$array[$x][17];//adv_id
                $array[$x][18] = !isset($array[$x][18])?0:$array[$x][18];//plan_type
                $array[$x][19] = !isset($array[$x][19])?0:$array[$x][19];//tc_id
                $array[$x][20] = !isset($array[$x][20])?0:$array[$x][20];//tpl_id

                // echo memory_get_usage(),'---2222--'."\n";
                $res[$i]['ad_id'] =  substr($array[$x][0],stripos($array[$x][0],'=')+1);
                $res[$i]['adz_id'] = substr($array[$x][1],stripos($array[$x][1],'=')+1);
                $res[$i]['site_id'] = substr($array[$x][2],stripos($array[$x][2],'=')+1);
                $res[$i]['uid'] = substr($array[$x][3],stripos($array[$x][3],'=')+1);
                $res[$i]['pid'] = substr($array[$x][4],stripos($array[$x][4],'=')+1);
                $res[$i]['views'] = substr($array[$x][5],stripos($array[$x][5],'=')+1);
                $res[$i]['click_num'] = substr($array[$x][6],stripos($array[$x][6],'=')+1);
                $res[$i]['sumprofit'] = substr($array[$x][7],stripos($array[$x][7],'=')+1);
                $res[$i]['sumpay'] = substr($array[$x][8],stripos($array[$x][8],'=')+1);
                $res[$i]['sumadvpay'] = substr($array[$x][9],stripos($array[$x][9],'=')+1);
                $res[$i]['uv_web'] = substr($array[$x][10],stripos($array[$x][10],'=')+1);
                $res[$i]['ui_web'] =  substr($array[$x][11],stripos($array[$x][11],'=')+1);
                $res[$i]['web_deduction'] =  substr($array[$x][12],stripos($array[$x][12],'=')+1);
                $res[$i]['adv_deduction']= substr($array[$x][13],stripos($array[$x][13],'=')+1);
                $res[$i]['web_num']= substr($array[$x][14],stripos($array[$x][14],'=')+1);
                $res[$i]['adv_num']= substr($array[$x][15],stripos($array[$x][15],'=')+1);
                $res[$i]['day']= substr($array[$x][16],stripos($array[$x][16],'=')+1);
                $res[$i]['adv_id']= substr($array[$x][17],stripos($array[$x][17],'=')+1);
                $res[$i]['plan_type']= substr($array[$x][18],stripos($array[$x][18],'=')+1);
                $res[$i]['tc_id']= substr($array[$x][19],stripos($array[$x][19],'=')+1);
                $res[$i]['tpl_id']= substr($array[$x][20],stripos($array[$x][20],'=')+1);
                // echo memory_get_usage(),'---444--'."\n";
                // 合并组装数据
                if(!empty($res[$i]['ad_id'])&&!empty($res[$i]['adz_id'])&&!empty($res[$i]['uid'])&&!empty($res[$i]['pid'])&&!empty($res[$i]['adv_id'])&&!empty($res[$i]['plan_type'])&&!empty($res[$i]['tpl_id']))
                {
                        $name = 'stats-'.$res[$i]['ad_id'].'-'.$res[$i]['adz_id'].'-'.$res[$i]['site_id'].$res[$i]['day'];
                        $final_res[$name]['views'] = !isset($final_res[$name]['views'])?0:$final_res[$name]['views'];
                        $final_res[$name]['sumprofit'] = !isset($final_res[$name]['sumprofit'])?0:$final_res[$name]['sumprofit'];
                        $final_res[$name]['sumpay'] = !isset($final_res[$name]['sumpay'])?0:$final_res[$name]['sumpay'];
                        $final_res[$name]['sumadvpay'] = !isset($final_res[$name]['sumadvpay'])?0:$final_res[$name]['sumadvpay'];
                        $final_res[$name]['web_deduction'] = !isset($final_res[$name]['web_deduction'])?0:$final_res[$name]['web_deduction'];
                        $final_res[$name]['adv_deduction'] = !isset($final_res[$name]['adv_deduction'])?0:$final_res[$name]['adv_deduction'];
                        $final_res[$name]['web_num'] = !isset($final_res[$name]['web_num'])?0:$final_res[$name]['web_num'];
                        $final_res[$name]['adv_num'] = !isset($final_res[$name]['adv_num'])?0:$final_res[$name]['adv_num'];
                        $final_res[$name]['uv_web'] = !isset($final_res[$name]['uv_web'])?0:$final_res[$name]['uv_web']; //站长独立访客数
                        $final_res[$name]['ui_web'] = !isset($final_res[$name]['ui_web'])?0:$final_res[$name]['ui_web']; //站长独立ip
                        $final_res[$name]['ui_adzone'] = !isset($final_res[$name]['ui_adzone'])?0:$final_res[$name]['ui_adzone'];//广告位独立ip
                        $final_res[$name]['heavy_click'] = !isset($final_res[$name]['heavy_click'])?0:$final_res[$name]['heavy_click'];//计划下排重点击
                        $final_res[$name]['web_click_num'] = !isset($final_res[$name]['web_click_num'])?0:$final_res[$name]['web_click_num'];//站长下排重点击
                        $final_res[$name]['adz_click_num'] = !isset($final_res[$name]['adz_click_num'])?0:$final_res[$name]['adz_click_num'];//广告位下排重点击
                        $final_res[$name]['click_num'] = !isset($final_res[$name]['click_num'])?0:$final_res[$name]['click_num'];//点击

                        $final_res[$name]['adz_id'] = $res[$i]['adz_id'];
                        $final_res[$name]['ad_id'] = $res[$i]['ad_id'];
                        $final_res[$name]['uid'] = $res[$i]['uid'];
                        $final_res[$name]['pid'] = $res[$i]['pid'];
                        $final_res[$name]['tc_id'] = $res[$i]['tc_id'];
                        $final_res[$name]['adv_id'] = $res[$i]['adv_id'];
                        $final_res[$name]['adtpl_id'] = $res[$i]['tpl_id'];
                        $final_res[$name]['site_id'] = $res[$i]['site_id'];
                        $final_res[$name]['views'] += $res[$i]['views'];
                        $final_res[$name]['click_num'] += $res[$i]['click_num'];
                        $final_res[$name]['sumprofit'] += $res[$i]['sumprofit'];
                        $final_res[$name]['sumpay'] += $res[$i]['sumpay'];
                        $final_res[$name]['sumadvpay'] += $res[$i]['sumadvpay'];
                        $final_res[$name]['web_deduction'] += $res[$i]['web_deduction'];
                        $final_res[$name]['adv_deduction'] += $res[$i]['adv_deduction'];
                        $final_res[$name]['web_num'] += $res[$i]['web_num'];
                        $final_res[$name]['adv_num'] += $res[$i]['adv_num'];
                        $final_res[$name]['uv_web'] += $res[$i]['uv_web'];
                        $final_res[$name]['ui_web'] += $res[$i]['ui_web'];
                        $final_res[$name]['ui_adzone'] = 0;
                        $final_res[$name]['heavy_click'] = 0;
                        $final_res[$name]['web_click_num'] = 0;
                        $final_res[$name]['adz_click_num'] = 0;
                        $final_res[$name]['plan_type'] = $res[$i]['plan_type'];
                        $final_res[$name]['day'] = $res[$i]['day'];
                }
                //用完即删
                unset($res[$i]);

                $i++;
            }
            unset($array[$x]);//用完即删
            $x++;
        }
        unset($file_arr[$key]);//用完即删
    }
    unset($file_arr); //用完即删
//     echo memory_get_usage(),'---0002--'."\n";

//     echo count($final_res);

// echo microtime();
// echo '----','3',"\n";
// echo memory_get_usage();
// echo '----','3合并',"\n";



// echo count($array),'***',"\n";
//组装sql
$insert_sql = array();

$per_num = 0;
$sql_num = 0;
$time = time();
foreach ($final_res as $key => $list) {
    //计划排重点击
    $heavy_click = $redis->HLEN('paiclick-'.$list['pid']);
    if(!empty($heavy_click)){
        $heavy_num = $heavy_click;
    }else{
        $heavy_num = 0;
    }
    // //按广告位算 独立 ip
    $adzone_ip = $redis->HLEN('separate-ip-'.$list['adz_id']);
    
    //按站长算 排重点击数
    $web_click_uid = $redis->HLEN('web-click-uid-'.$list['uid']);
    if(!empty($web_click_uid)){
        $web_click_num = $web_click_uid;
    }else{
        $web_click_num = 0;
    }
    //按广告位算 排重点击数
    $adz_click_id = $redis->HLEN('adz-click-id-'.$list['adz_id']);
    if(!empty($adz_click_id)){
        $adz_click_num = $adz_click_id;
    }else{
        $adz_click_num = 0;
    }
    if($list['views']>'0'){
        if($per_num==0){
            $insert_sql[$sql_num] = 'INSERT INTO lz_stats_log (uid,pid,ad_id,tc_id,adv_id,adtpl_id,site_id,adz_id,sumprofit,sumpay,sumadvpay,views,day,web_deduction,adv_deduction,web_num,adv_num,uv_web,ui_web,ui_adzone,web_click_num,adz_click_num,heavy_click_num,click_num,plan_type,ctime) VALUES ';
        }
        
        $insert_sql[$sql_num].= "(".$list['uid'].",".$list['pid'].",".$list['ad_id'].",".$list['tc_id'].",".$list['adv_id'].",".$list['adtpl_id'].",".$list['site_id'].",".$list['adz_id'].",".$list['sumprofit'].",".$list['sumpay'].",".$list['sumadvpay'].",".$list['views'].",'".$list['day']."',".$list['web_deduction'].",".$list['adv_deduction'].",".$list['web_num'].",".$list['adv_num'].",".$list['uv_web'].",".$list['ui_web'].",".$adzone_ip.",".$web_click_num.",".$adz_click_num.",".$heavy_num.",".$list['click_num'].",'".$list['plan_type']."',".$time."),";

        if($per_num==3000){
            $per_num=0;
            $sql_num++;
        }else{
            $per_num++;
        }
    }
}
unset($final_res);

// echo microtime();
// echo '----','4',"\n";
// echo memory_get_usage();
// echo '----','4入库',"\n";

try{
    //net  write
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

$log_test_file = 'sql.log';


//入库
foreach ($insert_sql as $key => $value) {
    if(empty($value)){
        continue;
    }
    $value = rtrim($value,',');
    $value = $value.';';
    // $log_test_str = $value;
    // writeFile($log_test_file,$log_test_str);
    $insert = $pdo->prepare(''.$value.'');
    $insert->execute();
}
unset($insert_sql);

echo microtime();
echo '----','5',"\n";
echo memory_get_usage();
echo '----','5入库完成',"\n";


$pdo=null;
exit;