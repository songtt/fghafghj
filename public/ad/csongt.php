<?php
// error_reporting(0);
date_default_timezone_set('PRC');//校正时间

function writeFile($file, $str, $mode = 'a+')
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
//log 统计
function writeFileForLog($file, $str, $mode = 'a+')
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

if (empty(array_keys($_GET, '='))) {
    $urlnum = array_keys($_GET, '');
} else {
    $urlnum = array_keys($_GET, '=');
}
$urlnum = base64_decode($urlnum[0]);
$urlnum = explode('&', $urlnum);
$blogid = substr($urlnum[0], 7);
$siteid = substr($urlnum[1], 7);
$uid = substr($urlnum[2], 4);
$pid = substr($urlnum[3], 4);
$userip = substr($urlnum[4], 7);
$tpl_id = substr($urlnum[5], 7);
$plantype = substr($urlnum[6], 9);
$planuid = substr($urlnum[7], 8);

//  id 是广告位 id
$id = addslashes($_GET['id']);
$id = htmlspecialchars($id);

// 站长id
$uid = htmlspecialchars($uid);

// adid 广告id
if (empty($blogid)) {
    exit;
}
$adid = htmlspecialchars($blogid);

$siteid = htmlspecialchars($siteid);

//pid
$pid = htmlspecialchars($pid);


//临时
function writeFileforpid($file, $str, $mode = 'a+')
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

//ip
$userip = htmlspecialchars($userip);

//正则截取GET提交的id(只留取数字)
$id = getIdCut($id);
$adid = getIdCut($adid);
$uid = getIdCut($uid);
$siteid = getIdCut($siteid);
$pid = getIdCut($pid);
$tpl_id = getIdCut($tpl_id);
$planuid = getIdCut($planuid);

if (empty($siteid) || $siteid == '0') {
    $siteid = 1;
}
$list = array(
    'user_ip' => $userip,
    'tpl_id' => $tpl_id,
    'pid' => $pid,
    'uid' => $uid,
    'site_id' => $siteid,
    'ad_id' => $adid,
    'adz_id' => $id,
    'plantype' => $plantype,
    'planuid' => $planuid
);

require_once __DIR__ . '/redis.php';
$connectParam = array(
    'host' => '10.28.204.171',
    'port' => 6379,
    'password' => '',
    'timeout' => 1,
    'expire' => 0,
    'persistent' => false,
    'prefix' => '',
);
$redis = new Redisutil($connectParam);
//redis切库
$week = date('w');
if (date('Y-m-d') != '2018-01-02') {
    switch ($week) {
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
//主库写
$db_link = $redis->db_link;
$db_root = $redis->db_root;
$db_password = $redis->db_password;

try {
    // $pdo = new PDO($db_link,$db_root,$db_password);
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lz_ad;port=3306', 'slave_user', '5WKYxnl1f1Uu8MCj');
} catch (PDOException $e) {
    // echo '数据库连接失败'.$e->getMessage();
}
// $pdo->exec('set names utf8');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

//log
$log_test_file = 'clog.txt';
// $log_test_str = ''.date('Y-m-d H:i:s').' ';

$prep = $pdo->prepare("SELECT url FROM lz_ads WHERE ad_id=?");

$prep->execute(array($adid));
$res = $prep->fetchAll();

if (empty($res[0])) {
    //广告信息不全    ERROR10011
    $log_test_str .= 'ERROR10011' . "\n";
    writeFile($log_test_file, $log_test_str);
    exit;
}
$list_res = $res[0];

$oldurl = $list_res['url'];
$list_res['url'] = str_replace("{uid}", $uid, $list_res['url']);
$list_res['url'] = str_replace("{gid}", $id, $list_res['url']);

if (empty($list_res['url'])) {
    exit;
}

// if(stristr($_SERVER['HTTP_USER_AGENT'], 'UCBrowser'))
// {
// $ccurl = $List['url'];
// echo <<<EOT
// <html>
// <head>
// <meta http-equiv="Refresh" content="3; url=$ccurl" />
// </head>
// <body>
// <span>caonimauc</span><span></span>
// </body>
// </html>

///test
// EOT;
// }else
// {


if (stristr($_SERVER['HTTP_USER_AGENT'], 'UCBrowser')) {
    if (stristr($list_res['url'], '?')) {
        $list_res['url'] = $list_res['url'] . "&uctime=" . time();
    } else {
        $list_res['url'] = $list_res['url'] . "?uctime=" . time();
    }
}
$log_test_file = 'songt.txt';
$f = serialize($list) . "\n";
writeFileForLog($log_test_file, $f);


// header('Location:' . $list_res['url']);
// }
$dayTime = date("Y-m-d");
// echo '<meta http-equiv="Refresh" content="0; url='.$list_res['url'].'" />';

//临时统计计划id为87的计划跳转链接
// if($pid == 87){
//     $log_test_file = 'c-pid-log.txt';
//     $log_test_str.="pid=".$pid.",url=".$list_res['url']."\n";
//     writeFileforpid($log_test_file,$log_test_str);
// }
$h = serialize($list) . "\n";
writeFileForLog($log_test_file, $h);
$day = date("Y-m-d", time());
// $redis->handler()->HINCRBY('stats-'.$adid.'-'.$id.'-'.$siteid.$day,'click_num',1);
// 同ip的点击次数
$redis->handler()->HINCRBY('ip_click', $id . '-' . $list['user_ip'], 1);
//排重点击
$click_pid = $redis->handler()->HMGET('paiclick-' . $pid, array($userip));

if (empty($click_pid[$userip])) {
    $array_userip = array(
        $userip => 1,
    );
    $redis->handler()->HMSET('paiclick-' . $pid, $array_userip);
}

//站长排重点击
$web_click_uid = $redis->handler()->HMGET('web-click-uid-' . $uid, array($userip));
if (empty($web_click_uid[$userip])) {
    $array_userip = array(
        $userip => 1,
    );
    $redis->handler()->HMSET('web-click-uid-' . $uid, $array_userip);
}

//广告位排重点击
$adz_click_id = $redis->handler()->HMGET('adz-click-id-' . $id, array($userip));
if (empty($adz_click_id[$userip])) {
    $array_userip = array(
        $userip => 1,
    );
    $redis->handler()->HMSET('adz-click-id-' . $id, $array_userip);
}

$a = serialize($list) . "\n";
writeFileForLog($log_test_file, $a);
//CPC固定位置计费
if ($list['tpl_id'] == '5030' || $list['plantype'] == 'CPC' || $list['tpl_id'] == '5033' || $list['tpl_id'] == '5034' || $list['tpl_id'] == '5035') {
    $b = 'cpc' . "\n";
    writeFileForLog($log_test_file, $b);

    //获取自营广告id
    $self_adv_id = explode(',', $redis->self_adv_id);
    //得到该次展示的单价和扣量
    $list = getList($pdo, $list, $redis);
    //根据不同的扣量优先级选择扣量
    $list = getDeducation($list, $pdo);
    // CPC查看ip+点击计费次数
    $IpNum = getIpNum($list, $redis);
    $c = '扣量+次数' . "\n";
    writeFileForLog($log_test_file, $c);
    if ($IpNum > 100) {
        $log_test_file = 'chaoguoip.txt';
        $log_test_str = $id . ' ' . $IpNum . "\n";
        writeFileforpid($log_test_file, $log_test_str);
    }
    //更新结算后的钱
    statsCpcUpdate($list, $pdo, $IpNum, $redis, $dayTime, $self_adv_id);
} else {
    $d = '不是cpc' . "\n";
    writeFileForLog($log_test_file, $d);
    $logday = date('Ymd');
    $logdate = date('H-i');

    if (empty($_GET['id']) || empty($blogid) || empty($pid) || empty($uid)) {
        exit;
    }

    //文件目录
    $data_str = substr($logdate, 0, strlen($logdate) - 1);
    if (!file_exists(__DIR__ . '/../test/lezunlog/' . $logday)) {
        mkdir(__DIR__ . "/../test/lezunlog/" . $logday, 0755, true);
    }
    $log_test_file = __DIR__ . "/../test/lezunlog/" . $logday . '/' . 'v' . $data_str . '.log';
    $log_test_str = "ad_id=" . $list['ad_id'] . ",adz_id=" . $list['adz_id'] . ",site_id=" . $list['site_id'] . ",uid=" . $list['uid'] . ",pid=" . $list['pid'] . ",views=0,sumprofit=0,sumpay=0,sumadvpay=0,uv_web=0,ui_web=0,web_deduction=0,adv_deduction=0,web_num=0,adv_num=0,day=" . $dayTime . ",adv_id=" . $list['planuid'] . ",plan_type=CPM,tc_id=0,tpl_id=" . $list['tpl_id'] . ",click_num=1\n";
    // writeFileForLog($log_test_file,$log_test_str);
}

//得到广告价格和扣量
function getList($pdo, $list, $redis)
{
//    $res = $redis->handler()->HMGET('chapv'.$list['ad_id'].'-'.$list['uid'].'-'.$list['pid'].'-'.$list['adz_id'],
//        array('ads_web_deduction','ads_adv_deduction','deduction','plan_web_deduction','budget','user_web_deduction',
//            'price','price_1','price_2','price_3','price_4','price_5','pricedv','gradation'));
//    if(empty($res['pricedv'])){
    $prep = $pdo->prepare("SELECT a.web_deduction AS adz_web_deduction,a.adv_deduction AS adz_adv_deduction,
        b.web_deduction AS ads_web_deduction,b.adv_deduction AS ads_adv_deduction,
        c.deduction,c.web_deduction AS plan_web_deduction,c.budget,d.web_deduction AS user_web_deduction,d.adv_deduction AS
        user_adv_deduction,e.price,e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv,e.gradation
        FROM lz_adzone AS a LEFT JOIN lz_admode AS f ON a.adtpl_id = f.tpl_id LEFT JOIN lz_ads AS b ON b.tpl_id=f.tpl_id
        LEFT JOIN lz_plan AS c ON b.pid = c.pid LEFT JOIN lz_users AS d ON a.uid = d.uid LEFT JOIN lz_plan_price AS e
        ON b.tc_id = e.id WHERE b.ad_id=? AND d.uid=? AND c.pid=? AND adz_id=? AND c.status=1 AND b.status=1");
    $prep->execute(array($list['ad_id'], $list['uid'], $list['pid'], $list['adz_id']));
    $res = $prep->fetchAll();
    $res = $res[0];
//        $redis->handler()->HMSET($list['ad_id'].'-'.$list['uid'].'-'.$list['pid'].'-'.$list['adz_id'],$res);
//    }
    if (empty($res)) {
        exit;
    }
    //判断该广告是否分站长星级，并且查询不同站长的单价
    if ($res['gradation'] == 1) {
        $gradation = $pdo->prepare("SELECT star FROM lz_adzone WHERE adz_id=?");
        $gradation->execute(array($list['adz_id']));
        $gradation = $gradation->fetchAll();
        switch ($gradation[0]['star']) {
            case 1:
                $list['price'] = $res['price_1'];
                break;
            case 2:
                $list['price'] = $res['price_2'];
                break;
            case 3:
                $list['price'] = $res['price_3'];
                break;
            case 4:
                $list['price'] = $res['price_4'];
                break;
            case 5:
                $list['price'] = $res['price_5'];
                break;
        }
    } else {
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

    return $list;
}

//根据不同的扣量优先级选择扣量
function getDeducation($list, $pdo)
{
    // 查看当前域名是否存在 和 站点扣量
//    $fuzzySites = $pdo->prepare("SELECT site_id,uid,siteurl,web_deduction,adv_deduction,site_cnzz_id FROM lz_site WHERE uid = ? AND site_id=? AND status=1 ");
//    $fuzzySites->execute(array($list['uid'],$list['site_id']));
//    $siteRes = $fuzzySites->fetchAll();

    //判断选择扣量的优先级
    if (empty($list['user_web_deduction']) && empty($list['user_adv_deduction'])) {         //站长扣量

        if (empty($list['adz_web_deduction']) && empty($list['adz_adv_deduction'])) {       //广告位扣量

            if (empty($list['ads_web_deduction']) && empty($list['ads_adv_deduction'])) {     //广告扣量

                if (empty($list['plan_web_deduction']) && empty($list['deduction'])) {        //计划扣量
                    // 查全局扣量
                    $sett = $pdo->prepare("SELECT cpm_deduction,adv_cpm_deduction FROM lz_setting");
                    $sett->execute();
                    $settRes = $sett->fetchAll();
                    $list['web_deduction'] = $settRes[0]['cpm_deduction'] / 100;
                    $list['adv_deduction'] = $settRes[0]['adv_cpm_deduction'] / 100;
                } else {
                    $list['web_deduction'] = $list['plan_web_deduction'] / 100;
                    $list['adv_deduction'] = $list['deduction'] / 100;
                }
            } else {
                $list['web_deduction'] = $list['ads_web_deduction'] / 100;
                $list['adv_deduction'] = $list['ads_adv_deduction'] / 100;
            }
        } else {
            $list['web_deduction'] = $list['adz_web_deduction'] / 100;
            $list['adv_deduction'] = $list['adz_adv_deduction'] / 100;
        }
    } else {
        $list['web_deduction'] = $list['user_web_deduction'] / 100;
        $list['adv_deduction'] = $list['user_adv_deduction'] / 100;
    }

    //加上扣量之后的单价
    $list['adv_money'] = $list['pricedv'] * (1 + ($list['adv_deduction']));
    $list['web_money'] = $list['price'] * (1 + ($list['web_deduction']));
    return $list;
}

// 查看当前ip 点击次数
function getIpNum($list, $redis)
{
    //=======================ip+点击计数===============
    $adz_ipkey = $list['adz_id'] . '-' . $list['user_ip'];
    $IpNum = $redis->handler()->HMGET('ip_click', array($adz_ipkey));
    $IpNum = $IpNum[$adz_ipkey];
    if (empty($IpNum)) {
        $IpNum = 0;
    } else {
        $IpNum = (int)$IpNum;
    }

    return $IpNum;
}

function statsCpcUpdate($list, $pdo, $IpNum, $redis, $dayTime, $self_adv_id)
{
    $e = '计费开始' . "\n";
    $log_test_file = 'songt.txt';
    writeFileForLog($log_test_file, $e);
    // var_dump($list['web_money']);exit;
    $web_deduction = $list['web_deduction'];
    $adv_deduction = $list['adv_deduction'];
    $web_num = 1 + $list['web_deduction'];              //站长结算数
    $adv_num = 1 + $list['adv_deduction'];              //广告商结算数
    //用户独立ip访问的次数所统计不同的价钱
    if ($IpNum == 1) {
        $list['adv_money'] = $list['adv_money'] * '1';
        $list['web_money'] = $list['web_money'] * '1';
    } else {
        $web_num = 0;              //站长结算数
        $adv_num = 0;              //广告商结算数
        $list['adv_money'] = 0;
        $list['web_money'] = 0;
        $web_deduction = 0;
        $adv_deduction = 0;
    }
    $q = "计费*=" . $IpNum . "\n";
    writeFileForLog($log_test_file, $q);
    //自营广告不盈利
    if (in_array($list['planuid'], $self_adv_id)) {
        $list['adv_money'] = $list['web_money'];
    }
    //redis缓存，当广告商消耗大于1元时更新数据库
    $redis->handler()->HINCRBYFLOAT('users-' . $list['planuid'], 'adv_money', $list['adv_money']);
    $redis->handler()->HINCRBYFLOAT('users-' . $list['planuid'], 'key_money', $list['adv_money']);
    $adv_money = $redis->handler()->HMGET('users-' . $list['planuid'], array('adv_money', 'key_money'));
    $update_advmoney = 10;
    $w = "计费2*=" . $IpNum . "\n";
    writeFileForLog($log_test_file, $w);
    // if ($adv_money['key_money'] > $update_advmoney) {
        if (true) {
            //更新广告商余额
        echo 'x';
        try {
            echo 'x0.1';
            $advUpdate = $pdo->prepare("UPDATE lz_users set adv_money=? WHERE uid=?");
            echo 'x0.2';
            echo $adv_money['adv_money'] . '\n';
            echo $list['planuid'] . '\n';
            $advUpdate->execute(array(340, $list['planuid']));
            echo 'x0.5';
        } catch (Exception $e) {
            echo 'x1';
            var_dump($e);
        }
        echo 'x2';
        //该广告商的redis置0
        // try{
        //     $pdoa = new PDO($redis->db_pv_link,$redis->db_pv_root,$redis->db_pv_password);
        // }catch(PDOException $e){
        //     // echo '数据库连接失败'.$e->getMessage();
        // }
        // $pdoa->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        // $advUpdate = $pdoa->prepare("UPDATE lz_users set adv_money=? WHERE uid=?");
        // $advUpdate->execute(array($adv_money['adv_money'], $list['planuid']));
        // $pdoa = null;
        $adv_money['key_money'] = 0;
        $redis->handler()->HMSET('users-' . $list['planuid'], $adv_money);
    }

    echo "<pre>";
    // var_dump(222);
    $sumprofit = $list['adv_money'] - $list['web_money'];     // 平台盈利
    $sumpay = $list['web_money'];                           //站长盈利
    $sumadvpay = $list['adv_money'];                      //广告商支付

    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumprofit',$sumprofit);
    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumpay',$sumpay);
    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumadvpay',$sumadvpay);
    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'web_deduction',$web_deduction);
    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'adv_deduction',$adv_deduction);
    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'web_num',$web_num);
    // $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'adv_num',$adv_num);

    //缓存计划限额
    $redis->handler()->HINCRBYFLOAT('budget-' . $list['pid'] . '-' . $dayTime, 'budget', $list['adv_money']);
    $logday = date('Ymd');
    $logdate = date('H-i');
    //文件目录
    // $data_str = substr($logdate,0,strlen($logdate)-1);
    // if (!file_exists(__DIR__.'/../test/lezunlog/'.$logday)){
    //     mkdir (__DIR__."/../test/lezunlog/".$logday,0755,true);
    // }
    // $log_test_file = __DIR__."/../test/lezunlog/".$logday.'/'.'c'.$data_str.'.log';

    $log_test_str = "ad_id=" . $list['ad_id'] . ",adz_id=" . $list['adz_id'] . ",site_id=" . $list['site_id'] . ",uid=" . $list['uid'] . ",pid=" . $list['pid'] . ",views=0,click_num=1,sumprofit=" . $sumprofit . ",sumpay=" . $sumpay . ",sumadvpay=" . $sumadvpay . ",uv_web=0,ui_web=0,web_deduction=" . $web_deduction . ",adv_deduction=" . $adv_deduction . ",web_num=" . $web_num . ",adv_num=" . $adv_num . ",day=" . $dayTime . ",adv_id=" . $list['planuid'] . ",plan_type=" . $list['plantype'] . ",tc_id=0,tpl_id=" . $list['tpl_id'] . "\n";
    writeFileForLog($log_test_file, $log_test_str);
    // writeFileForLog($log_test_file,$log_test_str);

    unset($log_test_str);
    unset($log_test_file);

}


$pdo = null;
//正则截取GET提交的id(只留取数字)
function getIdCut($id)
{
    preg_match_all('/\d+/', $id, $name);
    $id_num = join('', $name[0]);
    return $id_num;
}