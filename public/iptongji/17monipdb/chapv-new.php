<?php

header("Cache-Control: no-cache");
header("Pragma: no-cache");
// $start_timea = microtime(true);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

// ignore_user_abort();
// header('HTTP/1.1 200 OK');
// header('Content-Length:0');
// header('Connection:Close');
// flush();

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

$log_test_file = 'chapvlog.txt';
$log_test_str = '';

require_once __DIR__ . '/redis.php';
$redis = new Redisutil();

//获取自营广告id
$self_adv_id = explode(',',$redis->self_adv_id);

//数据库连接信息
$db_link = $redis->db_link;
$db_root = $redis->db_root;
$db_password = $redis->db_password;
$pdo = new PDO($db_link,$db_root,$db_password);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
// $pdo->setAttribute(PDO::ATTR_PERSISTENT,true);
// $pdo->exec('set names utf8');

if(empty($_GET['blog_id']) || empty($_GET['id']) || empty($_GET['pid']) || empty($_GET['uid'])){ exit; }

//正则截取GET提交的id(只留取数字)
$id = getIdCut($_GET['id']);
$blog_id = getIdCut($_GET['blog_id']);
$pid = getIdCut($_GET['pid']);
$uid = getIdCut($_GET['uid']);
$tc_id = getIdCut($_GET['tc_id']);
$tpl_id = getIdCut($_GET['tpl_id']);
$planuid = getIdCut($_GET['planuid']);
$site_id = getIdCut($_GET['site_id']);

$list = array(
    'adz_id' => $id,
    'ad_id'  => $blog_id,
    'pid'    => $pid,
    'uid'    => $uid,
    'tc_id'  => $tc_id,
    'tpl_id' => $tpl_id,
    'plan_type' => substr($_GET['plan_type'],0,3),
    'planuid'   => $planuid,
    'site_id'   => empty($site_id) ? 1 : $site_id,
    'ip_infos_useradd' => $_GET['ip_infos_useradd'],
    'user_ip' => $_GET['user_ip'],
    'base_cookies' => substr($_GET['base_cookies'],0,strrpos($_GET['base_cookies'],'==')).'==',
    'browser'=>$_GET['browser'],
    'ver'=>$_GET['ver'],
    'kernel'=>$_GET['kernel'],
    'modle_name'=>$_GET['modle_name'],
    'system_version'=>$_GET['system_version'],
);

$dayTime = date('Y-m-d');
////压测专用
//$ipOne = rand(1,255);
//$ipTwo = rand(1,255);
//$ipThree = rand(1,255);
//$ipFour = rand(256,512);
//$list['user_ip'] = $ipOne.'.'.$ipTwo.'.'.$ipThree.'.'.$ipFour;
//$dayTime = date('Y-m-d');
//$list['base_cookies'] = $ipOne.'.'.$ipTwo.'.'.$ipThree.'.'.$ipFour;

$pdoa = new PDO($redis->db_syn_link,$redis->db_syn_root,$redis->db_syn_password);
// $pdoa->exec('set names utf8');
$pdoa->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

//得到该次展示的单价和扣量
$list = getList($pdoa,$list,$redis);
//根据不同的扣量优先级选择扣量
$list = getDeducation($list,$pdoa);
$pdoa = null;
// 查看统计表是否有数据
$statRes = statsCheck($list,$redis,$dayTime);
//查看计费次数
$uiNum = getUiNum($list,$redis);
//更新实时ip表
$ipNumber = updateRealtimeip($list,$pdo,$uiNum,$redis,$dayTime);
//更新浏览器表
$uvNumber = updateBrowser($pdo,$list,$redis,$dayTime);
//按广告位算的独立ip  separate
$separateip = $redis->handler()->HMGET('separate-ip-'.$list['adz_id'],array($list['user_ip']));
if(empty($separateip[$list['user_ip']])){
    //此ip 没有访问此广告位id时存入
    $array_userip = array(
        $list['user_ip'] => 1,
    );
    $redis->handler()->HMSET('separate-ip-'.$list['adz_id'],$array_userip);
}
//数据报表为空插入数据，反之更新数据
if (empty($statRes['views'])) {
    statsInsert($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime);
}else{
    statsUpdate($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime);
}

//得到广告价格和扣量
function getList($pdo,$list,$redis)
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
        LEFT JOIN lz_plan AS c ON b.pid = c.pid LEFT JOIN lz_users_log AS d ON a.uid = d.uid LEFT JOIN lz_plan_price AS e
        ON b.tc_id = e.id WHERE b.ad_id=? AND d.uid=? AND c.pid=? AND adz_id=? AND c.status=1 AND b.status=1");
    $prep->execute(array($list['ad_id'],$list['uid'],$list['pid'],$list['adz_id']));
    $res = $prep->fetchAll();
    $res = $res[0];
//        $redis->handler()->HMSET($list['ad_id'].'-'.$list['uid'].'-'.$list['pid'].'-'.$list['adz_id'],$res);
//    }

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

    return $list;
}

// 查看统计表是否有数据
function statsCheck($list,$redis,$dayTime)
{
    $views = $redis->handler()->HMGET('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,array('views'));

    //为空的情况下赋值0
    if (!$views['views']) {
        $views['views'] = 0;
    }
    return $views;
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

//统计表 CPM计费模式下插入数据
function statsInsert($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime)
{
    $web_deduction = $list['web_deduction'];
    $adv_deduction = $list['adv_deduction'];
    //获取当前计费次数
    $billing_number = $uiNum + 1;

    $web_num = 1 + $list['web_deduction'];              //站长结算数
    $adv_num = 1 + $list['adv_deduction'];              //广告商结算数
    //用户独立ip访问的次数所统计不同的价钱
    if($list['tpl_id'] != '5030'){
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
    }else{
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
        $advUpdate = $pdo->prepare("UPDATE lz_users set adv_money=? WHERE uid=?");
        $advUpdate->execute(array($adv_money['adv_money'],$list['planuid']));
        //该广告商的redis置0
        $adv_money['key_money'] = 0;
        $redis->handler()->HMSET('users-'.$list['planuid'],$adv_money);
    }

    $sumprofit = $list['adv_money'] - $list['web_money'];     // 平台盈利
    $sumpay = $list['web_money'];                           //站长盈利
    $sumadvpay = $list['adv_money'];                      //广告商支付

    $redis->handler()->HINCRBY('statsKey','statsKey',1);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'views',1);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'click_num',0);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumprofit',$sumprofit);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumpay',$sumpay);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumadvpay',$sumadvpay);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'uv_web',$uvNumber);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'ui_web',$ipNumber);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'web_deduction',$web_deduction);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'adv_deduction',$adv_deduction);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'web_num',$web_num);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'adv_num',$adv_num);

    //缓存计划限额
    $redis->handler()->HINCRBYFLOAT('budget-'.$list['pid'].'-'.$dayTime,'budget',$list['adv_money']);

    //按广告位算 独立 ip
    $adzone_ip = $redis->handler()->HLEN('separate-ip-'.$list['adz_id']);
    $insert = $pdo->prepare("INSERT INTO lz_stats (uid,pid,ad_id,tc_id,adv_id,adtpl_id,site_id,adz_id,sumprofit,sumpay,sumadvpay,
            views,day,web_deduction,adv_deduction,web_num,adv_num,unique_visitor,unique_ip,uv_web,ui_web,ui_adzone,plan_type,heavy_click,ctime)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $insert->execute(array($list['uid'],$list['pid'],$list['ad_id'],$list['tc_id'],$list['planuid'],$list['tpl_id'],$list['site_id'], $list['adz_id'],
        $sumprofit,$sumpay,$sumadvpay,1,$dayTime,$web_deduction,$adv_deduction,$web_num,$adv_num,1,1,$uvNumber,$ipNumber,$adzone_ip,$list['plan_type'],0,time()));
}
//CPM计费模式下更新统计表数据
function statsUpdate($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime)
{
    $web_deduction = $list['web_deduction'];
    $adv_deduction = $list['adv_deduction'];

    //获取当前计费次数
    $billing_number = $uiNum + 1;

    $web_num = 1 + $list['web_deduction'];              //站长结算数
    $adv_num = 1 + $list['adv_deduction'];              //广告商结算数

    //用户独立ip访问的次数所统计不同的价钱
    if($list['tpl_id'] != '5030'){
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
    }else{
        $redis->handler()->HINCRBY('statsKey','statsKey',1);
        $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'views',1);
        $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'uv_web',$uvNumber);
        $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'ui_web',$ipNumber);

        //将redis里面的数据同步到数据库
        redis_update($list,$pdo,$redis,$dayTime);
        exit;
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
        $advUpdate = $pdo->prepare("UPDATE lz_users set adv_money=? WHERE uid=?");
        $advUpdate->execute(array($adv_money['adv_money'], $list['planuid']));
        //该广告商的redis置0
        $adv_money['key_money'] = 0;
        $redis->handler()->HMSET('users-'.$list['planuid'],$adv_money);
    }

    $sumprofit = $list['adv_money'] - $list['web_money'];     // 平台盈利
    $sumpay = $list['web_money'];                           //站长盈利
    $sumadvpay = $list['adv_money'];                      //广告商支付

    $redis->handler()->HINCRBY('statsKey','statsKey',1);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'views',1);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumprofit',$sumprofit);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumpay',$sumpay);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'sumadvpay',$sumadvpay);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'uv_web',$uvNumber);
    $redis->handler()->HINCRBY('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'ui_web',$ipNumber);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'web_deduction',$web_deduction);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'adv_deduction',$adv_deduction);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'web_num',$web_num);
    $redis->handler()->HINCRBYFLOAT('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,'adv_num',$adv_num);

    //缓存计划限额
    $redis->handler()->HINCRBYFLOAT('budget-'.$list['pid'].'-'.$dayTime,'budget',$list['adv_money']);

    // $log_test_file = 'budget.log';
    // $log_test_str ='stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime.'------------------'.'budget-'.$list['pid'].'-'.$dayTime.'------------------'.$sumadvpay."\n";
    // writeFile($log_test_file,$log_test_str);
    //将redis里面的数据同步到数据库
    redis_update($list,$pdo,$redis,$dayTime);

}

//将redis里面的数据同步到数据库
function redis_update($list,$pdo,$redis,$dayTime)
{
    //缓存达到50次后，同步数据据库
    $views = $redis->handler()->HMGET('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,array('views'));
//    $redis_num = substr($list['adz_id'],-2) + 50;
    $redis_num = $list['adz_id'] % 2 == 0 ? 51 : 49;
    if($views['views'] % $redis_num == 0){
        //排重点击
        $heavy_click = $redis->handler()->HLEN('paiclick-'.$list['pid']);
        if(!empty($heavy_click)){
            $heavy_num = $heavy_click;
        }else{
            $heavy_num = 0;
        }
        //按广告位算 独立 ip
        $adzone_ip = $redis->handler()->HLEN('separate-ip-'.$list['adz_id']);

        $views = $redis->handler()->HMGET('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,
            array('views','click_num','sumprofit','sumpay','sumadvpay','uv_web','ui_web','web_deduction','adv_deduction','web_num','adv_num'));
        //更新数据报表的数据
        $update = $pdo->prepare("UPDATE lz_stats SET sumprofit=?,sumpay=?,sumadvpay=?,views=?,click_num=?,uv_web=?,ui_web=?,ui_adzone=?,web_deduction=?,
       adv_deduction=?,web_num=?,adv_num=?,heavy_click=? WHERE adz_id=? AND ad_id=? AND site_id=? AND day=?");
        $update->execute(array($views['sumprofit'],$views['sumpay'],$views['sumadvpay'],$views['views'],$views['click_num'],
            $views['uv_web'],$views['ui_web'],$adzone_ip,$views['web_deduction'],$views['adv_deduction'],$views['web_num'],
            $views['adv_num'],$heavy_num,$list['adz_id'],$list['ad_id'],$list['site_id'],$dayTime));
    }

//     100000次redis操作后同步数据库
    $stats_key = $redis->handler()->HMGET('statsKey',array('statsKey'));
    if($stats_key['statsKey'] % 2000000 == 0){
        $array = array();
        //查询出数据报表的所有redis数据，并且剔除掉浏览数大于50的数据
        $views = $redis->handler()->KEYS('stats-*');
        $num = 0;
        foreach($views as $key=>$value){
            $arr = $redis->handler()->HMGET($value,array('views','click_num','sumprofit','sumpay','sumadvpay','uv_web','ui_web','web_deduction','adv_deduction','web_num','adv_num'));
            if($arr['views'] < 51 && !empty($arr['views'])){
                $data = explode('-',$value);
                $array[$num] = $arr;
                $array[$num]['ad_id'] = $data[1];
                $array[$num]['adz_id'] = $data[2];
                $array[$num]['site_id'] = substr($data[3],0,-4);
                $array[$num++]['day'] = substr($data[3],-4).'-'.$data[4].'-'.$data[5];
            }
        }
        // 将redis中的数据同步到数据库中
        foreach($array as $key => $value){
            // 更新数据报表的数据
            $update = $pdo->prepare("UPDATE lz_stats SET sumprofit=?,sumpay=?,sumadvpay=?,views=?,click_num=?,uv_web=?,ui_web=?,web_deduction=?,
             adv_deduction=?,web_num=?,adv_num=? WHERE adz_id=? AND ad_id=? AND site_id=? AND day=?");
            $update->execute(array($value['sumprofit'],$value['sumpay'],$value['sumadvpay'],$value['views'],$value['click_num'],
                $value['uv_web'],$value['ui_web'],$value['web_deduction'],$value['adv_deduction'],$value['web_num'],
                $value['adv_num'],$value['adz_id'],$value['ad_id'],$value['site_id'],$value['day']));
        }
    }

    //处理计划限额
    $money = $redis->handler()->HMGET('budget-'.$list['pid'].'-'.$dayTime,array('budget'));
    if($list['budget'] <= $money['budget']){
        $webUpdate = $pdo->prepare("UPDATE lz_plan SET status=3 WHERE pid=?");
        $webUpdate->execute(array($list['pid']));
    }
//    updateBudget($list,$pdo,$redis,$dayTime);
}

//判断计划限额并更新
function updateBudget($list,$pdo,$redis,$dayTime)
{
    //获取当前计划消耗
    $money = $redis->handler()->HMGET('budget-'.$list['pid'].'-'.$dayTime,array('budget'));
    if(empty($money)){
        $money['budget'] = 0;
    }
    //判断该计划是否达到限额，如果达到限额则将该计划锁定
    if($list['budget'] <= $money['budget']){
        //直接将该计划限额的缓存置0，防止程序没有运行完的情况下其他程序再次进入判断，影响性能
        $budget['budget'] = 0;
        $redis->handler()->HMSET('budget-'.$list['pid'].'-'.$dayTime,$budget);
        //查询该计划下所有激活的广告
        $adid = $pdo->prepare("SELECT ad_id FROM lz_ads WHERE pid=? AND status=1 ");
        $adid->execute(array($list['pid']));
        $adid = $adid->fetchAll();
        //将该计划下所有广告的应付金额从缓存中取出，并且累加
        $sumMoney = 0;
        foreach($adid as $key=>$value){
            $arr = $redis->handler()->KEYS('stats-'.$value['ad_id'].'-*');
            foreach($arr as $k=>$v){
                $sumadvpay = $redis->handler()->HMGET($v,array('sumadvpay'));
                $sumadvpay['sumadvpay'] = empty($sumadvpay['sumadvpay']) ? 0 : $sumadvpay['sumadvpay'];
                $sumMoney = $sumMoney + $sumadvpay['sumadvpay'];
            }
        }
        //若该 计划的限额 减去 stats缓存的应付 小于1元，则将计划的状态修改为超限额，否则将限额的缓存跟新为应付
        $budget = $list['budget'] - $sumMoney;
        $advMoney['budget'] = $sumMoney;
        if($budget > 1){
            $redis->handler()->HMSET('budget-'.$list['pid'].'-'.$dayTime,$advMoney);
        }else{
            $webUpdate = $pdo->prepare("UPDATE lz_plan SET status=3 WHERE pid=?");
            $webUpdate->execute(array($list['pid']));
        }
    }
}

//判断实时ip信息
function updateRealtimeip($list,$pdo,$uiNum,$redis,$dayTime)
{
    //用户IP
    $user_IP = $list['user_ip'];
    //用户位置
    $userAdd = $list['ip_infos_useradd'];

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
//         //将每次需要插入数据库的ip暂时缓存到redis中
//         $ip_now= "("."'".$list['uid']."',"."'".$user_IP."',"."'".$userAdd."',"."'".$list['plan_type']."',"."'".$dayTime.
//             "',"."'".time()."',"."'".$list['adz_id']."'),";
//         //批量插入数据所需要的键值
//         $redis->handler()->HINCRBY('ui_ip_key','ui_ip_key',1);
//         $ui_ip_key = $redis->handler()->HMGET('ui_ip_key',array('ui_ip_key'));
//         //将本次的ip信息与未入库的ip信息拼接，存入缓存
//         $data = $redis->handler()->HMGET('realtime_ip_',array('ip'));
//         $data['ip'] = empty($data) ? '' : $data['ip'];
//         $data['ip'] = $data['ip'].$ip_now;
//         $redis->handler()->HMSET('realtime_ip_',$data);
//
//         //累计1000次后批量插入到数据库中
//         if($ui_ip_key['ui_ip_key'] % 50 == 0){
//             $ui['ui_ip_key'] = 0;
//             $arr['ip'] = '';
//             $redis->handler()->HMSET('ui_ip_key',$ui);
//             $redis->handler()->HMSET('realtime_ip_',$arr);
//             $char =  substr($data['ip'], 0, -1);
//             //插入当前浏览器信息
//             $insebrowse = $pdo->prepare("INSERT INTO lz_realtimeip (uid,ip,regional,type,day,ctime,adz_id) VALUES $char");
//             $insebrowse->execute();
//         }
    }
    return $uiNumber;
}

//判断有无,添加或修改浏览器表数据
function updateBrowser($pdo,$list,$redis,$dayTime)
{
    //手机型号
    $modle = $list['modle_name'];
    //手机系统版本
    $system_version = $list['system_version'];
    //得到是否有这个ip
    $havaIp = $redis->handler()->SISMEMBER('uv_'.$list['adz_id'],$list['base_cookies']);


    // 有此ip就增加重复数，没有就添加
    if(empty($havaIp)){
        $uvNumber = 1;
        //存各个站长独立ip
        $redis->handler()->SADD('uv_'.$list['adz_id'],$list['base_cookies']);
        // //将每次需要插入数据库的ip暂时缓存到redis中
        // $ip_now= "("."'".$list['uid']."',"."'".$list['browser']."',"."'".$list['ver']."',"."'".$list['kernel']."',"."'".$dayTime.
        //     "',"."'".$list['user_ip']."',"."'".$list['ad_id']."',"."'".$list['base_cookies']."',"."'".$list['pid']
        //     ."',"."'".$list['adz_id']."',"."'".$modle."',"."'".$system_version."'),";
        // //批量插入数据所需要的键值
        // $redis->handler()->HINCRBY('uv_key','uv_key',1);
        // $ui_ip_key = $redis->handler()->HMGET('uv_key',array('uv_key'));
        // //将本次的ip信息与未入库的ip信息拼接，存入缓存
        // $data = $redis->handler()->HMGET('browser_uv',array('uv'));
        // $data['uv'] = empty($data) ? '' : $data['uv'];

        // $redis->handler()->HMSET('browser_uv',$data);

        // //累计1000次后批量插入到数据库中
        // if($ui_ip_key['uv_key'] % 50 == 0){
        //     $ui['uv_key'] = 0;
        //     $arr['uv'] = '';
        //     $redis->handler()->HMSET('uv_key',$ui);
        //     $redis->handler()->HMSET('browser_uv',$arr);
        //     $char =  substr($data['uv'], 0, -1);
        //     //插入当前浏览器信息
        //     $insebrowse = $pdo->prepare("INSERT INTO lz_browser (uid,browser,ver,kernel,day,ip,ad_id,cookies,pid,adz_id,modle_name,system_version) VALUES $char");
        //     $insebrowse->execute();
        // }
    }else{
        $uvNumber = 0;
    }
    return $uvNumber;
}

//根据不同的扣量优先级选择扣量
function getDeducation($list,$pdo)
{
    // 查看当前域名是否存在 和 站点扣量
//    $fuzzySites = $pdo->prepare("SELECT site_id,uid,siteurl,web_deduction,adv_deduction,site_cnzz_id FROM lz_site WHERE uid = ? AND site_id=? AND status=1 ");
//    $fuzzySites->execute(array($list['uid'],$list['site_id']));
//    $siteRes = $fuzzySites->fetchAll();

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

$pdo = null;

