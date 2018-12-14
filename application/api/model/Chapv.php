<?php 
namespace app\api\model;
use PDO;
use think\Model;
use org\RedisUtil as RedisUtil;

class Chapv extends \think\Model
{
    //返回数据库连接对象
    private function getPdo($redis,$tag)
    {
        if($tag=='master'){
            $db_link = $redis->db_link;
            $db_root = $redis->db_root;
            $db_password = $redis->db_password;
        }else{
            $db_link = $redis->db_syn_link;
            $db_root = $redis->db_syn_root;
            $db_password = $redis->db_syn_password;
        }   
        try{
            $pdo = new PDO($db_link,$db_root,$db_password);
        }catch(PDOException $e){
            //echo '数据库连接失败'.$e->getMessage();
        }
        return $pdo;
    }

    public function charge($statsParams)
    {
        import("extend.org");
        $redis = new RedisUtil();

        //获取自营广告id
        $self_adv_id = explode(',',$redis->self_adv_id);

        if(empty($statsParams['ad_id']) || empty($statsParams['adz_id']) || empty($statsParams['pid']) || empty($statsParams['uid'])){ exit; }

        //正则截取GET提交的id(只留取数字)
        $adz_id = $this->getIdCut($statsParams['adz_id']);
        $ad_id = $this->getIdCut($statsParams['ad_id']);
        $pid = $this->getIdCut($statsParams['pid']);
        $uid = $this->getIdCut($statsParams['uid']);
        $tc_id = $this->getIdCut($statsParams['tc_id']);
        $tpl_id = $this->getIdCut($statsParams['tpl_id']);
        $planuid = $this->getIdCut($statsParams['planuid']);
        $site_id = $this->getIdCut($statsParams['site_id']);

        $list = array(
            'adz_id' => $adz_id,
            'ad_id'  => $ad_id,
            'pid'    => $pid,
            'uid'    => $uid,
            'tc_id'  => $tc_id,
            'tpl_id' => $tpl_id,
            'plan_type' => substr($statsParams['plan_type'],0,3),
            'planuid'   => $planuid,
            'site_id'   => empty($site_id) ? 1 : $site_id,
            'ip_infos_useradd' => $statsParams['ip_infos_useradd'],
            'user_ip' => $statsParams['user_ip'],
            'base_cookies' => substr($statsParams['base_cookies'],0,strrpos($statsParams['base_cookies'],'==')).'==',
            'modle_name'=>$statsParams['modle_name'],
            'system_version'=>$statsParams['system_version'],
        );

        $dayTime = date('Y-m-d');
        $pdoa = $this->getPdo($redis,'slave');
        //得到该次展示的单价和扣量
        $list = $this->getList($pdoa,$list,$redis);
        //根据不同的扣量优先级选择扣量
        $list = $this->getDeducation($list,$pdoa);
        $pdoa = null;

        $pdo = $this->getPdo($redis,'master');
        // 查看统计表是否有数据
        $statRes = $this->statsCheck($list,$redis,$dayTime);
        //查看计费次数
        $uiNum = $this->getUiNum($list,$redis);
        //更新实时ip表
        $ipNumber = $this->updateRealtimeip($list,$pdo,$uiNum,$redis,$dayTime);
        //更新浏览器表
        $uvNumber = $this->updateBrowser($pdo,$list,$redis,$dayTime);
        //数据报表为空插入数据，反之更新数据
        if(empty($statRes['views'])) {
            $this->statsInsert($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime);
        }else{
            $this->statsUpdate($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime);
        }
        $pdo = null;
    }


    //得到广告价格和扣量
    private function getList($pdo,$list,$redis)
    {
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

        if(empty($res)){exit;}
        //判断该广告是否分站长星级，并且查询不同站长的单价
        if ($res['gradation'] == 1) 
        {
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


    //查看统计表是否有数据
    private function statsCheck($list,$redis,$dayTime)
    {
        $views = $redis->handler()->HMGET('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,array('views'));
        //为空的情况下赋值0
        if (!$views['views']) {
            $views['views'] = 0;
        }
        return $views;
    }


    //查看当前uv计费次数
    private function getUiNum($list,$redis)
    {
        $uiNum = $redis->handler()->HMGET('ui_ip_'.$list['adz_id'],array($list['user_ip']));
        $uiNum = $uiNum[$list['user_ip']];
        if(empty($uiNum)){
            $uiNum = 0;
        }else{
            $uiNum = (int)$uiNum;
        }
        return $uiNum;
    }


    //统计表CPM计费模式下插入数据
    private function statsInsert($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime)
    {
        $web_deduction = $list['web_deduction'];
        $adv_deduction = $list['adv_deduction'];
        //获取当前计费次数
        $billing_number = $uiNum + 1;

        $web_num = 1 + $list['web_deduction']; //站长结算数
        $adv_num = 1 + $list['adv_deduction']; //广告商结算数
        
        //用户独立ip访问的次数所统计不同的价钱
        if($list['tpl_id'] != '5030')
        {
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

        $insert = $pdo->prepare("INSERT INTO lz_stats (uid,pid,ad_id,tc_id,adv_id,adtpl_id,site_id,adz_id,sumprofit,sumpay,sumadvpay,
                views,day,web_deduction,adv_deduction,web_num,adv_num,unique_visitor,unique_ip,uv_web,ui_web,plan_type,heavy_click_num,ctime)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $insert->execute(array($list['uid'],$list['pid'],$list['ad_id'],$list['tc_id'],$list['planuid'],$list['tpl_id'],$list['site_id'], $list['adz_id'],
            $sumprofit,$sumpay,$sumadvpay,1,$dayTime,$web_deduction,$adv_deduction,$web_num,$adv_num,1,1,$uvNumber,$ipNumber,$list['plan_type'],0,time()));
    }


    //CPM计费模式下更新统计表数据
    private function statsUpdate($list,$pdo,$uiNum,$uvNumber,$ipNumber,$self_adv_id,$redis,$dayTime)
    {
        $web_deduction = $list['web_deduction'];
        $adv_deduction = $list['adv_deduction'];

        //获取当前计费次数
        $billing_number = $uiNum + 1;

        $web_num = 1 + $list['web_deduction'];    //站长结算数
        $adv_num = 1 + $list['adv_deduction'];    //广告商结算数
        
        //用户独立ip访问的次数所统计不同的价钱
        if($list['tpl_id'] != '5030')
        {
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
            $this->redis_update($list,$pdo,$redis,$dayTime);
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
        $this->redis_update($list,$pdo,$redis,$dayTime);
    }


    //将redis里面的数据同步到数据库
    private function redis_update($list,$pdo,$redis,$dayTime)
    {
        //缓存达到50次后，同步数据据库
        $views = $redis->handler()->HMGET('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,array('views'));
        $redis_num = $list['adz_id'] % 2 == 0 ? 51 : 49;
        if($views['views'] % $redis_num == 0)
        {
            $views = $redis->handler()->HMGET('stats-'.$list['ad_id'].'-'.$list['adz_id'].'-'.$list['site_id'].$dayTime,
                array('views','click_num','sumprofit','sumpay','sumadvpay','uv_web','ui_web','web_deduction','adv_deduction','web_num','adv_num'));
            //更新数据报表的数据
            $update = $pdo->prepare("UPDATE lz_stats SET sumprofit=?,sumpay=?,sumadvpay=?,views=?,click_num=?,uv_web=?,ui_web=?,web_deduction=?,
           adv_deduction=?,web_num=?,adv_num=? WHERE adz_id=? AND ad_id=? AND site_id=? AND day=?");
            $update->execute(array($views['sumprofit'],$views['sumpay'],$views['sumadvpay'],$views['views'],$views['click_num'],
                $views['uv_web'],$views['ui_web'],$views['web_deduction'],$views['adv_deduction'],$views['web_num'],
                $views['adv_num'],$list['adz_id'],$list['ad_id'],$list['site_id'],$dayTime));
        }

        //100000次redis操作后同步数据库
        $stats_key = $redis->handler()->HMGET('statsKey',array('statsKey'));
        if($stats_key['statsKey'] % 2000000 == 0)
        {
            $array = array();
            //查询出数据报表的所有redis数据，并且剔除掉浏览数大于50的数据
            $views = $redis->handler()->KEYS('stats-*');
            $num = 0;
            foreach($views as $key=>$value)
            {
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
            foreach($array as $key => $value)
            {
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
    }


    //判断计划限额并更新
    private function updateBudget($list,$pdo,$redis,$dayTime)
    {
        //获取当前计划消耗
        $money = $redis->handler()->HMGET('budget-'.$list['pid'].'-'.$dayTime,array('budget'));
        if(empty($money)){
            $money['budget'] = 0;
        }
        //判断该计划是否达到限额，如果达到限额则将该计划锁定
        if($list['budget'] <= $money['budget'])
        {
            //直接将该计划限额的缓存置0，防止程序没有运行完的情况下其他程序再次进入判断，影响性能
            $budget['budget'] = 0;
            $redis->handler()->HMSET('budget-'.$list['pid'].'-'.$dayTime,$budget);
            //查询该计划下所有激活的广告
            $adid = $pdo->prepare("SELECT ad_id FROM lz_ads WHERE pid=? AND status=1 ");
            $adid->execute(array($list['pid']));
            $adid = $adid->fetchAll();
            //将该计划下所有广告的应付金额从缓存中取出，并且累加
            $sumMoney = 0;
            foreach($adid as $key=>$value)
            {
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
    private function updateRealtimeip($list,$pdo,$uiNum,$redis,$dayTime)
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
        }
        return $uiNumber;
    }


    //判断有无,添加或修改浏览器表数据
    private function updateBrowser($pdo,$list,$redis,$dayTime)
    {
        //手机型号
        $modle = $list['modle_name'];
        //手机系统版本
        $system_version = $list['system_version'];
        //得到是否有这个ip
        $havaIp = $redis->handler()->SISMEMBER('uv_'.$list['adz_id'],$list['base_cookies']);

        // 有此ip就增加重复数，没有就添加
        if(empty($havaIp))
        {
            $uvNumber = 1;
            //存各个站长独立ip
            $redis->handler()->SADD('uv_'.$list['adz_id'],$list['base_cookies']);
        }else{
            $uvNumber = 0;
        }
        return $uvNumber;
    }


    //根据不同的扣量优先级选择扣量
    private function getDeducation($list,$pdo)
    {
        //判断选择扣量的优先级 站长扣量
        if(empty($list['user_web_deduction']) && empty($list['user_adv_deduction']))
        {         
            //广告位扣量
            if(empty($list['adz_web_deduction']) && empty($list['adz_adv_deduction']))
            {    
                //广告扣量   
                if(empty($list['ads_web_deduction']) && empty($list['ads_adv_deduction']))
                {     
                    //计划扣量
                    if(empty($list['plan_web_deduction']) && empty($list['deduction']))
                    {   
                        //查全局扣量
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
    private function getIdCut($id)
    {
        preg_match_all('/\d+/',$id,$name);
        $id_num = join('',$name[0]);
        return $id_num;
    }
}
?>