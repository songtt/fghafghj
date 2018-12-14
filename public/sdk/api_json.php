<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

error_reporting(0);
date_default_timezone_set('PRC');//校正时间

$start_timea = microtime(true);

require_once __DIR__ . '/../ad/17monipdb/Ipsearch.class.php';
$IpSearch = new IpSearch('../ad/qqzeng-ip-utf8.dat');
require_once __DIR__ . '/../ad/sredis.php';
$sredis = new Redisutil($connectParam);
//redis切库
$week = date('w');
switch($week){
    case 1:
        $sredis->select(1);
        break;
    case 2:
        $sredis->select(2);
        break;
    case 3:
        $sredis->select(3);
        break;
    case 4:
        $sredis->select(4);
        break;
    case 5:
        $sredis->select(5);
        break;
    case 6:
        $sredis->select(6);
        break;
    case 0:
        $sredis->select(7);
}

//log slog
$log_test_file = 'slog.txt';
$log_test_str = ''.date('Y-m-d H:i:s').'';

//获取参数
header('Content-Type:application/json; charset=utf-8');
$data_json = file_get_contents('php://input');
//解密成数组格式
$iv = "16-Bytes--String";//16位
$privateKey = '2bd83b239a4140d6';//16位
$re_c = openssl_decrypt(base64_decode($data_json),"AES-128-CBC",$privateKey,OPENSSL_RAW_DATA,$iv);
$data_json = json_decode($re_c);

//end
$type = $data_json->type;//请求类型
$appId = $data_json->appId;
$id = $data_json->placementId;//广告位id
$IMEI = $data_json->IMEI;
$androidId = $data_json->androidId;//类似IMEI
$packageName = $data_json->packageName;//应用包名
// $user_IP = $data_json->ip;
$mac = $data_json->mac;
$time = $data_json->time;//请求时间
$latitude = $data_json->latitude;//纬度
$longitude = $data_json->longitude;//经度
$deviceBrand = $data_json->deviceBrand;//手机品牌
$deviceModel = $data_json->deviceModel;//手机型号
$deviceType = $data_json->deviceType;//设备类型 0：手机； 1：平板
$os = $data_json->os;//系统 1:Android 2:ios
$osVersion = $data_json->osVersion;//系统版本
$appVersion = $data_json->appVersion;//应用版本
$carrier = $data_json->carrier;//运营商 46000：移动，46001：联通，46003：电信，46020：铁通
$network = $data_json->network;//网络类型 0：未知，1：Ethernet，2：WIFI，3：2G，4—3G，5：4G
$sdkVersion = $data_json->sdkVersion;//sdk版本号
$screenWidth = $data_json->screenWidth;//屏幕宽度
$screenHeight = $data_json->screenHeight;//屏幕高度
$screenDensity = $data_json->screenDensity;//屏幕ppi



//ua
$useragent = $_SERVER['HTTP_USER_AGENT'];
//手机型号,设置为全局变量，在计划筛选中用到
global $modle;
$modle = model($useragent);
//获取手机操作系统（android为3 ios为2），设置为全局变量，在计划筛选中用到
global $mobile;
if(stristr($useragent, 'iPhone') || stristr($useragent, 'iPad')){
    $mobile = 2;
}else{
    $mobile = 3;
}
if(empty($useragent) || empty($id) || empty($IMEI)){
// echo '参数错误';exit;
    $data = array(
        'error' => true,
        'errorCode' => '3003',
        'errorMessage' => '参数错误',
        'type' => $type,
        'data' => '',
    );
    echo encrypt($data);
    exit;
}

//ip地理位置
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
$pdo->exec('set names utf8');

//全局服务器配置地址
$globalSql = $pdo->prepare("SELECT img_server,js_server,jump_server,adv_server,domain_limit FROM lz_setting");
$globalSql->execute();
$globalRes = $globalSql->fetchAll();

if (empty($globalRes[0])) {
    $log_test_str.=' ERROR10000'."\n";
    writeFile($log_test_file,$log_test_str);
    // echo('服务器地址配置错误');
    // exit;
        $data = array(
        'error' => true,
        'errorCode' => '3004',
        'errorMessage' => '服务器地址配置错误',
        'type' => $type,
        'data' => '',
    );
    echo encrypt($data);
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

$adzLimitSql = $pdo->prepare("SELECT adz_id,uid,htmlcontrol,checkadz,minutes,adtpl_id,width,height,class_id,plan_class_allow FROM lz_adzone WHERE adz_id=?  AND status=1");
$adzLimitSql->execute(array($id));
$adzList = $adzLimitSql->fetchAll();

if (empty($adzList[0])) {
    $log_test_str.=' 广告位id'.$id.' ERROR10001'."\n";
    writeFile($log_test_file,$log_test_str);
    // echo('广告位没有激活');
    // exit;
    $data = array(
        'error' => true,
        'errorCode' => '3002',
        'errorMessage' => '广告位没有激活',
        'type' => $type,
        'data' => '',
    );
    echo encrypt($data);
    exit;
}

//查询出该广告位所有可展示的广告
$ad_id_arr = $pdo->prepare("SELECT ad_id FROM lz_ads WHERE tpl_id=? AND width=? AND height=? AND status=?");
$ad_id_arr->execute(array($adzList[0]['adtpl_id'],$adzList[0]['width'],$adzList[0]['height'],1));
$ad_id_arr = $ad_id_arr->fetchAll();
//将查询出来的数据转换为字符串
$ad_id = '';
foreach($ad_id_arr as $key=>$value){
    $ad_id = $ad_id.','.$value['ad_id'];
}

$ad_id = substr($ad_id,1);
if (empty($ad_id)) {
    // echo('无可展示的广告');
    // exit;
        $data = array(
        'error' => true,
        'errorCode' => '3005',
        'errorMessage' => '无可展示的广告',
        'type' => $type,
        'data' => '',
    );
    echo encrypt($data);
    exit;
}
$ad_id = rtrim($ad_id,',');
//广告位属于哪个站长id的
if (!empty($adzList[0]['uid'])) {
    $uid = $adzList[0]['uid'];
} else {
    $log_test_str.=' ERROR10005'."\n";
    writeFile($log_test_file,$log_test_str);
    // echo('该广告位没有所属站长');
    // exit;
    $data = array(
        'error' => true,
        'errorCode' => '3006',
        'errorMessage' => '该广告位没有所属站长',
        'type' => $type,
        'data' => '',
    );
    echo encrypt($data);
    exit;
}

// 查看当前域名属于哪个站长的
$userSql = $pdo->prepare("SELECT uid,domain_limit FROM lz_users WHERE uid=? ");
$userSql->execute(array($uid));
$userRes = $userSql->fetchAll();

// 该站长是否开启域名限制   0 默认 1 开启  2关闭 （默认就根据全局限制）
if ($userRes[0]['domain_limit'] == 0) {

    //全局  开启域名限制，则只有审核的网站可以显示广告 0 关闭 1开启
    if ($globalRes[0]['domain_limit'] == 0) {
        $siteRes = openDomainLimit($uid, $adsthisUrl, $pdo);
        //当前网站是否存在，不存在为空
        if (empty($siteRes)) {
            $siteRes[0] = array(
                "site_id" => 0,
                "class_id" => 0,
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
            // echo('该网站没有添加，域名被限制');
            // exit;
            $data = array(
                'error' => true,
                'errorCode' => '3007',
                'errorMessage' => '该网站没有添加，域名被限制',
                'type' => $type,
                'data' => '',
            );
            echo encrypt($data);
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
        // echo('该网站没有添加，域名被限制');
        // exit;
            $data = array(
                'error' => true,
                'errorCode' => '3007',
                'errorMessage' => '该网站没有添加，域名被限制',
                'type' => $type,
                'data' => '',
            );
            echo encrypt($data);
            exit;
    }
    $domain_type = 1;
} else {
    $siteRes = openDomainLimit($uid, $adsthisUrl, $pdo);
    //当前网站是否存在，不存在为空
    if (empty($siteRes)) {
        $siteRes[0] = array(
            "site_id" => 0,
            "class_id" => 0,
            "uid" => 0,
            "siteurl" => 0,
            "web_deduction" => 0,
            "adv_deduction" => 0,
            'site_cnzz_id' => '',
        );
    }
    $domain_type = 2;
}

$limit_pid = '0';

// 用户所在省份和城市
$user_city = $ipInfos[2] . '-' . $ipInfos[3];

//得到激活ad的集合
$ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,b.pid,b.restrictions,
b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id,b.run_terminal FROM lz_ads AS a
LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON a.uid = c.uid WHERE a.ad_id
IN (' . $ad_id . ') AND b.status = 1 AND a.status =1 AND c.money > c.adv_money AND b.delay_show_status !=1');
$ad_sql->execute(array());
$ad_sql_list = $ad_sql->fetchAll();
//判断redis里面，本次访问的用户是否访问过此计划id
$user_pid = $sredis->handler()->HMGET($user_IP,array('pid'))['pid'];

//得到正常显示的pid
$normal_pid_array = array();
foreach ($ad_sql_list as $key => $val){
    if(($ad_sql_list[$key]['run_terminal'] == $mobile) || ($ad_sql_list[$key]['run_terminal'] == '0')){
        $normal_pid_array[$val['pid']] = $val['pid'];
    }
}

$redis_pid_arr = array_flip(explode(',', $user_pid));
//判断已展示的pid 和全部pid 对比返回不同的pid
$delay_inter_adids = array_diff_key($normal_pid_array,$redis_pid_arr);
//读取延迟展示情况下每次加一的值
$delay_show_num = $sredis->handler()->HMGET($user_IP.'_delay_show_pid',array('delay_show_num'))['delay_show_num'];
//读取延迟展示的总个数
$delay_show_count = $sredis->handler()->GET($user_IP.'_delay_show_count');
//不限计划延迟也显示
if ($mobile == 2) {
    $mobile = '0,2';
}else{
    $mobile = '0,3';
}

//判断延迟展示计划是否展示完毕
if(($delay_show_num < $delay_show_count) && !empty($delay_show_count)  && !empty($delay_show_num)){
    //如果广告都被展示，则重新来
    if (empty($delay_inter_adids)) {
        //得到激活ad的集合
        $ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,
        b.pid,b.restrictions,b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id
        FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON
         a.uid = c.uid WHERE a.ad_id IN (' . $ad_id . ') AND b.run_terminal IN('.$mobile.') AND b.status = 1
         AND a.status =1 AND c.money > c.adv_money AND b.delay_show_status = 1');
        $ad_sql->execute(array());
        $delay_ad_sql_list = $ad_sql->fetchAll();
        if (!empty($delay_ad_sql_list)) {
            $ad_sql_list = $delay_ad_sql_list;
            //得到延迟显示的pid
            $delay_pid_array = array();
            foreach ($ad_sql_list as $key => $val) {
                $delay_pid_array[$val['pid']] = $val['pid'];
            }
            $delay_show_count = count($delay_pid_array) + 1;
            //存延迟展示的总个数
            $sredis->handler()->SET($user_IP . '_delay_show_count', $delay_show_count);
            //只在延迟展示情况下 每次加 1
            $sredis->handler()->HINCRBY($user_IP . '_delay_show_pid', 'delay_show_num', 1);
        }
    }
}else{
    //正常显示的计划为空
    if(empty($normal_pid_array)){
        //得到激活 延迟计划的 ad的集合
        $ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,
            b.pid,b.restrictions,b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id
            FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON
             a.uid = c.uid WHERE a.ad_id IN (' . $ad_id . ') AND b.run_terminal IN('.$mobile.') AND b.status = 1
             AND a.status =1 AND c.money > c.adv_money AND b.delay_show_status = 1');
        $ad_sql->execute(array());
        $ad_sql_list = $ad_sql->fetchAll();
    }

    $limit_pid_sql = $pdo->prepare('SELECT pid FROM lz_plan WHERE run_terminal IN('.$mobile.')
    AND status = 1 AND delay_show_status = 1 Limit 1');
    $limit_pid_sql->execute(array());
    $delay_limit_list = $limit_pid_sql->fetchAll();
    if(!empty($delay_limit_list)){
        //初始化 总个数值
        $sredis->handler()->SET($user_IP.'_delay_show_count',2);
        $delay_show_num = array(
            'delay_show_num' => 1,
        );
        //初始化
        $sredis->handler()->HMSET($user_IP.'_delay_show_pid',$delay_show_num);
    }
}
//广告位分类id
$adz_class_id = $adzList[0]['class_id'];
//广告位里面的计划分类
$adz_plan_class_allow = $adzList[0]['plan_class_allow'];
$i = 0;
foreach ($ad_sql_list as $key => $val) {

    if (!empty($ad_sql_list[$i]['pid'])) {

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
    if ($checkplan['expire_date']['isdate'] == 0 && $lim_uid_status == 0 && $checkplan['city']['isacl'] == 0 && $checkplan['week']['isacl'] == 0 && $checkplan['sitelimit'] == 0 && $checkplan['siteclass']['isacl'] == 0 && $checkplan['mobile']['isacl'] == 0 && $checkplan['run_model']['isacl'] == 0 && $checkplan['adzclass']['isacl'] == 0 && $checkplan['class_id'] == 0) {
    } else {
        $time = strtotime(date('Y-m-d', time()));
        //结束时间 转时间戳
        $expireTime = strtotime($checkplan['expire_date']['year'] . '-' . $checkplan['expire_date']['month'] . '-' . $checkplan['expire_date']['day']);

        //时间状态为 0时，则没有结束时间限制
        if ($checkplan['expire_date']['isdate'] == 0) {
            // 站长限制 2级
            lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $uid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        } elseif (($expireTime - $time) >= 0 && $checkplan['expire_date']['isdate'] == 1) {
            // 站长限制 2级
            lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $uid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

            //时间过期
        } else {
            $xianzhi_log_test=' 投放限制-结束日期不满足';

            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);
        }
    }
    $i++;
}

// 站长限制 2级
function lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $uid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    global $log_test_str ;

    $panduan = in_array($uid, $lim_uid_array,true);

    // 0 不限制 1 允许 2 屏蔽
    if ($lim_uid_status == 0) {
        // 投放地域 3级
        lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        //允许以下站长id (包含当前广告位对应的站长)
    } elseif ($lim_uid_status == 1 && in_array($uid, $lim_uid_array,true)) {
        // 投放地域 3级
        lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        //允许以下站长（不包含当前广告位对应的站长）
    } elseif ($lim_uid_status == 1 && !$panduan) {

        // //不满足条件的存cookies 存pid
        cookie_limit_pid($ad_sql_list, $i);

    } else {
        //屏蔽以下站长（不包含当前广告位对应的站长）
        if ($lim_uid_status == 2 && !in_array($uid, $lim_uid_array,true)) {
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
    if (!empty($checkplan['city']['province'])) {
        for ($a = 0; $a < count($checkplan['city']['province']); $a++) {
            for ($b = 0; $b < count($checkplan['city']['data']); $b++) {
                $provincial_city[] = $checkplan['city']['province'][$a] . '-' . $checkplan['city']['data'][$b];
            }
        }
    } else {
        $provincial_city = '';
    }

    //投放地区限制 0 不限制 1 选择区域
    if ($checkplan['city']['isacl'] == 0) {
        // 周期日程 4级
        lim_week_methods($checkplan, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

    } else {
        //选择区域 1 允许 0 拒绝 包含此地区
        if ($checkplan['city']['comparison'] == 1 && @in_array($user_city, $provincial_city,true)) {
            // 周期日程 4级
            lim_week_methods($checkplan, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

            //选择区域 1 允许 不包含此地区
        } elseif ($checkplan['city']['comparison'] == 1 && @!in_array($user_city, $provincial_city,true)) {

            $xianzhi_log_test=' 定向设置-投放地域不满足';
            //不满足条件的存cookies 存pid
            cookie_limit_pid($ad_sql_list, $i);

            //选择区域 0 拒绝 不包含此地区
        } elseif ($checkplan['city']['comparison'] == 0 && @!in_array($user_city, $provincial_city,true)) {

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

// 投放设备设置  7级
function lim_mobile_methods($checkplan, $ad_sql_list, $i, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $mobile;
    if($mobile == '0,2' || $mobile == 'ios'){
        $mobile = 'ios';
    }else{
        $mobile = 'android';
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
// 数组转字符串存cookies

$limit_pid = isset($limit_pid) ? $array_pid : 0;

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

$array_pid = array_flip($limit_pid);

//数组key对比（不满足条件的pid 和 全部pid 对比）
$contrast_arr = array_diff_key($pidarr_sel['plan'], $array_pid);

// 对比为空说明广告对应的计划都不满足条件
if (empty($contrast_arr)) {
    $log_test_str.= $xianzhi_log_test;
    $log_test_str.= ' '.implode(',', $limit_pid);
    $log_test_str.= ' id-'.$id;

    $log_test_str.=' ERROR10007'."\n";
    writeFile($log_test_file,$log_test_str);
    // echo('所有计划限制都不满足，无可投放的广告');
    // exit;
        $data = array(
            'error' => true,
            'errorCode' => '3008',
            'errorMessage' => '所有计划限制都不满足，无可投放的广告',
            'type' => $type,
            'data' => '',
        );
        echo encrypt($data);
        exit;
}else{
    // 在赋给原值
    $pidarr_sel['plan'] = $contrast_arr;
}
$pid_adid_sel_arr = array();

function get_rands($proArr)
{
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);
    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);             //抽取随机数
        if ($randNum <= $proCur) {
            $result = $proCur;                         //得出结果
            break;
        } else {
            $proSum -= $proCur;
        }
    }

    unset ($proArr);
    return $result;
}
    $final_pid = cookie_checker_plan($pidarr_sel['plan'],$sredis,$user_IP);
    if(empty($final_pid)){
        $log_test_str.=' ERROR10008'."\n";
        writeFile($log_test_file,$log_test_str);
        // echo('所有计划限制都不满足，无可投放的广告');
            $data = array(
                'error' => true,
                'errorCode' => '3008',
                'errorMessage' => '所有计划限制都不满足，无可投放的广告',
                'type' => $type,
                'data' => '',
            );
        echo encrypt($data);
        exit;
    }
//  ----
function rand_jh($pidarr_sel,$sredis,$user_IP,$data_json,$privateKey,$iv,$pdo,$id,$adzList,$androidId){
    global  $log_test_file;
    global $log_test_str;
    global $id;
    //开始
    //$pidarr_sel; 符合广告位的 所有计划 - 所有广告
    $plan_id = array_keys($pidarr_sel['plan']); //一维数组
    foreach ($pidarr_sel['ads'] as $key => $value) {
        $ads_id_arr[] = array_keys($value);
    }
    foreach ($ads_id_arr as $key => $value) {
        foreach ($value as $k => $v) {
            $ads .= $v.',';
        }
    }
    foreach ($plan_id as $key => $value) {
        $plan_ids .= $value.',';
    }
    //$plan_ids 是符合广告位的计划ID逗号分隔
    //$ads 是符合广告位的广告ID 逗号分隔
    //天才第一步 -- 判断广告位是什么类型
    $ads =  substr($ads,0,strlen($ads)-1);
    $plan_ids = substr($plan_ids,0,strlen($plan_ids)-1);
    $adz_re = $pdo->prepare("SELECT adz_id,rouse,download_status FROM lz_adzone WHERE adz_id = '$id'");
    $adz_re->execute();
    $adz_re_data = $adz_re->fetch();
    //下载池
    //唤醒优先数据
    $rouse_list = $pdo->prepare("SELECT id,type,bao_name,bao_url,weight  FROM lz_rouse WHERE type = '1'");
    $rouse_list->execute();
    $rouse_list_re = $rouse_list->fetchAll();
    //下载优先数据
    $download_list = $pdo->prepare("SELECT id,type,bao_name,bao_url,weight  FROM lz_rouse WHERE type = '2'");
    $download_list->execute();
    $download_list_re = $download_list->fetchAll();

    $appname = $pdo->prepare("SELECT id,appname,androidId FROM lz_appname WHERE androidId = ?");
    $appname->execute(array($androidId));
    $appname_res = $appname->fetchAll();
    //去除 该安卓用户点击过的所有广告-如果全部点击过那么就清空该用户的点击广告字段值
    $androidId_ad = $pdo->prepare("SELECT id,uid,ad_id FROM lz_user_ads WHERE uid = ?");
    $androidId_ad->execute(array($androidId));
    $androidId_ad_res = $androidId_ad->fetch();
    $androidId_ad_s = explode(',', $androidId_ad_res['ad_id']);
    // $ads = explode(',', $ads);
    // foreach ($ads as $key => $value) {
    //     foreach ($androidId_ad_s as $k => $v) {
    //         if($v == $value){
    //             unset($ads[$key]);
    //         }
    //     }
    // }
    // if(!$ads){
        //不为真的时候证明 该安卓用户以访问所有的广告，清空该字段
        // $stmt = $db->prepare("DELETE FROM lz_user_ads WHERE uid=:id");
        // $stmt->bindValue(':id', $androidId, PDO::PARAM_STR);
        // $stmt->execute();
        // $affected_rows = $stmt->rowCount();
        // dump($affected_rows);exit;
    // }
    //广告为真时， 那么证明还存在没有访问的广告
    // $ads = implode(',',$ads);
    if($adz_re_data['rouse'] == 1){  //1=唤醒 2=下载
        $site_data = explode(';', $appname_res[0]['appname']);
        //$siteRes  =   包名表    与查出所有的广告数据对比
        $ads_re = $pdo->prepare("SELECT  a.pid,a.rouse,a.status,b.ad_id,b.app_apply,b.priority,b.pid FROM lz_plan AS a LEFT JOIN lz_ads AS b  ON b.pid = a.pid  WHERE a.rouse = 1 AND a.pid IN ($plan_ids) AND a.status =1 AND b.ad_id IN($ads)");
        $ads_re->execute();
        $ads_res = $ads_re->fetchAll(); //唤醒 状态下 所有的计划和广告 符合广告位
        foreach ($ads_res as $k => $v) {
            foreach ($site_data as $key => $value) {
                if($v['app_apply'] == $value){   //唤醒计划下的广告包名与 包名表对比 匹配进入$e;
                        $e[] = $v['app_apply'] ;
                }
            }
        }
        if($e[0] != NULL){//天才第二步包名存在  --根据权重筛选
            foreach ($e as $key => $value) {
                $c[] = "'".$value."'";
            }
            $app_name = implode(',',$c);
            $ads_datas = $pdo->prepare("SELECT a.pid,a.rouse,b.ad_id,b.app_apply,b.priority FROM lz_plan AS a LEFT JOIN lz_ads AS b ON b.pid = a.pid WHERE b.app_apply IN($app_name) AND a.rouse = 1 AND a.status = 1 AND ad_id IN ($ads)");
            // $ads_datas = $pdo->prepare("SELECT ad_id,app_apply,priority FROM lz_ads WHERE app_apply IN($app_name)");
            $ads_datas ->execute();
            $ads_date_res = $ads_datas->fetchAll();
            foreach ($ads_date_res as $key => $value) {
                    $c[$key] = $value['ad_id'];
            }
            $re = get_rands($c);    //权重取值
            if($adz_re_data['download_status'] == 1){//已开启   唤醒并下载
                //包名与下载池对比
                $ads_bao = $pdo->prepare("SELECT ad_id,app_apply FROM lz_ads WHERE ad_id = $re");
                $ads_bao->execute();
                $ads_bao_s = $ads_bao->fetch();
                foreach ($download_list_re as $key => $value) {
                    if($ads_bao_s['app_apply'] != $value['bao_name']){   //存在符合的包名
                        $xiazai[$key]['name'] = $value['bao_name'];
                        $xiazai[$key]['id'] = $value['id'] ;
                    }
                }
                if($xiazai){ 
                   //包名存在
                    foreach ($xiazai as $key => $value) {
                        $cc[] = $value['id'];
                    }
                    $adss = array();
                    $adss['ad_id'] = $re;
                    $adss['bao'] = get_rands($cc);
                    return $adss;
                }
            }elseif ($adz_re_data['download_status'] ==2 ) {//未开启 唤醒并下载


                $adss = array();
                $adss['ad_id'] = $re;    //函数输出广告
                $adss['bao'] = '';
                return $adss;
            }
        }else{//天才第二步包名与下载计划中匹配
            $ads_download_re = $pdo->prepare("SELECT  a.pid,a.rouse,a.status,b.ad_id,b.app_apply,b.priority,b.pid FROM lz_plan AS a LEFT JOIN lz_ads AS b  ON b.pid = a.pid  WHERE a.rouse = 2 AND a.pid IN ($plan_ids) AND a.status =1 AND b.ad_id IN($ads)");
            $ads_download_re->execute();
            $ads_download_res = $ads_download_re->fetchAll();
            foreach ($ads_download_res as $k => $v) {
                foreach ($site_data as $key => $value) {
                    if($v['app_apply'] == $value){      //下载计划中的广告包名 与 包名一致 
                        $cc[$k]['id'] = $v['ad_id'] ; 
                    }else{                              //下载计划中的广告包名  与 包名不一致
                        $rr[$k]['id'] = $v['ad_id'] ;
                    }
                }
            }
            if($rr){    //存在值 不为空的情况下  -- 根据权重筛选
                foreach ($rr as $key => $value) {
                    $ads_id[] = $value['id'];
                }
                $ads = get_rands($ads_id);
                if($adz_re_data['download_status'] == 1){//开启
                //包名与下载池匹配
                    $ads_cc = $pdo->prepare("SELECT ad_id,app_apply FROM lz_ads WHERE ad_id = $ads");
                    $ads_cc->execute();
                    $ads_data_cc = $ads_cc->fetch();
                    foreach ($download_list_re as $key => $value) {
                        if($ads_data_cc['app_apply'] != $value['bao_name']){   //存在符合的包名
                            $xiazai[$key]['name'] = $value['bao_name'];
                            $xiazai[$key]['id'] = $value['id'] ;
                        }
                    }
                    if($xiazai){
                        //包名存在
                        foreach ($xiazai as $key => $value) {
                            $dd[] = $value['id'];
                        }
                        $adss = array();
                        $adss['ad_id'] = $ads;
                        $adss['bao'] = get_rands($dd);
                        return $adss;
                    }
                }else{      
                    $adss = array();                              //关闭
                    $adss['ad_id'] = $ads;
                    $adss['bao'] = '';
                    return $adss;
                }

            }else{      //不存在 根据权重筛从下载计划中取
                foreach ($cc as $key => $value) {
                    $ads_id[] = $value['id'];
                }          
                $ads = get_rands($ads_id);
                if($adz_re_data['download_status'] == 1){//开启
                //包名与下载池匹配
                    $ads_cc = $pdo->prepare("SELECT ad_id,app_apply FROM lz_ads WHERE ad_id = $ads");
                    $ads_cc->execute();
                    $ads_data_cc = $ads_cc->fetch();

                    foreach ($download_list_re as $key => $value) {
                        if($ads_data_cc['app_apply'] != $value['bao_name']){   //存在符合的包名
                            $xiazai[$key]['name'] = $value['bao_name'];
                            $xiazai[$key]['id'] = $value['id'] ;
                        }
                    }
                    if($xiazai){
                        foreach ($xiazai as $key => $value) {
                            $dd[] = $value['id'];
                        }
                        $adss = array();
                        $adss['ad_id'] = $ads;
                        $adss['bao'] = get_rands($dd);
                        return $adss;                        
                    }

                }else{            
                    $adss = array();                        //关闭
                    $adss['ad_id'] = $ads;
                    $adss['bao'] = '';
                    return $adss;
                }
            }
        }

    }elseif($adz_re_data['rouse'] == 2){   //下载优先
        //包名与下载计划中广告匹配
        $site_data = explode(';', $appname_res[0]['appname']);
        //$siteRes  =   包名表    与查出所有的广告数据对比
        $ads_re = $pdo->prepare("SELECT  a.pid,a.rouse,a.status,b.ad_id,b.app_apply,b.priority,b.pid FROM lz_plan AS a LEFT JOIN lz_ads AS b  ON b.pid = a.pid  WHERE a.rouse = 2 AND a.pid IN ($plan_ids) AND a.status =1 AND b.ad_id IN($ads)");
        $ads_re->execute();
        $ads_res = $ads_re->fetchAll();
        foreach ($ads_res as $k => $v) {
            foreach ($site_data as $key => $value) {
                if($v['app_apply'] == $value){   //下载计划下的广告包名与 包名表对比 匹配进入$e;
                        $e[] = $v['app_apply'] ;
                }
            }
        }
        if($e[0] != NULL){                       //下载计划下的广告包名 与 包名表 对比 匹配到
            foreach ($e as $key => $value) {
                $c[] = "'".$value."'";
            }
            $app_name = implode(',',$c);
            $ads_datas = $pdo->prepare("SELECT a.pid,a.rouse,b.ad_id,b.app_apply,b.priority FROM lz_plan AS a LEFT JOIN lz_ads AS b ON b.pid = a.pid WHERE b.app_apply IN($app_name) AND a.rouse = 2 AND a.status = 1 AND ad_id IN ($ads)");
            // $ads_datas = $pdo->prepare("SELECT ad_id,app_apply,priority FROM lz_ads WHERE app_apply IN($app_name)");
            $ads_datas ->execute();
            $ads_date_res = $ads_datas->fetchAll();
            foreach ($ads_date_res as $key => $value) {
                    $c[$key] = $value['ad_id'];
            }
            $re = get_rands($c);    //权重取值
            if($adz_re_data['download_status'] == 1){       //已开启   唤醒并下载
                //包名与唤醒池对比
                $ads_bao = $pdo->prepare("SELECT ad_id,app_apply FROM lz_ads WHERE ad_id = $re");
                $ads_bao->execute();
                $ads_bao_s = $ads_bao->fetch();
                foreach ($rouse_list_re as $key => $value) {
                    if($ads_bao_s['app_apply'] != $value['bao_name']){   //存在符合的包名
                        $xiazai[$key]['name'] = $value['bao_name'];
                        $xiazai[$key]['id'] = $value['id'] ;
                    }
                }
                if($xiazai){                                            //包名存在
                    foreach ($xiazai as $key => $value) {
                        $cc[] = $value['id'];
                    }
                    $adss = array();
                    $adss['ad_id'] = $re;
                    $adss['bao'] = get_rands($cc);
                    return $adss;
                }
            }elseif ($adz_re_data['download_status'] ==2 ) {//未开启 唤醒并下载
                $adss = array();
                $adss['ad_id'] = $re;    //函数输出广告
                $adss['bao'] = '';
                return $adss;
            }

        }else{                          //唤醒计划下的广告包名 与 包名表 对比 没有匹配到

            $ads_download_re = $pdo->prepare("SELECT  a.pid,a.rouse,a.status,b.ad_id,b.app_apply,b.priority,b.pid FROM lz_plan AS a LEFT JOIN lz_ads AS b  ON b.pid = a.pid  WHERE a.rouse = 2 AND a.pid IN ($plan_ids) AND a.status =1 AND b.ad_id IN($ads)");
            $ads_download_re->execute();
            $ads_download_res = $ads_download_re->fetchAll();
            foreach ($ads_download_res as $k => $v) {
                foreach ($site_data as $key => $value) {
                    if($v['app_apply'] == $value){      //下载计划中的广告包名 与 包名一致 
                        $cc[$k]['id'] = $v['ad_id'] ; 
                    }else{                              //下载计划中的广告包名  与 包名不一致
                        $rr[$k]['id'] = $v['ad_id'] ;
                    }
                }
            }
            if($rr){    //存在值 不为空的情况下  -- 根据权重筛选
                foreach ($rr as $key => $value) {
                    $ads_id[] = $value['id'];
                }
                $ads_id = get_rands($ads_id);
                if($adz_re_data['download_status'] == 1){//开启
                //包名与下载池匹配
                    $ads_cc = $pdo->prepare("SELECT ad_id,app_apply FROM lz_ads WHERE ad_id = $ads_id");
                    $ads_cc->execute();
                    $ads_data_cc = $ads_cc->fetch();
                    foreach ($download_list_re as $key => $value) {
                        if($ads_data_cc['app_apply'] != $value['bao_name']){   //存在符合的包名
                            $xiazai[$key]['name'] = $value['bao_name'];
                            $xiazai[$key]['id'] = $value['id'] ;
                        }
                    }
                    if($xiazai){
                        //包名存在
                        foreach ($xiazai as $key => $value) {
                            $dd[] = $value['id'];
                        }
                        $adss = array();
                        $adss['ad_id'] = $ads_id;
                        $adss['bao'] = get_rands($dd);
                        return $adss;
                    }
                }else{        
                    $adss = array();                 //关闭
                    $adss['ad_id'] = $ads_id;
                    $adss['bao'] = '';
                    return $adss;
                }

            }else{      //不存在 根据权重筛从下载计划中取

                foreach ($cc as $key => $value) {
                    $ads_id[] = $value['id'];
                }          
                $ads_id = get_rands($ads_id);
                if($adz_re_data['download_status'] == 1){//开启
                //包名与唤醒池匹配
                    $ads_cc = $pdo->prepare("SELECT ad_id,app_apply FROM lz_ads WHERE ad_id = $ads_id");
                    $ads_cc->execute();
                    $ads_data_cc = $ads_cc->fetch();
                    foreach ($rouse_list_re as $key => $value) {
                        if($ads_data_cc['app_apply'] != $value['bao_name']){   //存在符合的包名
                            $xiazai[$key]['name'] = $value['bao_name'];
                            $xiazai[$key]['id'] = $value['id'] ;
                        }
                    }
                    if($xiazai){
                        foreach ($xiazai as $key => $value) {
                            $dd[] = $value['id'];
                        }
                        $adss = array();
                        $adss['ad_id'] = $ads_id;
                        $adss['bao'] = get_rands($dd);
                        return $adss;                        
                    }

                }elseif($adz_re_data['download_status'] == 2){         
                                      //关闭
                    $adss = array();
                    $adss['ad_id'] = $ads_id;
                    $adss['bao'] = '';
                    return $adss;
                }
            }
        }            
    }
}
    //展示的广告信息
    // $List = advInformation($pdo,$id,$rand,$adzList,$Pid,$sredis);
    // return $List;
//调用去广告函数
$List_data = rand_jh($pidarr_sel,$sredis,$user_IP,$data_json,$privateKey,$iv,$pdo,$id,$adzList,$androidId);
//函数返回参数 1：广告ID 2：包名ID
//根据广告ID 查出该计划ID ，查出展示的广告
$rand = $List_data['ad_id'];    //广告ID

//包名池 ID
$rouse_id = $List_data['bao'];

$plan_id_s = $pdo->prepare("SELECT ad_id,pid,app_apply FROM lz_ads WHERE ad_id = '$rand' ");
$plan_id_s->execute();
$plan_id_ss = $plan_id_s->fetch();
$Pid = $plan_id_ss['pid'];      //计划ID
$List = advInformation($pdo,$id,$rand,$adzList,$Pid,$sredis);

//统计点击量  click_url
$baseUrl =  'blogid=' . $rand .'&siteid='.$siteRes[0]['site_id'].'&uid='.$adzList[0]['uid'].
    '&pid='.$List['pid'].'&userip='.$user_IP.'&tpl_id='.$List['tpl_id'].'&plantype='.$List['plantype'].'&planuid='.
    $List['planuid'].'&unique='.$IMEI.'&ua='.$useragent.'&os='.$os; //.'&androidId='.$androidId
$baseUrl = 'http://hmob.zqrwn.com/sdk/c.php?id='.$id.'&'. base64_encode($baseUrl);  //测试环境
// $baseUrl = 'http://hmob.com/sdk/c.php?id='.$id.'&'. base64_encode($baseUrl);      //线上环境
// 获取图片链接
if (!empty($List)) {
    // 把字符串 \ 转化 /
    if ($List['files'] == 1) {
        $List['imageurl'] = "$img_server" . str_replace('\\', '/', $List['imageurl']);
    } else {
        $List['imageurl'] = str_replace('\\', '/', $List['imageurl']);
    }
    $List['imageurl'] = str_replace('./', '/', $List['imageurl']);

}
//如果是https 把http换成https，图片域名
$http_type = is_HTTPS();
if($http_type==TRUE){
    if(strpos($List['imageurl'], 'img.joysroom') == true){
        if(strpos($List['imageurl'], 'https') == false){
            $List['imageurl'] = str_replace('http','https',$List['imageurl']);
        }
        $List['imageurl'] = str_replace('img.joysroom','wen.joysroom',$List['imageurl']);
    }
}
if (!empty($List)) {
    $statsParams = array(
        'adz_id' => $id,
        'ad_id'  => $rand,
        'pid'    => $List['pid'],
        'uid'    => $adzList[0]['uid'],
        'tc_id'  => $List['tc_id'],
        'tpl_id' => $List['tpl_id'],
        'plan_type' => $List['plan_type'],
        'planuid'   => $List['planuid'],
        'site_id'   => empty($siteRes) || empty($siteRes[0]['site_id']) ? 1 : $siteRes[0]['site_id'],
        'user_ip' => $user_IP,
        'unique' => $IMEI,
    );
    //计费链接
    $viewurl = 'ad_id='.$statsParams['ad_id'].'&adz_id='.$statsParams['adz_id'].'&pid='.$statsParams['pid'].
        '&uid='.$statsParams['uid'].'&tc_id='.$statsParams['tc_id'].'&tpl_id='.$statsParams['tpl_id'].
        '&planuid='.$statsParams['planuid'].'&site_id='.$statsParams['site_id'].'&user_ip='.$statsParams['user_ip'].
        '&plan_type='.$statsParams['plan_type'].'&unique='.$statsParams['unique'];

    $viewurl = 'http://hmob.zqrwn.com/sdk/v.php?'. base64_encode($viewurl);     //测试地址
    // $viewurl = 'http://hmob.com/sdk/v.php?'. base64_encode($viewurl);     //线上地址
}
//判断appid 是否包含广告位ID.
$ads_appid = $pdo->prepare("SELECT uid,appid FROM lz_users WHERE appid = '$appId'");
$ads_appid->execute();
$ads_appid_re = $ads_appid->fetchAll();
$u_id = $ads_appid_re[0]['uid'] ;
$check_data = $pdo->prepare("SELECT adz_id,uid FROM lz_adzone WHERE uid ='$u_id' and adz_id = '$id'");
$check_data->execute();
$check_data_re = $check_data->fetchAll();
if(!$check_data_re){
    $data = array(
        'error' => true,
        'errorCode' => '3001',
        'errorMessage' => 'appid与广告位不匹配',
        'type' => $type,
        'data' => '',
        );
    echo encrypt($data);
    exit;
}
if($List){  //判断是否随机出广告
    //判断该广告位 是否符合 请求数据限制的广告播放地域
    //如果符合，不显示附加功能，广告正常显示
    $adzoneOne_city = unserialize($List['address']);
    $data_city = implode(',',$adzoneOne_city['city']['data']);

    // if(!empty($ipInfos[3]) && !empty($data_city)){
        

    if($ipInfos[3] == $data_city){

        //一个是ip算出的地址，一个是广告位限制该地域 如果相等于不显示附加功能
            if($adzone_data['copy_clipboard_s'] ==1){
                $cr = 'true';
            }else{
                $cr = 'false';
            }

            $data = array(
                'error' => false,
                'errorCode' => 0,
                'errorMessage' => '',
                'type' => $type,
                'data' => array(
                    'reqId' => $data_json->reqId,
                    'placementId' => $data_json->placementId,
                    'image' => array(
                        $List['imageurl'],
                    ),
                    'content' => $List['adinfo'],
                    'displayTrack' => array(
                        $viewurl,
                    ),
                    'clickTrack' => array(
                        $baseUrl,
                    ),
                    'playTrack' => '',
                    'video' => array(
                        'type' => '',
                        'duration' => '',
                        'bitrate' => '',
                        'url' => '',
                    ),
                    'action' => $List['action'],
                    'link' => $List['url'],
                    'deepLink'=>$List['deep_url'],
                    'title' => $List['adname'],
                    "deepPackage"=> $List['app_apply'],
                    "clipboard"=>$List['copy_clipboard'],
                    "openExtention"=>false,
                ),
            );
            echo encrypt($data);
            exit;
      
        //如果该广告位 不属于限制的地域，执行以下
    }else{  
        //不限制区域
        //判断包名池ID 正确取值，不正确不取
        if($rouse_id){
            $baos = $pdo->prepare("SELECT id,bao_url,type FROM lz_rouse WHERE id ='$rouse_id'");
            $baos->execute();
            $bao_url = $baos->fetch();
            $bao = $bao_url['bao_url'];
            if($bao_url['type'] == 1){
                $types = 1;
            }elseif($bao_url['type'] == 2){
                $types = 0;
            }
        }else{
            $bao = '';
            $types = '';
        }
        //误点几率
        if(!$List['lose_min']){
            $lose_min_s = 'false';
            $lose_min = '';
        }else{
            $lose_min_s = 'true';
            $lose_min = $List['lose_min'];
        }
        //判断 广告位粘贴板是否为真
        if($List['copy_clipboard_s'] ==1){
            $cr = 'true';
        }else{
            $cr = 'false';
        }
        if($List['download_status'] == 1){
            $czx = 'true';
        }elseif($List['download_status'] == 2){
            $czx ='false';
        }
        if($List['fj_status'] == 1){
            $data = array(
            'error' => false,
            'errorCode' => 0,
            'errorMessage' => '',
            'type' => $type,
            'data' => array(
                'reqId' => $data_json->reqId,
                'placementId' => $data_json->placementId,
                'image' => array(
                    $List['imageurl'],
                ),
                'content' => $List['adinfo'],
                'displayTrack' => array(
                    $viewurl,
                ),
                'clickTrack' => array(
                    $baseUrl,
                ),
                'playTrack' => '',
                'video' => array(
                    'type' => '',
                    'duration' => '',
                    'bitrate' => '',
                    'url' => '',
                ),
                'action' => $List['action'],
                'link' => $List['url'],
                'deepLink'=>$List['deep_url'],
                'title' => $List['adname'],
                "deepPackage"=> $List['app_apply'],
                "clipboard"=>$List['copy_clipboard'],
                "openExtention"=>true,
                    "extention"=>array(
                        'awaken'=>array(
                            'open'=>$czx,
                            'type'=>$types,
                            'link'=>[
                                        $bao
                                    ],
                        ),
                        'close'=>array(
                            'open'=>$lose_min_s,
                            'time'=>$lose_min,
                        ),
                        'copy'=>array(
                            'open'=>$cr,
                        ),
                    ),
                ),
            );  
            echo encrypt($data);
            exit;
        }elseif($List['fj_status'] ==2){
            $data = array(
                'error' => false,
                'errorCode' => 0,
                'errorMessage' => '',
                'type' => $type,
                'data' => array(
                    'reqId' => $data_json->reqId,
                    'placementId' => $data_json->placementId,
                    'image' => array(
                        $List['imageurl'],
                    ),
                    'content' => $List['adinfo'],
                    'displayTrack' => array(
                        $viewurl,
                    ),
                    'clickTrack' => array(
                        $baseUrl,
                    ),
                    'playTrack' => '',
                    'video' => array(
                        'type' => '',
                        'duration' => '',
                        'bitrate' => '',
                        'url' => '',
                    ),
                    'action' => $List['action'],
                    'link' => $List['url'],
                    'deepLink'=>$List['deep_url'],
                    'title' => $List['adname'],
                    "deepPackage"=> $List['app_apply'],
                    "clipboard"=>$List['copy_clipboard'],
                    "openExtention"=>false,
                ),
            );
            echo encrypt($data);
            exit;
        }
    }
}

//接口加密 函数
function encrypt($data)
{
    $iv = "16-Bytes--String";//16位
    $privateKey = '2bd83b239a4140d6';//16位
    header('Content-Type:application/json; charset=utf-8');
    $data_front = json_encode($data,JSON_UNESCAPED_UNICODE);
    $data_front_c = base64_encode(openssl_encrypt($data_front,"AES-128-CBC",$privateKey,OPENSSL_RAW_DATA,$iv));
    return $data_front_c;
}

//返回图片链接和点击链接
//统计手机手机唯一识别码等信息
// logToSdk($IMEI,$modle,$mobile,$id,$user_IP);

// 开启限制
function openDomainLimit($uid, $adsthisUrl, $pdo)
{
    if(!empty($adsthisUrl)){
        // 查看当前域名是否存在 和 站点扣量
        $sites = $pdo->prepare("select site_id,uid,siteurl,class_id,web_deduction,adv_deduction,site_cnzz_id from lz_site WHERE uid=? AND siteurl=? AND status=1");
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
            $fuzzySites = $pdo->prepare("select  site_id,uid,siteurl,class_id,web_deduction,adv_deduction,site_cnzz_id
            from lz_site WHERE uid = ? AND siteurl LIKE '%$fuzzy_url_array' AND status=1 ");
            $fuzzySites->execute(array($uid));
            $siteRes = $fuzzySites->fetchAll();
        }
    }
    return $siteRes;
}

//方案1：传过来的数据是单个的 没有办法比对. 应该传 二维数组   [计划ID[广告ID,广告权重]，计划权重](推荐使用)
//方案2: 这边先筛选已经展示过的广告和广告计划.然后交由广告筛选器进行筛选.
//$proArr 广告id加权重数组  110 广告 100权重
function cookie_checkerG($pdo, $ad_id_arr, $pid,$sredis,$user_IP)
{
    $result = '';
    $type = "randg";
    //判断redis里面，本次访问的用户是否访问过此广告id
    $user_adid = $sredis->handler()->HMGET($user_IP,array('adid'))['adid'];
    if (!empty($user_adid)) {
        $redis_adid_arr = array_flip(explode(',', $user_adid));
        $inter_adids = array_diff_key($ad_id_arr,$redis_adid_arr);

        //当前计划的广告都展示过，清除此计划下面的广告，在加上本次的广告id
        if(empty($inter_adids)){

            foreach($ad_id_arr as $key=>$value){
                if(array_key_exists($key,$redis_adid_arr)){
                    unset($redis_adid_arr[$key]);
                }
            }
        }
        //如果广告都被展示，则重新来
        if (empty($inter_adids)) {

            $result = get_rand($ad_id_arr);
            //本次用户访问的广告id + 别的计划下面已显示的广告id 存redis
            $text_adid = implode(',',array_flip($redis_adid_arr));
            $user_text_adid =$text_adid.','.$result;

            $array_adid =array(
                'adid' => $user_text_adid,
            );

            $sredis->handler()->HMSET($user_IP,$array_adid);
            //$sredis->set($user_IP,$user_text_adid);

        } else {
            $result = get_rand($inter_adids);
            //本次用户访问的广告id + 以前的广告id  存redis
            $user_text_adid = $result.','.$user_adid;

            $array_adid =array(
                'adid' => $user_text_adid,

            );
            $sredis->handler()->HMSET($user_IP,$array_adid);

        }

    } else {
        $result = get_rand($ad_id_arr);
        $array_adid =array(
            'adid' => $result
        );

        //本次用户访问的广告id存redis
        $sredis->handler()->HMSET($user_IP,$array_adid);
    }
    return $result;
}

//ccp.
function cookie_checker_plan($ad_id_arr,$sredis,$user_IP)
{
    $type = "randj";
    $pidcount = count($ad_id_arr);

    $result = '';

    //判断redis里面，本次访问的用户是否访问过此计划id
    $user_pid = $sredis->handler()->HMGET($user_IP,array('pid'))['pid'];


    if (!empty($user_pid)) {

        $redis_pid_arr = array_flip(explode(',', $user_pid));
        $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);

        //如果广告都被展示，则重新来
        if (empty($inter_adids)) {

            $result = get_rand($ad_id_arr);
            $array_pid =array(
                'pid'  => $result
            );

            //本次用户访问的广告id存redis
            $sredis->handler()->HMSET($user_IP,$array_pid);


        } else {
            $result = get_rand($inter_adids);
            //本次用户访问的计划id + 以前的计划id  存redis
            $array_pid =array(
                'pid'  => $user_pid.','.$result
            );
            //本次用户访问的广告id存redis
            $sredis->handler()->HMSET($user_IP,$array_pid);
        }

    } else {

        $result = get_rand($ad_id_arr);
        $array_pid =array(
            'pid'  => $result
        );
        //本次用户访问的广告id存redis
        $sredis->handler()->HMSET($user_IP,$array_pid);
    }
    return $result;

}

//gr.
function get_rand($proArr)
{
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);
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

//判断访问用户是httpstp
function is_HTTPS(){
    if(!isset($_SERVER['HTTPS']))  return FALSE;
    if($_SERVER['HTTPS'] === 1){  //Apache
        return TRUE;
    }elseif($_SERVER['HTTPS'] === 'on'){ //IIS
        return TRUE;
    }elseif($_SERVER['SERVER_PORT'] == 443){ //其他
        return TRUE;
    }
    return FALSE;
}

//展示的广告信息
function advInformation($pdo,$id,$rand,$adzList,$pid,$sredis)
{
    global $log_test_str;
    global  $log_test_file;

    $prep = $pdo->prepare("SELECT a.uid,a.copy_clipboard_s,a.plantype,a.width,a.height,a.false_close,b.viewjs,b.iframejs,c.tpl_id,d.text_chain,d.imageurl,a.address,d.url,d.action,d.files,d.tc_id,d.pid,a.download_bao_url,a.fj_status,a.lose_min,d.ad_id,d.copy_clipboard,a.download,d.deep_url,d.adname,d.adinfo,a.download_app,a.city_province_s,a.download_status,e.plan_type,d.app_apply,e.uid AS planuid FROM lz_adzone AS a
        LEFT JOIN lz_adstyle AS b ON a.adstyle_id = b.style_id LEFT JOIN lz_admode AS c
        ON a.adtpl_id = c.tpl_id LEFT JOIN lz_ads AS d ON c.tpl_id =d.tpl_id LEFT JOIN lz_plan AS e ON d.pid = e.pid
        WHERE a.adz_id=? AND d.ad_id=? AND a.uid=? AND e.pid=? AND d.status = 1");
    $prep->execute(array($id,$rand,$adzList[0]['uid'],$pid));
    $res = $prep->fetchAll();

    if (empty($res)) {
        //展示广告信息有误
        $log_test_str.=' ERROR10010'."\n";
        writeFile($log_test_file,$log_test_str);
        // echo('展示广告信息有误');
        // exit;
        $data = array(
            'error' => true,
            'errorCode' => '3009',
            'errorMessage' => '展示广告信息有误',
            'type' => $type,
            'data' => '',
        );
        echo encrypt($data);
        exit;
    }

    $res = $res[0];

    return $res;

}

///服务器地址
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

//CPC计费模式下更新统计表数据
function logToSdk($IMEI,$modle,$mobile,$id,$user_IP)
{
    $logday = date('Ymd');
    $logdate = date('H-i');
    $dayTime = date('Y-m-d');
    //文件目录
    $data_str = substr($logdate,0,strlen($logdate)-1);
    if (!file_exists(__DIR__.'/../../ad/sdk/apilog/'.$logday)){
        mkdir (__DIR__."/../../ad/sdk/apilog/".$logday,0755,true);
    }
    $log_test_file = __DIR__."/../../ad/sdk/apilog/".$logday.'/'.'sdk'.$data_str.'.log';
    $log_test_str = "id=".$id.",unique=".$IMEI.",modle=".$modle.",mobile=".$mobile.",ip=".$user_IP.",day=".$dayTime."\n";
    writeFileForPv($log_test_file,$log_test_str);

}

//正则截取GET提交的id(只留取数字)
function getIdCut($id)
{
    preg_match_all('/\d+/',$id,$name);
    $id_num = join('',$name[0]);

    return $id_num;
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
//统计专用写日志
function writeFileForPv($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
    $fp = @fopen($file, $mode);
    // @flock($fp, 3);
    if (!$fp) {

    } else {
        @fwrite($fp, $str);
        @fclose($fp);
        // @umask($oldmask);
        // Return true;
    }
}
//获取用户 IP
function GetUserIp()
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
function model($useragent)
{
    //返回值中是否有Android这个关键字
    if (stristr($useragent, 'Android') && stristr($useragent, 'Build')) {
        $sub_end = stristr($useragent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        if(empty($sub_results)){
            $sub_results = 'Android';
        }
        return $sub_results;   //返回手机型号
    }elseif(stristr($useragent, 'Android')){
        return 'Android';
    }elseif(stristr($useragent, 'iPhone') || stristr($useragent, 'iPad') || stristr($useragent, 'iPod')){
        $sub_end = stristr($useragent, 'ike',true); //截取like之前的字符串
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


?>
