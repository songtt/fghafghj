<?php
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

require_once __DIR__ . '/ad/redis.php';
$redis = new Redisutil();
//redis切库
$week = date('w');
if(date('Y-m-d') != '2018-01-03'){
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

//数据库连接信息
$db_link = $redis->db_link;
$db_root = $redis->db_root;
$db_password = $redis->db_password;
$pdo = new PDO($db_link,$db_root,$db_password);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->exec('SET NAMES utf8');
$day = date("Y-m-d");
$time = time();
//判断参数是否为数值类型
if(is_numeric($_GET['adz_id'])){
    if(!empty($_GET['adz_id']) && !empty($_GET['event_type'])){
        $adz_id = $_GET['adz_id'];
        //二次点击类型
        $event_type = $_GET['event_type'];
        if($event_type === 'A'){
            $event_type = 'A';
        }elseif($event_type === 'B'){
            $event_type = 'B';
        }elseif($event_type === 'C'){
            $event_type = 'C'; 
        }elseif($event_type === 'D'){
            $event_type = 'D';
        }elseif($event_type === 'E'){
            $event_type = 'E';
        }elseif($event_type === 'F'){
            $event_type = 'F';
        }else{
            echo '请求参数不是指定事件类型';exit;
        }
        //用户ip
        $ip = GetIp();
        //存入redis   同事件类型下点击次数
        $redis->handler()->HINCRBY('two_click_type',$adz_id.'-'.$event_type.'-'.$day,1);
        $key = $adz_id.'-'.$event_type.'-'.$day;
        $two_clickNum = $redis->handler()->HMGET('two_click_type',array($key));
        // 同事件类型下 ui 排重
        $ui_twoclick = $redis->handler()->HMGET('ui_twoclick'.$adz_id.'-'.$event_type.'-'.$day,array($userip));
        if(empty($ui_twoclick[$ip])){
            $ip = array(
                $ip => 1,
            );
            $redis->handler()->HMSET('ui_twoclick'.$adz_id.'-'.$event_type.'-'.$day,$ip);
        }
        $ui_num = $redis->handler()->HLEN('ui_twoclick'.$adz_id.'-'.$event_type.'-'.$day);
        //插入数据库
        $sql = $pdo->prepare('SELECT id FROM lz_two_click WHERE adz_id=? AND event_type=? AND day=?');
        $sql->execute(array($adz_id,$event_type,$day));  
        $res = $sql->fetchAll();
        //lz_two_click为空插入，反之更新
        if(empty($res)){
            $prep= $pdo->prepare('INSERT INTO lz_two_click (adz_id,event_type,click_num,ui_num,day,ctime) VALUES (?,?,?,?,?,?)');
            $prep->execute(array($adz_id,$event_type,$two_clickNum[$key],$ui_num,$day,$time));
        }else{
            $prep= $pdo->prepare('UPDATE lz_two_click SET click_num=?,ui_num=? WHERE id=?');
            $prep->execute(array($two_clickNum[$key],$ui_num,$res[0]['id']));
        }
        if($prep == true){
            echo '请求成功!';
        }
    }else{
        echo '请求不能为空';
    }
}else{
    echo '请求参数广告位id不是数值类型';
}


//获取用户 IP
function GetIp()
{
    $realip = '';
    $unknown = 'unknown';
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($arr as $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } else if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $realip = $_SERVER['REMOTE_ADDR'];
        } else {
            $realip = $unknown;
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)) {
            $realip = getenv("REMOTE_ADDR");
        } else {
            $realip = $unknown;
        }
    }
    $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
    return $realip;
}

