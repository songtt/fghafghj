<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="renderer" content="webkit">
</head>
<body>
<script src='show.js'></script>


<?php 
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

error_reporting(0);
date_default_timezone_set('PRC');//校正时间
error_reporting(0);
require_once  __DIR__ . '/17monipdb/Ipsearch.class.php';
$IpSearch = new IpSearch(__DIR__.'/qqzeng-ip-utf8.dat');
$user_IP = GetIp();
$ipInfos = GetIpLookup($user_IP,$IpSearch);

//获取手机操作系统
$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

$is_pc = (stripos($agent, 'windows nt')) ? true : false;
$is_iphone = (stripos($agent, 'iphone')) ? true : false;
$is_ipad = (stripos($agent, 'ipad')) ? true : false;

if($is_pc||$is_iphone||$is_ipad){
    echo 'error';
    exit;
}

$model = model();

$uid = $_GET['uid'] ;
if(empty($uid)){
    exit();
}

$log_file = "./testipyy.log";
$log_str = 'uid:'.$uid.'---ip:'.$user_IP.'--地域:'.$ipInfos['2'].",".$ipInfos['3'].'--手机型号：'.$model."\n";
writeFile($log_file,$log_str);


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
?>
</body>
</html>