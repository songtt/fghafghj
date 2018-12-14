<?php
/**
 * 计划
 * @date   2016-6-2
 */
namespace app\admin\model;
use think\Db;

class Plan extends \think\Model
{

    /**
     * 更新状态
     * param pid 计划id status 状态
     */
    public function adsSelStatus($pageParam)
    {

        $data = array(
            'ads_sel_status' => $pageParam['status']
        );
        $map = array(
            'pid'=>$pageParam['pid'],
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 更新权重
     */
    public function updateAdsPriority($ad_id,$priority)
    {

        $data = array(
            'priority' => $priority,
        );
        $map = array(
            'ad_id'=>$ad_id,
        );
        $res = Db::name('ads')->where($map)->update($data);
        return $res;
    }

    /**
     * 查询该计划下所有广告尺寸
     */
    public function getSize($pid)
    {
        $sql = 'SELECT pid,ad_id,width,height FROM lz_ads WHERE pid=? GROUP BY ad_id';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 查询该计划下同尺寸广告
     */
    public function getAdid($v)
    {
        $sql = 'SELECT pid,ad_id,width,height FROM lz_ads WHERE concat(width,"*",height)=? AND pid=? GROUP BY ad_id';
        $res = Db::query($sql,[$v['size'],$v['pid']]);
        return $res;

    }


    /**
     * 查询该计划下同一尺寸下所有广告总点击数
     */
    public function getClickNum($value)
    {
        $day = date("Y-m-d");
        $sql = 'SELECT pid,ad_id,SUM(click_num) as click_num,day FROM lz_stats_new WHERE ad_id=? AND day=?';
        $res = Db::query($sql,[$value['ad_id'],$day]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }


    /**
     * 查询该计划下每个广告的点击数
     */
    public function getAdNum($value)
    {
        $day = date("Y-m-d");
        $sql = 'SELECT pid,ad_id,SUM(click_num) as click_num,day FROM lz_stats_new WHERE ad_id=? AND day=?';
        $res = Db::query($sql,[$value['ad_id'],$day]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     * 查询该计划下广告
     */
    public function getClick($value)
    {
        $day = date("Y-m-d");
        $sql = 'SELECT pid,ad_id,SUM(click_num) as click_num FROM lz_stats_new WHERE pid=? AND day=? GROUP BY ad_id';
        $res = Db::query($sql,[$value,$day]);
        return $res;
    }


    /**
     * 查询该计划的浏览数
     */
    public function getPlanViews($pid)
    {
        $day = date("Y-m-d");
        $sql = 'SELECT SUM(views) as views FROM lz_stats_new WHERE pid=? AND day=? ';
        $res = Db::query($sql,[$pid,$day]);
        $res = empty($res) ? 0 : $res[0];
        return $res;
    }

    /**
     * add data
     * param data
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $data['status'] = 1;
        Db::name('plan')->insert($data);
        //返回插入数据的id
        $res = Db::name('plan')->getLastInsID();
        return $res;
    }

	/**
     * remindingAdd $pid $params
     * param data
     */
    public function remindingAdd($pid,$params)
    {
        $data = array();
        $data['pid'] = $pid;
        $data['uid'] = $params['uid'];
        $data['plan_name'] = $params['plan_name'];
        $data['terminal'] = ($params['run_terminal'] == 2) ? 'ios' : 'android';
        $data['type'] = '1';
        $data['ctime'] = time();

        $res = Db::name('reminding')->insert($data);
        return $res;
    }

    /**
     * gametotalbudget $pid $totalbudget
     * param data
     */
    public function gametotalbudget($pid,$totalbudget)
    {
        $data['pid'] = $pid;
        $data['totalbudget'] = $totalbudget;
        $res = Db::name('game_totalbudget')->insert($data);
        return $res;
    }

    /**
     * 得到列表
     * param data
     */
    public function getLst($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.run_terminal,a.plan_name,a.plan_type,a.budget,a.clearing,a.status,a.ads_sel_status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,
         a.deduction,a.web_deduction,c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b ON a.class_id=b.class_id LEFT JOIN 
         lz_users as c ON a.uid=c.uid  LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid ';
         if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }

            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.type!=2 ORDER BY a.pid DESC ';
            $res = Db::query($sql);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.run_terminal='.$mobile.' AND a.type!=2 ORDER BY a.pid DESC ';
            }else{
                $sql.= ' WHERE a.type!=2 ORDER BY a.pid DESC ';
            }
            $res = Db::query($sql);
        }
        return $res; 
    }

    /**
     * 更新游戏推广计划总限额表次数
     */
    public function gameUpdateNum($params)
    {
        $map = array(
            'pid' => $params['pid'],
        );
        $num = array(
            'num' => $params['num'],
        );
        $res = Db::name('game_totalbudget')->where($map)->update($num);
        if($res == false){
            $num = '';
        }
        return $num;

    }

    /**
     * 查询复制计划的数据
     */
    public function planCopy($pid)
    {
        $sql = 'SELECT uid,plan_name,bigpname,run_terminal,run_type,run_model,gradation,price,price_1,price_2,price_3,price_4,price_5,pricedv,mobile_price,price_info,budget,plan_type,deduction,web_deduction,clearing,restrictions,resuid,sitelimit,limitsiteid,adzlimit,limitadzid,pkey,linkurl,cookie,checkplan,class_id,ads_sel_views,ads_sel_status,status,delay_show_status,ctime,type,priority,click_status,percent FROM lz_plan WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res[0];
    }

    /**
     * 把复制计划的数据插入到新计划中
     */
    public function planCopyAdd($data)
    {
        //插入数据
        Db::name('plan')->insert($data);
        //返回插入数据的id
        $pid = Db::name('plan')->getLastInsID();
        return $pid;
    }

    /**
     *  获取复制游戏推广计划的总限额
     */
    public function gameCopyTotal($pid)
    {
        $sql = 'SELECT totalbudget,num FROM lz_game_totalbudget WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        if(!empty($res)){
            $res = $res[0];
        }else{
            $res = '';
        }
        return $res;
    }

    /**
     *  把复制游戏推广计划的总限额插入到新的计划下
     */
    public function gameCopyAdd($params,$new_pid)
    {
        $data = array(
            'pid' => $new_pid,
            'totalbudget' => $params['totalbudget'],
            'num' => $params['num'],
        );

        Db::name('game_totalbudget')->insert($data);
    }

    /**
     *  获取复制计划下面的单价模板
     */
    public function PriceCopy($pid)
    {
        $sql = 'SELECT tpl_id,size,price_name,gradation,price,price_1,price_2,price_3,price_4,price_5,pricedv,template_name,ctime FROM lz_plan_price WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 把单价模板插入到新计划下面
     */
    public function PriceCopyAdd($data,$new_pid)
    {
        $data['pid'] = $new_pid;
        $res = Db::name('plan_price')->insert($data);
        return $res;
    }

    /**
     *  查询复制计划下面的广告
     */
    public function adsCopy($pid)
    {
        $sql = 'SELECT adname,text_chain,pid,uid,tpl_id,tc_id,width,height,files,imageurl,url,priority,adinfo,web_deduction,adv_deduction,status,ctime FROM lz_ads WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 把查询到的广告插入到新计划下面
     */
    public function adsCopyAdd($data,$new_pid)
    {
        $data['pid'] = $new_pid;
        $res = Db::name('ads')->insert($data);
        return $res;
    }


    /**
     *  获取新复制的计划下面的单价 查询在插入到复制广告关联的单价字段
     */
    public function priceSelect($pid)
    {
        $sql = 'SELECT id,pid,size,tpl_id FROM lz_plan_price WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 在修改到复制广告关联的单价字段里
     */
    public function adsCopyUpdate($id,$adid)
    {
        $data['tc_id'] = $id;
        $res = Db::name('ads')->where('ad_id',$adid)->update($data);
        return $res;
    }

    /**
     *  查询新复制计划下面的广告
     */
    public function adsSelect($pid)
    {
        $sql = 'SELECT ad_id,pid,tpl_id,width,height FROM lz_ads WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 得到列表
     * param data
     */
    public function gamegetLst($offset,$count,$params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,a.status,a.ads_sel_status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,
         a.deduction,a.web_deduction,c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b ON a.class_id=b.class_id LEFT JOIN
         lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }
            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" and a.type=1 ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[$offset,$count]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.checkplan LIKE "%'.$mobile.'%" and a.type=1 ORDER BY a.pid DESC LIMIT ?,? ';

            }else{
                $sql.= 'WHERE a.type=1 ORDER BY a.pid DESC LIMIT ?,? ';

            }
            $res = Db::query($sql,[$offset,$count]);
        }
        return $res;
    }

    /**
     * 得到锁定计划列表
     * param data
     */
    public function getLstOne($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.status,a.ads_sel_status,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }
            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.status=? AND a.type!=2 ORDER BY a.pid DESC ';

            $res = Db::query($sql,[0]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.' AND a.type!=2 ORDER BY a.pid DESC ';

            }else{
                $sql.= 'WHERE a.status=? AND a.type!=2 ORDER BY a.pid DESC ';
            }
            $res = Db::query($sql,[0]);
        }
        return $res;
    }

    /**
     * 得到锁定计划列表 游戏推广
     * param data
     */
    public function gamegetLstOne($offset,$count,$params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.status,a.ads_sel_status,
        c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }

            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.type=1 AND a.status=? ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[0,$offset,$count]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.type=1 AND a.checkplan LIKE "%'.$mobile.'%" ORDER BY a.pid DESC LIMIT ?,? ';
            }else{
                $sql.= 'WHERE a.status=? AND a.type=1 ORDER BY a.pid DESC LIMIT ?,? ';

            }
            $res = Db::query($sql,[0,$offset,$count]);
        }
        return $res;
    }

    /**
     * 得到活动计划列表
     * param data
     */
    public function getLstTwo($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.ads_sel_status,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }
            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.status=? AND a.type!=2 ORDER BY a.pid DESC ';

            $res = Db::query($sql,[1]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.' AND a.type!=2 ORDER BY a.pid DESC ';
            }else{
                $sql.= 'WHERE a.status=? AND a.type!=2 ORDER BY a.pid DESC ';
            }

            $res = Db::query($sql,[1]);
        }
        return $res;
    }

    /**
     * 得到活动计划列表  游戏推广
     * param data
     */
    public function gamegetLstTwo($offset,$count,$params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.ads_sel_status,
        c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }

            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.type=1 AND a.status=? ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[1,$offset,$count]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.type=1 AND a.checkplan LIKE "%'.$mobile.'%" ORDER BY a.pid DESC LIMIT ?,? ';

            }else{
                $sql.= 'WHERE a.status=? AND a.type=1 ORDER BY a.pid DESC LIMIT ?,? ';

            }

            $res = Db::query($sql,[1,$offset,$count]);
        }
        return $res;
    }

    /**
     * 今天统计表所有的计划数据
     * */
    public function allList($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.ads_sel_status,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid ';
        if(!empty($params['mobile'])){
            $mobile = $params['mobile'];
            $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.' AND a.type!=2 ORDER BY a.pid';
        }else{
            $sql.= 'WHERE a.status=? AND a.type!=2 ORDER BY a.pid';
        }

        $res = Db::query($sql,[3]);
        return $res;
    }

    /**
     * 今天统计表所有的计划数据   游戏推广
     * */
    public function gameallList($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.ads_sel_status,
        c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid ';
        if(!empty($params['mobile'])){
            $mobile = $params['mobile'];
            $sql.= 'WHERE a.status=? AND a.type=1 AND a.checkplan LIKE "%'.$mobile.'%" ORDER BY a.pid';

        }else{
            $sql.= 'WHERE a.status=? AND a.type=1 ORDER BY a.pid';
        }

        $res = Db::query($sql,[3]);
        return $res;
    }

    // //超过限额的计划提示列表
    // public function quotaList($text_pid,$day)
    // {
    //     $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.budget,a.clearing,
    //     a.status,a.restrictions,a.sitelimit,a.checkplan,a.mobile_price,a.priority,
    //     c.username,b.class_name,b.type,d.sumadvpay FROM lz_plan as a LEFT JOIN lz_classes as b
    //     ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_stats_new AS d ON a.pid=d.pid WHERE a.pid IN ('.$text_pid.') AND d.day=? GROUP BY a.plan_name ORDER BY  a.pid DESC ';
    //     $res = Db::query($sql,[$day]);
    //     return $res;
    // }

    /**
     * 得到待审计划列表
     * param data
     */
    public function getLstThree($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.ads_sel_status,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }
            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.status=? AND a.type!=2 ORDER BY a.pid DESC ';
            $res = Db::query($sql,[2]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.' AND a.type!=2 ORDER BY a.pid DESC ';
            }else{
                $sql.= 'WHERE a.status=? AND a.type!=2  ORDER BY a.pid DESC ';
            }

            $res = Db::query($sql,[2]);
        }
        return $res;
    }

    /**
     * 得到待审计划列表  游戏推广
     * param data
     */
    public function gamegetLstThree($offset,$count,$params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.run_terminal,a.plan_type,a.budget,a.clearing,
        a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.ads_sel_status,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }

            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" AND a.type=1 AND a.status=? ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[2,$offset,$count]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.type=1 AND a.checkplan LIKE "%'.$mobile.'%" ORDER BY a.pid DESC LIMIT ?,? ';

            }else{
                $sql.= 'WHERE a.status=? AND a.type=1 ORDER BY a.pid DESC LIMIT ?,? ';

            }

            $res = Db::query($sql,[2,$offset,$count]);
        }
        return $res;
    }

    /**
     *  新建广告下得到计划列表
     * param data
     */
    public function getAll()
    {
        $sql = 'SELECT pid,plan_name,plan_type,uid,click_status FROM lz_plan  ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  新建文字广告下得到计划列表
     * param data
     */
    public function gettextAll()
    {
        $sql = 'SELECT a.pid,a.plan_name,a.plan_type,a.uid,b.price_name FROM lz_plan as a LEFT JOIN lz_plan_price as b ON a.pid = b.pid ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查询pid是否存在
     * param data
     */
    public function getPid($params)
    {
        $sql = 'SELECT plan_name,uid FROM lz_plan where pid=? ';
        $res = Db::query($sql,[$params]);
        return $res;
    }
    /**
     * 所有计划列表页数
     */
    public function planLstCount($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }
            $sele = $params['search'];
            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%" ';
            $res = Db::query($sql);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.run_terminal='.$mobile.'';
            }
            $res = Db::query($sql);
        }
        return $res['0']['count'];
    }

    /**
     * 所有计划列表页数  游戏推广
     */
    public function gameplanLstCount($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }
            $sele = $params['search'];
            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%"  and a.type=1';
            $res = Db::query($sql);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.checkplan LIKE "%'.$mobile.'%" and a.type=1';
            }else{
                $sql.= 'WHERE a.type=1';
            }
            $res = Db::query($sql);
        }
        return $res['0']['count'];
    }

    /**
     * 锁定计划列表页数
     */
    public function planLstCount1($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,a.status,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            $sele = $params['search'];
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }
            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%" AND a.status=?';
            $res = Db::query($sql,[0]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.'';
            }else{
                $sql.= 'WHERE a.status=?';
            }
            $res = Db::query($sql,[0]);
        }
        return $res['0']['count'];
    }

    /**
     * 锁定计划列表页数
     */
    public function gameplanLstCount1($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,a.status,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            $sele = $params['search'];
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }

            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%" AND a.type=1 AND a.status=?';
            $res = Db::query($sql,[0]);
        }else{
            if(!empty($params['mobile'])){

                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.type=1 AND a.checkplan LIKE "%'.$mobile.'%"';
            }else{

                $sql.= 'WHERE a.status=? AND a.type=1';
            }
            $res = Db::query($sql,[0]);
        }
        return $res['0']['count'];
    }

    /**
     * 活动计划列表页数
     */
    public function planLstCount2($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,a.status,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            $sele = $params['search'];
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }
            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%" AND a.status=?';
            $res = Db::query($sql,[1]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.'';
            }else{
                $sql.= 'WHERE a.status=?';
            }
            $res = Db::query($sql,[1]);
        }
        return $res[0]['count'];
    }

    /**
     * 活动计划列表页数  游戏推广
     */
    public function gameplanLstCount2($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,a.status,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            $sele = $params['search'];
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }

            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%" AND a.type=1  AND a.status=?';
            $res = Db::query($sql,[1]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.type=1  AND a.checkplan LIKE "%'.$mobile.'%"';

            }else{
                $sql.= 'WHERE a.status=? AND a.type=1';

            }
            $res = Db::query($sql,[1]);
        }
        return $res[0]['count'];
    }

    /**
     * 待审计划列表页数
     */
    public function planLstCount3($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,a.status,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            $sele = $params['search'];
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }
            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%" AND a.status=?';
            $res = Db::query($sql,[2]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.run_terminal='.$mobile.'';
            }else{
                $sql.= 'WHERE a.status=? ';
            }

            $res = Db::query($sql,[2]);
        }
        return $res[0]['count'];
    }

    /**
     * 待审计划列表页数  游戏推广
     */
    public function gameplanLstCount3($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,a.status,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            $sele = $params['search'];
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }

            $sql.=' WHERE '.$sort.' AND a.type=1 LIKE "%'.$sele.'%" AND a.status=?';
            $res = Db::query($sql,[2]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.status=? AND a.type=1 AND a.checkplan LIKE "%'.$mobile.'%"';

            }else{
                $sql.= 'WHERE a.status=? AND a.type=1';

            }

            $res = Db::query($sql,[2]);
        }
        return $res[0]['count'];
    }

    /**
     * 更新状态
     * param pid 计划id status 状态
     */
    public function updateStatus($pid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'pid'=>$pid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 更新广告状态
     * param pid 计划id status 状态
     */
    public function updateAdsStatus($pid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'pid'=>$pid,
        );
        $res = Db::name('ads')->where($map)->update($data);
        return $res;
    }

    /**
     * 查询计划下的所有的广告
     */
    public function getAds($pid)
    {
        $sql = 'SELECT ad_id,tpl_id,width,height FROM lz_ads WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 查询广告位
     */
    public function getAdzone($params)
    {
        $sql = 'SELECT adz_id,show_adid FROM lz_adzone WHERE adtpl_id=? AND width=? AND height=? AND viewtype=?';
        $res = Db::query($sql,[$params['tpl_id'],$params['width'],$params['height'],1]);
        return $res;
    }

    /**
     * 计划列表下更新金额
     * param pid 计划id status 状态
     */
    public function updatePrice($pid,$data)
    {
        $map = array(
            'pid'=>$pid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 计划列表下更新金额
     * param pid 计划id status 状态
     */
    public function updatePriority($pid,$data)
    {
        $map = array(
            'pid'=>$pid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 计划列表下更新扣量
     */
    public function deduction($data)
    {
        $map = array(
            'pid'=>$data['pid'],
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 编辑
     * param data array 修改数据
     */
    public function editOne($data)
    {
        $map = array(
            'pid'=>$data['pid'],
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 编辑
     * param data array 修改数据
     */
    public function editPlan($params,$pid)
    {
        $map = array(
            'pid'=>$pid,
        );
        $data = array_filter($params);
        $data['restrictions'] = $params['restrictions'];
        $data['sitelimit'] = $params['sitelimit'];
        $data['delay_show_status'] = $params['delay_show_status'];
        $data['run_terminal'] = $params['run_terminal'];
        $data['type'] = empty($params['type']) ? '0' : $params['type'];
        $data['click_status'] = empty($params['click_status']) ? '0' : $params['click_status'];
        $data['percent'] = empty($params['percent']) ? '0' : $params['percent'];
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 更新游戏推广计划的总限额
     * param data array 修改数据
     */
    public function editgamebudget($params)
    {
        $sql = 'SELECT pid FROM lz_game_totalbudget WHERE pid=?';
        $budget = Db::query($sql,[$params['pid']]);
        $data['totalbudget'] = $params['totalbudget'];
        if(!empty($budget)){
            $map = array(
                'pid'=>$params['pid'],
            );

            Db::name('game_totalbudget')->where($map)->update($data);
        }else{
            $data['pid'] = $params['pid'];
            Db::name('game_totalbudget')->insert($data);
        }

    }

    /**
     * 编辑
     * param pid 计划id
     */
    public function getOne($pid)
    {
        $sql = 'SELECT a.pid,a.plan_name,a.run_terminal,a.bigpname,a.plan_type,a.budget,a.clearing,a.deduction,
        a.web_deduction,a.status,a.checkplan,a.editmodle,a.restrictions,a.sitelimit,a.mobile_price,a.class_id,
        a.price_info,a.resuid,a.limitsiteid,a.priority,a.delay_show_status,a.type as game_type,a.click_status,a.percent,c.username,b.class_name,
        b.type,c.uid,c.money,a.ads_sel_status,a.ads_sel_views,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid WHERE a.pid=?';
        $res = Db::query($sql,[$pid]);
        if(empty($res)){
            return 0;
        }else{
            return $res;
        }
    }

    /**
     * 删除计划
     * param pid 计划id
     */
    public function delOne($pid)
    {
        $map = array(
            'pid'=>$pid,
        );
        $res = $this::where($map)->delete();

        $sql = 'SELECT pid FROM lz_ads where pid=?';
        $adsRes = Db::query($sql,[$pid]);
        if(empty($adsRes)){
            if($res>0){
                return 1;
            }else{
                return 0;
            }
        }else{
            $res2 = Db::name('ads')->where($map)->delete();
            if($res>0 && $res2>0){
                return 1;
            }else{
                return 0;
            }
        }
    }

    /**
     * 获取所有余额>=0的广告商
     */
    public function getOnekLst()
    {
        $sql = 'SELECT uid,username,money FROM lz_users WHERE money>=0 AND type=2 ; ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 获取由广告商列表跳转的广告商
     */
    public function getkLst($param)
    {
        $sql = 'SELECT uid,username,money FROM lz_users WHERE money>=0 AND type=2 and uid=? ; ';
        $res = Db::query($sql,[$param]);
        return $res;
    }

    /**
     * 获取新增计划时可选择的计费模式
     */
    public function getSetting()
    {
        $sql = 'SELECT cpc,cpm,cpv,cps,cpa FROM lz_setting ; ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 计划列表批量删除
     */
    public function delLst($ids,$idsArr)
    {
        Db::startTrans();
        try {
            $Plan = new Plan;
            $res = $Plan::destroy($ids);

            //批量删除计划相关广告
            foreach ($idsArr as $key => $value) {
                $res2 = $this->_delAdsByPid($value);
            }

            // 提交事务
            Db::commit();
            if($res>0 && $res2>0){
                return 1;
            }else{
                return 0;
            }
        } catch (\PDOException $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    /**
     * 删除计划列表
     */
    private function _delAdsByPid($pid)
    {
        $resnum = 1;
        $sql = 'SELECT pid FROM lz_ads where pid=?';
        $adsRes = Db::query($sql,[$pid]);
        if(empty($adsRes)){
            $resnum = 1;
        }else{
            $map = array(
                'pid'=>$pid,
            );
            $res2 = Db::name('ads')->where($map)->delete();
            if($res2>=0){
                $resnum = 1;
            }else{
                $resnum = 0;
            }
        }
        return $resnum;
    }

    /**
     *  获取计划单价所需的类型和尺寸
     */
    public function getPriceTc()
    {
        $sql = 'SELECT b.tpl_id,b.tplname,a.specs FROM lz_adstyle as a  LEFT JOIN lz_admode as b ON a.tpl_id=b.tpl_id ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 新建单价
     */
    public function addPrice($data)
    {
        $res = Db::name('plan_price')->insert($data);
        return $res;
    }

    /**
     *  获取该计划下所有的单价
     */
    public function getPlanPrice($pid)
    {
        $sql = 'SELECT a.id,a.pid,a.price_name,a.gradation,a.size,a.price,a.price_1,a.price_2,a.price_3,a.price_4,a.price_5,
        a.pricedv,a.template_name,b.plan_name,b.checkplan FROM lz_plan_price as a LEFT JOIN lz_plan as b ON a.pid=b.pid WHERE a.pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     *  单价模板列表
     */
    public function templateList()
    {
        $sql = 'SELECT id,template_name FROM lz_plan_price WHERE pid=0 GROUP BY template_name DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  获取该计划下安卓的所有的单价
     */
    public function getPrice()
    {
        $sql = 'SELECT a.id,a.pid,a.price_name,a.gradation,a.size,a.price,a.price_1,a.price_2,a.price_3,a.price_4,a.price_5,
        a.pricedv,b.plan_name,b.checkplan FROM lz_plan_price as a LEFT JOIN lz_plan as b ON a.pid=b.pid WHERE a.pid=?';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  获取计划单价默认值
     */
    public function getPriceModel($param)
    {
        $sql = 'SELECT price,price_1,price_2,price_3,price_4,price_5,
        pricedv FROM lz_plan_price  WHERE pid=? AND tpl_id=? AND size=? AND template_name=?';
        $res = Db::query($sql,[0,$param[2],$param[1],$param['template_name']]);
        return $res;
    }

    /**
     *  验证该计划单价是否存在
     */
    public function validatePrice($param)
    {
        $sql = 'SELECT id FROM lz_plan_price WHERE pid=? AND price_name=?';
        $res = Db::query($sql,[$param['pid'],$param['price_name']]);
        return $res;
    }

    /**
     *  新建单价模板
     */
    public function templatePrice($param)
    {
        $sql = 'SELECT id FROM lz_plan_price WHERE pid=? AND price_name=? AND template_name=?';
        $res = Db::query($sql,[$param['pid'],$param['price_name'],$param['template_name']]);
        return $res;
    }

    /**
     *  查看单价模板
     */
    public function typeprice()
    {
        $sql = 'SELECT template_name FROM lz_plan_price WHERE template_name !="" AND pid = "0" GROUP BY template_name';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查看模板下面所有的单价尺寸
     */
    public function batchtypeprice($param)
    {
        $sql = 'SELECT id,tpl_id,size,price_name,gradation,price,price_1,price_2,price_3,price_4,price_5,pricedv,template_name
        FROM lz_plan_price WHERE template_name=? AND pid=?';
        $res = Db::query($sql,[$param['template_name'],0]);
        return $res;
    }

    /**
     *  批量新建该模板下面的单价尺寸
     */
    public function batchaddprice($value,$param)
    {
        $time = time();
        $sql = 'INSERT INTO lz_plan_price (pid,tpl_id,size,price_name,gradation,price,price_1,price_2,price_3,price_4,price_5,
        pricedv,template_name,ctime) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $res = Db::query($sql,[$param['pid'],$value['tpl_id'],$value['size'],$value['price_name'],$param['gradation'],
            $value['price'],$value['price_1'],$value['price_2'],$value['price_3'],$value['price_4'],$value['price_5'],
            $value['pricedv'],$value['template_name'],$time]);
        return $res;
    }

    /**
     *  修改计划单价尺寸
     */
    public function editprice($value,$param)
    {
        $sql = 'UPDATE lz_plan_price SET gradation=?,price=?,price_1=?,price_2=?,price_3=?,price_4=?,price_5=?,
        pricedv=? WHERE price_name=? AND pid=?';
        $res = Db::execute($sql,[$param['gradation'],$value['price'],$value['price_1'],$value['price_2'],$value['price_3'],
            $value['price_4'],$value['price_5'],$value['pricedv'],$value['price_name'],$param['pid']]);
        return $res;
    }

    /**
     *  该计划下面所有尺寸的单价
     */
    public function planallprice($param)
    {
        $sql = 'SELECT tpl_id,size,price_name,gradation,price,price_1,price_2,price_3,price_4,price_5,pricedv
        FROM lz_plan_price WHERE pid=?';
        $res = Db::query($sql,[$param['pid']]);
        return $res;
    }

    /**
     *  查看该计划下面是否存在单价模板
     */
    public function typePidprice($pid)
    {
        $sql = 'SELECT template_name FROM lz_plan_price WHERE template_name !="" AND pid=? GROUP BY template_name';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     *  批量删除新建计划单价
     */
    public function batDelPrice($params)
    {
        $map = array(
            'id'=>$params,
        );
        $res = Db::name('plan_price')->where($map)->delete();
        return $res;
    }

    /**
     *  批量删除单价模板
     */
    public function templateDelPrice($params)
    {
        $map = array(
            'template_name'=>$params,
            'pid'=>0,
        );
        $res = Db::name('plan_price')->where($map)->delete();
        return $res;
    }

    /**
     *  查询新建单价下的广告
     */
    public function priceAds($params)
    {
        $sql = 'SELECT a.pid,a.size,b.pid AS id,b.width,b.height FROM lz_ads AS b LEFT JOIN lz_plan_price AS a ON b.tc_id=a.id WHERE a.id IN (?)';
        $res = Db::query($sql,[$params]);
        return $res;
    }


    /**
     *  得到类型和尺寸
     */
    public function getTd($id)
    {
        $sql = 'SELECT id,pid,price_name,pricedv,gradation,price,price_1,price_2,price_3,price_4,price_5,template_name FROM lz_plan_price WHERE id=?';
        $res = Db::query($sql,[$id]);
        return $res;
    }


    /**
     *  编辑更新计划单价至数据表
     */
    public function updateOne($param)
    {
        $map = array(
            'id'=>$param['id'],
        );
        $res = Db::name('plan_price')->where($map)->update($param);
        return $res;
    }

    /**
     * 计划分类查询
     */
    public function planclassList()
    {
        $one =Db::query('SELECT * FROM lz_classes WHERE type=2 ORDER BY class_id DESC');
        return $one;
    }

    /**
     *  游戏推广客服列表
     **/
    public function gameList($type){
        $sql = 'SELECT uid,username FROM lz_users WHERE type=?';
        $res = Db::query($sql,[$type]);
        return $res;
    }

    /**
     *  计划分配表
     **/
    public function gameone($data){
        $sql = 'SELECT pid,uid FROM lz_plan_associated WHERE pid=?';
        $res = Db::query($sql,[$data['pid']]);
        return $res;
    }

    /**
     *  修改该计划分配的客服
     **/
    public function gameEdit($data){
        $sql = 'UPDATE lz_plan_associated SET uid=? WHERE pid=?';
        Db::execute($sql,[$data['uid'],$data['pid']]);
    }

    /**
     *  添加该计划分配的客服
     **/
    public function gameAdd($data){
        $sql = 'INSERT INTO lz_plan_associated (pid,uid) VALUES (?,?)';
        Db::query($sql,[$data['pid'],$data['uid']]);
    }

    /**
     * 添加计划分类
     */
    public  function planclassadd($data)
    {
        $inst = Db::name('classes')->insert($data);
        return $inst;
    }

    /**
     * 计划分类修改默认
     */
    public function planclassone($classid)
    {
        $sql ='SELECT * FROM lz_classes WHERE class_id=? ORDER BY class_id DESC';
        $res = Db::query($sql,[$classid]);
        return $res[0];
    }

    /**
     * 编辑计划分类
     */
    public function planclassedit($data,$params)
    {
        $map = array(
            'class_id'=>$params['classid'],
        );
        $res = Db::name('classes')->where($map)->update($data);
        return $res;
    }

    /**
     * 计划分类删除
     */
    public function planclassdel($classid)
    {
        $map = array(
            'class_id'=>$classid,
        );
        $res = Db::name('classes')->where($map)->delete();
        return $res;
    }

    /**
     * 计划投放选择机型：
     * 获取机型
     */
    public function getModle()
    {
        $sql ='SELECT name,notice,type,type_pay FROM lz_modle';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 获取广告商昨日今日消耗
     */
    public function advMoney($params)
    {
        $day = date('Y-m-d');
        if($params['day'] == $day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }

        $sql ='SELECT SUM(sumadvpay) AS sumadvpay,adv_id FROM '.$stats.' WHERE day=? AND adv_id=?';
        $res = Db::query($sql,[$params['day'],$params['adv_id']]);
        return $res;
    }

    //计划编辑时 获取所有广告商
    public function ad_user(){
        $sql = 'SELECT uid,username,money FROM lz_users WHERE money>=0 AND type=2';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 计划补消耗，数据报表添加数据
     */
    public function insertToStats($params)
    {
        $ad_id = -time();//随机一个不重复的广告id，为了防止用户一天修改两次统计表数据，唯一索引会导致出错
        Db::startTrans(); //启动事务
        try {
            $sql = 'INSERT INTO lz_stats_new (pid,adv_id,ad_id,views,sumadvpay,sumprofit,day) VALUES (?,?,?,?,?,?,?);';
            Db::execute($sql, [$params['pid'], $params['adv_id'], $ad_id, 1, $params['money'], $params['money'], $params['day']]);
            $sql = 'INSERT INTO lz_stats_log (pid,adv_id,ad_id,views,sumadvpay,sumprofit,day) VALUES (?,?,?,?,?,?,?);';
            $res = Db::execute($sql, [$params['pid'], $params['adv_id'], $ad_id, 1, $params['money'], $params['money'], $params['day']]);
            Db::commit(); //提交事务
        } catch (\Exception $e) {
            Db::rollback(); //回滚事务
        }
        return $res;
    }

    /**
     * 计划补消耗，减去广告商消耗
     */
    public function updateUserMoney($params)
    {
        $sql = 'UPDATE lz_users SET money=money-? WHERE uid=?';
        $res = Db::execute($sql, [$params['money'], $params['adv_id']]);
        return $res;
    }

    /**
     * 获取广告商id
     */
    public function getAdvId($params)
    {
        $sql = 'SELECT uid FROM lz_plan WHERE pid=?';
        return Db::query($sql, [$params['pid']]);
    }

}