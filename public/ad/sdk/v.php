<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Origin: *');

error_reporting(0);
date_default_timezone_set('PRC');//校正时间

$start_timea = microtime(true);

require_once __DIR__ . '/../sredis.php';
$connectParam = array(
    'host'       => '127.0.0.1',
    'port'       => 6379,
    'password'   => '',
    'timeout'    => 1,
    'expire'     => 0,
    'persistent' => false,
    'prefix'     => '',);
$redis = new Redisutil($connectParam);

//redis切库
$week = date('w');
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

try{
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}
// $pdo->exec('set names utf8');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

//log slog
$log_test_file = 'slog.txt';
$log_test_str = ''.date('Y-m-d H:i:s').'';

if(empty(array_keys($_GET,'='))){
    $urlnum = array_keys($_GET,'');
}else{
    $urlnum = array_keys($_GET,'=');
}

$urlnum = base64_decode($urlnum[0]);
$urlnum = explode('&',$urlnum);
$ad_id = substr($urlnum[0], 6);
$adz_id = substr($urlnum[1], 7);
$pid = substr($urlnum[2], 4);
$uid = substr($urlnum[3], 4);
$tc_id = substr($urlnum[4], 6);
$tpl_id = substr($urlnum[5], 7);
$planuid = substr($urlnum[6], 8);
$site_id = substr($urlnum[7], 8);
$user_ip = substr($urlnum[8], 8);
$plan_type = substr($urlnum[9], 10);
$unique = substr($urlnum[10], 7);

if(empty($ad_id) || empty($adz_id) || empty($pid) || empty($uid)){ exit; }

//正则截取GET提交的id(只留取数字)
$adz_id = getIdCut($adz_id);
$ad_id = getIdCut($ad_id);
$pid = getIdCut($pid);
$uid = getIdCut($uid);
$tc_id = getIdCut($tc_id);
$tpl_id = getIdCut($tpl_id);
$planuid = getIdCut($planuid);
$site_id = getIdCut($site_id);

$list = array(
    'adz_id' => $adz_id,
    'ad_id'  => $ad_id,
    'pid'    => $pid,
    'uid'    => $uid,
    'tc_id'  => $tc_id,
    'tpl_id' => $tpl_id,
    'planuid'   => $planuid,
    'site_id'   => empty($site_id) ? 1 : $site_id,
    'plan_type' => substr($plan_type,0,3),
    'user_ip' => $user_ip,
    'base_cookies' => $unique,
);
chapv($list,$pdo,$redis);
function chapv($list,$pdo,$redis)
{
    $list['adz_id'] = getIdCut($list['adz_id']);
    //获取自营广告id
    $self_adv_id = explode(',',$redis->self_adv_id);

    //得到该次展示的单价和扣量
    $list = getList($pdo,$list);
    //根据不同的扣量优先级选择扣量
    $list = getDeducation($list,$pdo);
    $pdo = null;
    //查看计费次数
    $uiNum = getUiNum($list,$redis);
    //更新实时ip表
    $ipNumber = updateRealtimeip($list,$uiNum,$redis);
    //更新浏览器表
    $uvNumber = updateBrowser($list,$redis);
    // //按广告位算的独立ip  separate
    // $separateip = $redis->handler()->HMGET('separate-ip-'.$list['adz_id'],array($list['user_ip']));
    // if(empty($separateip[$list['user_ip']])){
    //     //此ip 没有访问此广告位id时存入
    //     $array_userip = array(
    //         $list['user_ip'] => 1,
    //     );
    //     $redis->handler()->HMSET('separate-ip-'.$list['adz_id'],$array_userip);
    // }

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
            if (!file_exists(__DIR__.'/../../test/lezunlog/'.$logday)){
                mkdir (__DIR__."/../../test/lezunlog/".$logday,0755,true);
            }
            $log_test_file = __DIR__."/../../test/lezunlog/".$logday.'/'.'v'.$data_str.'.log';

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
            if (!file_exists(__DIR__.'/../../test/lezunlog/'.$logday)){
                mkdir (__DIR__."/../../test/lezunlog/".$logday,0755,true);
            }
            $log_test_file = __DIR__."/../../test/lezunlog/".$logday.'/'.'c'.$data_str.'.log';
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
        if (!file_exists(__DIR__.'/../../test/lezunlog/'.$logday)){
            mkdir (__DIR__."/../../test/lezunlog/".$logday,0755,true);
        }
        $log_test_file = __DIR__."/../../test/lezunlog/".$logday.'/'.'c'.$data_str.'.log';
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

