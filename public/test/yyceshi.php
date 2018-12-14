<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="renderer" content="webkit">
</head>
<body>

<?php 

error_reporting(0);
date_default_timezone_set('PRC');//校正时间

require_once  __DIR__ . '/../ad/17monipdb/Ipsearch.class.php';
$IpSearch = new IpSearch(__DIR__.'/../ad/qqzeng-ip-utf8.dat');
$user_IP = GetIp();
$ipInfos = GetIpLookup($user_IP,$IpSearch);
$model = strtolower(model());
$uid = $_GET['uid'];
$date = time();
if(empty($uid)){
    echo '需要站长id参数.';
    exit;
}

$area = $ipInfos['2']."-".$ipInfos['3'];

$ispcflag = ispc();

//这是主库 不要搞错了，写是主库，读是从库,这里是写所以是主库
$db_link='mysql:host=127.0.0.1;dbname=lezun;port=3306';
$db_root='root';
$db_password='xya197a3321';

try{
    $savePdo = new PDO($db_link,$db_root,$db_password);
    $savePdo->query("set names utf8");
    $savePdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

if($ispcflag==1){

    try {
        $userNum = $savePdo->prepare("SELECT * FROM lz_area_ip_stat WHERE uid = ? and area = ? and model = ? and type = ?");
        $userNum->execute(array($uid, $area,$model,1));
        $userNum_arr = $userNum->fetchAll();
        if(!empty($userNum_arr)){
            $updata_sql = $savePdo->prepare("UPDATE lz_area_ip_stat set num=num+1,stime = $date WHERE uid = ? and area = ? and model = ? and type = ?");
            $updata_sql->execute(array($uid, $area, $model,1));
        }else{
            $sql = ' INSERT INTO lz_area_ip_stat (uid,ip,area,model,stime,num,type) value(?,?,?,?,?,?,?) ';
            $advUpdate = $savePdo->prepare($sql);
            $advUpdate->execute(array($uid,$user_IP,$area,$model,$date,1,1));
        }
        $savePdo = null;
    } catch (Exception $e) {
        echo '需要在手机端打开.';
    }

    echo '需要在手机端打开';
    exit;
}

if($ispcflag==2) {
    try {
        $userNum = $savePdo->prepare("SELECT * FROM lz_area_ip_stat WHERE uid = ? and area = ? and model = ? and type = ?");
        $userNum->execute(array($uid, $area,$model,2));
        $userNum_arr = $userNum->fetchAll();
        if(!empty($userNum_arr)){
            $updata_sql = $savePdo->prepare("UPDATE lz_area_ip_stat set num=num+1,stime = $date WHERE uid = ? and area = ? and model = ? and type = ?");
            $updata_sql->execute(array($uid, $area, $model,2));
        }else{
            $sql = ' INSERT INTO lz_area_ip_stat (uid,ip,area,model,stime,num,type) value(?,?,?,?,?,?,?) ';
            $advUpdate = $savePdo->prepare($sql);
            $advUpdate->execute(array($uid,$user_IP,$area,$model,$date,1,2));
        }
        $savePdo = null;
    } catch (Exception $e) {
        echo '需要在安卓手机上打开.';
    }

    echo '需要在安卓手机上打开';
    exit;
}

$userNum = $savePdo->prepare("SELECT * FROM lz_area_ip_stat WHERE uid = ? and area = ? and model = ? and type = ?");
$userNum->execute(array($uid, $area,$model,3));
$userNum_arr = $userNum->fetchAll();
if(!empty($userNum_arr)){
    $updata_sql = $savePdo->prepare("UPDATE lz_area_ip_stat set num=num+1,stime = $date WHERE uid = ? and area = ? and model = ? and type = ?");
    $updata_sql->execute(array($uid, $area, $model,3));
}else{
    $sql = ' INSERT INTO lz_area_ip_stat (uid,ip,area,model,stime,num,type) value(?,?,?,?,?,?,?) ';
    $advUpdate = $savePdo->prepare($sql);
    $advUpdate->execute(array($uid,$user_IP,$area,$model,$date,1,3));
}
$savePdo = null;
function  ispc(){
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    //分析数据
    $is_pc = (stripos($agent, 'windows nt')) ? true : false;
    $is_iphone = (stripos($agent, 'iphone')) ? true : false;
    $is_ipad = (stripos($agent, 'ipad')) ? true : false;
    $is_android = (stripos($agent, 'android')) ? true : false;
    // $is_wp = (stripos($agent, 'wp')) ? true : false;
    $is_wp = (stripos($agent, 'micromessenger')) ? true : false;  //微信

    if($is_wp){
        $mobile = 'wp';  //微信
    }else{
        if ($is_iphone||$is_ipad) {
            $mobile = 'ios';
        }elseif($is_pc){
            $mobile = 'pc';
        }elseif($is_wp){
            $mobile = 'wp';  //微信
        }elseif($is_android){
            $mobile = 'android';
        }else{
            $mobile = 'pc';
        }
    }
    if('pc'==$mobile){
        return 1;
    }elseif('ios'==$mobile){
        return 2;
    }else{
        return 3;
    }
}

//统计专用写日志
function writeFile($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
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

//根据用户ip获取地理位置
function GetIpLookup($ip = '',$IpSearch)
{
    header("Content-Type: text/html;charset=utf-8");
    $res = $IpSearch->get($ip);
    if(!empty($res)){
        $res = explode('|', $res);
    }else{
        $res = array(
            0 => '',
            1 => '',
            2 => '',
            3 => '',
            4 => '',
        );
    }
    return $res;
}



//获取手机型号
function model()
{
    //获取手机系统及型号
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    //返回值中是否有Android这个关键字
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'Android') && stristr($user_agent, 'Build')) {
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        if(empty($sub_results)){
            $sub_results = 'Android';
        }
        return $sub_results;   //返回手机型号
    }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Android')){
        return 'Android';
    }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'iPhone') || stristr($_SERVER['HTTP_USER_AGENT'], 'iPad') || stristr($_SERVER['HTTP_USER_AGENT'], 'iPod')){
        $sub_end = stristr($user_agent, 'ike',true); //截取like之前的字符串
        $sub_start = strripos($sub_end,'CPU') + 4; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'l'); //L最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        if(empty($sub_results)){
            $sub_results = 'iPhone';
        }
        return $sub_results;   //返回手机型号
    }else{
        return 'win系统';
    }

}



?>

<script src='show.js'></script>

</body>
</html>