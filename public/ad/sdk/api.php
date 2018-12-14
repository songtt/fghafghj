<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Origin: *');

error_reporting(0);
date_default_timezone_set('PRC');//校正时间

$start_timea = microtime(true);

require_once __DIR__ . '/../17monipdb/Ipsearch.class.php';
$IpSearch = new IpSearch('../qqzeng-ip-utf8.dat');
require_once __DIR__ . '/../sredis.php';

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

$id = $_GET['id'];
$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
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
if(empty($useragent) || empty($id)){
    //返回图片链接和点击链接
    $data = array(
        'status' => 1,
        'data' => '参数错误'
    );
    header('Content-Type:application/json; charset=utf-8');
    $data_front = json_encode($data,JSON_UNESCAPED_UNICODE);
    echo $data_front;exit;
}

//用户ip
$user_IP = GetIp();
//ip地理位置
global $ipInfos;
$user_IP = '220.191.255.255';
$ipInfos = GetIpLookup($user_IP,$IpSearch);
dump($ipInfos);exit;
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

$adzLimitSql = $pdo->prepare("SELECT adz_id,uid,htmlcontrol,checkadz,minutes,adtpl_id,width,height,class_id,plan_class_allow FROM lz_adzone WHERE adz_id=?  AND status=1");
$adzLimitSql->execute(array($id));
$adzList = $adzLimitSql->fetchAll();

if (empty($adzList[0])) {
    $log_test_str.=' 广告位id'.$id.' ERROR10001'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('广告位没有激活');
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
    echo('无可展示的广告');
    exit;
}
$ad_id = rtrim($ad_id,',');

//广告位属于哪个站长id的
if (!empty($adzList[0]['uid'])) {
    $uid = $adzList[0]['uid'];
} else {
    $log_test_str.=' ERROR10005'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('该广告位没有所属站长');
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

//获取手机操作系统
$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
$is_pc = (strpos($agent, 'windows nt')) ? true : false;
$is_iphone = (strpos($agent, 'iphone')) ? true : false;
$is_ipad = (strpos($agent, 'ipad')) ? true : false;
$is_android = (strpos($agent, 'android')) ? true : false;
$is_wp = (strpos($agent, 'micromessenger')) ? true : false;  //微信
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
b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id,b.run_terminal FROM lz_ads AS a
LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON a.uid = c.uid WHERE a.ad_id
IN (' . $ad_id . ') AND b.status = 1 AND a.status =1 AND b.run_terminal IN('.$mobile.') AND c.money > c.adv_money
AND b.delay_show_status !=1');
$ad_sql->execute(array());
$ad_sql_list = $ad_sql->fetchAll();

////判断redis里面，本次访问的用户是否访问过此计划id
//$user_pid = $sredis->handler()->HMGET($user_IP,array('pid'))['pid'];
//
////得到正常显示的pid
//$normal_pid_array = array();
//foreach ($ad_sql_list as $key => $val){
//    if(($ad_sql_list[$key]['run_terminal'] == $mobile) || ($ad_sql_list[$key]['run_terminal'] == '0')){
//        $normal_pid_array[$val['pid']] = $val['pid'];
//    }
//}
//
//$redis_pid_arr = array_flip(explode(',', $user_pid));
////判断已展示的pid 和全部pid 对比返回不同的pid
//$delay_inter_adids = array_diff_key($normal_pid_array,$redis_pid_arr);
////读取延迟展示情况下每次加一的值
//$delay_show_num = $sredis->handler()->HMGET($user_IP.'_delay_show_pid',array('delay_show_num'))['delay_show_num'];
////读取延迟展示的总个数
//$delay_show_count = $sredis->handler()->GET($user_IP.'_delay_show_count');
////不限计划延迟也显示
//if ($mobile == 2) {
//    $mobile = '0,2';
//}else{
//    $mobile = '0,3';
//}
//
////判断延迟展示计划是否展示完毕
//if(($delay_show_num < $delay_show_count) && !empty($delay_show_count)  && !empty($delay_show_num)){
//    //如果广告都被展示，则重新来
//    if (empty($delay_inter_adids)) {
//        //得到激活ad的集合
//        $ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,
//        b.pid,b.restrictions,b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id
//        FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON
//         a.uid = c.uid WHERE a.ad_id IN (' . $ad_id . ') AND b.run_terminal IN('.$mobile.') AND b.status = 1
//         AND a.status =1 AND c.money > c.adv_money AND b.delay_show_status = 1');
//        $ad_sql->execute(array());
//        $delay_ad_sql_list = $ad_sql->fetchAll();
//        if (!empty($delay_ad_sql_list)) {
//            $ad_sql_list = $delay_ad_sql_list;
//            //得到延迟显示的pid
//            $delay_pid_array = array();
//            foreach ($ad_sql_list as $key => $val) {
//                $delay_pid_array[$val['pid']] = $val['pid'];
//            }
//            $delay_show_count = count($delay_pid_array) + 1;
//            //存延迟展示的总个数
//            $sredis->handler()->SET($user_IP . '_delay_show_count', $delay_show_count);
//            //只在延迟展示情况下 每次加 1
//            $sredis->handler()->HINCRBY($user_IP . '_delay_show_pid', 'delay_show_num', 1);
//        }
//    }
//}else{
//    //正常显示的计划为空
//    if(empty($normal_pid_array)){
//        //得到激活 延迟计划的 ad的集合
//        $ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,
//            b.pid,b.restrictions,b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id
//            FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON
//             a.uid = c.uid WHERE a.ad_id IN (' . $ad_id . ') AND b.run_terminal IN('.$mobile.') AND b.status = 1
//             AND a.status =1 AND c.money > c.adv_money AND b.delay_show_status = 1');
//        $ad_sql->execute(array());
//        $ad_sql_list = $ad_sql->fetchAll();
//    }
//
//    $limit_pid_sql = $pdo->prepare('SELECT pid FROM lz_plan WHERE run_terminal IN('.$mobile.')
//    AND status = 1 AND delay_show_status = 1 Limit 1');
//    $limit_pid_sql->execute(array());
//    $delay_limit_list = $limit_pid_sql->fetchAll();
//    if(!empty($delay_limit_list)){
//        //初始化 总个数值
//        $sredis->handler()->SET($user_IP.'_delay_show_count',2);
//        $delay_show_num = array(
//            'delay_show_num' => 1,
//        );
//        //初始化
//        $sredis->handler()->HMSET($user_IP.'_delay_show_pid',$delay_show_num);
//    }
//}
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
    echo('所有计划限制都不满足，无可投放的广告');
    exit;
}else{
    // 在赋给原值
    $pidarr_sel['plan'] = $contrast_arr;
}
$pid_adid_sel_arr = array();
//得到随机计划
$final_pid = cookie_checker_plan($pidarr_sel['plan'],$sredis,$user_IP);

if(empty($final_pid)){
    $log_test_str.=' ERROR10008'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('所有计划限制都不满足，无可投放的广告');
    exit;
}

$adid_arr = $pidarr_sel['ads'][$final_pid];

foreach ($adid_arr as $key => $value) {
    if (empty($value)) {
        $value = 1;
    }
    $adid_arr[$key] = $value;
}

$today = strtotime(date("Y-m-d"), time());
$end = $today + 60 * 60 * 24;
$cookTime = $end - time();//独立访客存储时间

//独立访客
$old_cookies = isset($_COOKIE['baseCookies']) ? $_COOKIE['baseCookies'] : '';

//获取毫秒
function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}

if (empty($old_cookies)) {
    $baseCookies = base64_encode(getMillisecond());
    $_COOKIE['baseCookies'] = $baseCookies;
    $old_cookies = $baseCookies;
    setcookie('baseCookies', $baseCookies, time() + $cookTime);
}else{
    $old_cookies = $_COOKIE['baseCookies'];
}

$final_ad_id = cookie_checkerG($pdo, $adid_arr, $final_pid,$sredis,$user_IP);

//随机得到的广告id
$rand = $final_ad_id;

//随机得到的计划id
$Pid = $final_pid;

//展示的广告信息
$List = advInformation($pdo,$id,$rand,$adzList,$Pid,$sredis);

//统计点击量  click_url
$baseUrl =  'blogid=' . $rand .'&siteid='.$siteRes[0]['site_id'].'&uid='.$adzList[0]['uid'].
    '&pid='.$final_pid.'&userip='.$user_IP.'&tpl_id='.$List['tpl_id'].'&plantype='.$List['plantype'].'&planuid='.$List['planuid'];
$baseUrl = "$platformUrl/$cilck_url" .$id .$sign. base64_encode($baseUrl);

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
    if(strpos($List['imageurl'], 'img.miaoceshi') == true){
        if(strpos($List['imageurl'], 'https') == false){
            $List['imageurl'] = str_replace('http','https',$List['imageurl']);
        }
        $List['imageurl'] = str_replace('img.miaoceshi','wen.miaoceshi',$List['imageurl']);
    }
}

if (!empty($List)) {
    $statsParams = array(
        'adz_id' => $id,
        'ad_id'  => $rand,
        'pid'    => $final_pid,
        'uid'    => $adzList[0]['uid'],
        'tc_id'  => $List['tc_id'],
        'tpl_id' => $List['tpl_id'],
        'plan_type' => $List['plan_type'],
        'planuid'   => $List['planuid'],
        'site_id'   => empty($siteRes) || empty($siteRes[0]['site_id']) ? 1 : $siteRes[0]['site_id'],
        'user_ip' => $user_IP,
        'base_cookies' => $old_cookies,
        'browser'=>$arrayBrowse[2],
        'ver'=>$arrayBrowse[3],
        'kernel'=>$arrayBrowse[0],
        'modle_name'=>$modle,
        'system_version'=>$system_version,
        'adtype_id' => $List['adtype_id'],
        'text_chain' => $List['text_chain'],
        'textcheck' => unserialize($List['textcheck'])
    );

}

//计费链接
$viewurl = 'ad_id='.$statsParams['ad_id'].'&adz_id='.$statsParams['adz_id'].'&pid='.$statsParams['pid'].
    '&uid='.$statsParams['uid'].'&tc_id='.$statsParams['tc_id'].'&tpl_id='.$statsParams['tpl_id'].
    '&planuid='.$statsParams['planuid'].'&site_id='.$statsParams['site_id'].'&user_ip='.$statsParams['user_ip'].
    '&plan_type='.$statsParams['plan_type'].'&base_cookies='.$statsParams['base_cookies'];
$jsServer = str_replace('https://','',$globalRes[0]['js_server']);
$jsServer = str_replace('http://','',$globalRes[0]['js_server']);
$viewurl = $jsServer.'/devmip/'. base64_encode($viewurl);
if ($statsParams['adtype_id'] == '20'){
    $textInfo = $statsParams['textcheck']['0'];
}elseif ($statsParams['adtype_id'] == '21') {
    $textInfo = $statsParams['textcheck']['0'];
}elseif ($statsParams['adtype_id'] == '17') {
    $textInfo = $statsParams['text_chain'];
}else{
    $textInfo = '';
}
if ($statsParams['adtype_id'] == '19') {
    //返回图片链接和点击链接
    $data = array(
        'status' => 0,
        'data' => array(
            'items' => array(
                array(
                'imageurl' => $List['imageurl'],
                'clickurl' => $baseUrl,
                'viewurl' => $viewurl,
                'textFirst' => $statsParams['textcheck']['0'],
                'textSecond' => $statsParams['textcheck']['1'],
                'textThird' => $statsParams['textcheck']['2'],
                'textForth' => $statsParams['textcheck']['3'],
                )
            )
        )
    );
}else{
    //返回图片链接和点击链接
    $data = array(
        'status' => 0,
        'data' => array(
            'items' => array(
                array(
                'imageurl' => $List['imageurl'],
                'clickurl' => $baseUrl,
                'viewurl' => $viewurl,
                'text' => $textInfo,
                'day' => date('Y-m-d')
                )
            )
        )
    );
}


header('Content-Type:application/json; charset=utf-8');
$data_front = json_encode($data,JSON_UNESCAPED_UNICODE);
//执行回调
$callback = $_GET['callback'];
echo $callback."($data_front)";

if (!empty($List)) {
    $statsParams = array(
        'adz_id' => $id,
        'ad_id'  => $rand,
        'pid'    => $final_pid,
        'uid'    => $adzList[0]['uid'],
        'tc_id'  => $List['tc_id'],
        'tpl_id' => $List['tpl_id'],
        'plan_type' => $List['plan_type'],
        'planuid'   => $List['planuid'],
        'site_id'   => empty($siteRes) || empty($siteRes[0]['site_id']) ? 1 : $siteRes[0]['site_id'],
        'user_ip' => $user_IP,
        'base_cookies' => $old_cookies,
        'browser'=>$arrayBrowse[2],
        'ver'=>$arrayBrowse[3],
        'kernel'=>$arrayBrowse[0],
        'modle_name'=>$modle,
        'system_version'=>$system_version,
    );

}



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

    $prep = $pdo->prepare("SELECT a.uid,a.plantype,a.width,a.height,a.false_close,b.viewjs,b.iframejs,c.tpl_id,
        d.text_chain,d.imageurl,d.textcheck,d.url,d.files,d.tc_id,d.pid,c.adtype_id,e.plan_type,e.uid AS planuid FROM lz_adzone AS a
        LEFT JOIN lz_adstyle AS b ON a.adstyle_id = b.style_id LEFT JOIN lz_admode AS c
        ON a.adtpl_id = c.tpl_id LEFT JOIN lz_ads AS d ON c.tpl_id =d.tpl_id LEFT JOIN lz_plan AS e ON d.pid = e.pid
        WHERE a.adz_id=? AND d.ad_id=? AND a.uid=? AND e.pid=? AND d.status = 1");
    $prep->execute(array($id,$rand,$adzList[0]['uid'],$pid));
    $res = $prep->fetchAll();

    if (empty($res)) {
        //展示广告信息有误
        $log_test_str.=' ERROR10010'."\n";
        writeFile($log_test_file,$log_test_str);
        echo('展示广告信息有误');
        exit;
    }

    $res = $res[0];

    return $res;

}

//正则截取GET提交的id(只留取数字)
function getIdCut($id)
{
    preg_match_all('/\d+/',$id,$name);
    $id_num = join('',$name[0]);

    return $id_num;
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
