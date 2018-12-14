<?php
namespace app\home\model;
use think\Model;
use think\Db;

class Advertiser extends Model
{
/***************************************************我的首页*****************************************************/
    /**
     * 获取当天报告
     */
    public function reportNow($param)
    {
        /*$sql = 'SELECT a.views,a.num,a.sumadvpay,a.plan_type,a.adv_deduction,b.money,d.pricedv FROM lz_stats_log AS a LEFT JOIN lz_users AS b
        ON a.adv_id=b.uid LEFT JOIN lz_plan AS c ON a.pid=c.pid LEFT JOIN lz_plan_price AS d ON a.tc_id = d.id WHERE a.adv_id=? AND a.day=? AND a.adv_id!=0 ORDER BY a.plan_type';*/
        $sql = 'SELECT SUM(sumadvpay) AS sumadvpay,plan_type FROM lz_stats_log WHERE adv_id=? AND day=?';
        $res = Db::query($sql,[$param['uid'],$param['day']]);

        return $res;
    }

    /**
     * 获取昨日支付
     */
    public function getYesMoney($param)
    {
        /*$sql = 'SELECT a.sumadvpay,a.views,a.num,a.adv_deduction,c.pricedv FROM lz_stats_log AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c ON a.tc_id = c.id WHERE a.adv_id=? AND a.day=? AND a.adv_id!=0 ';*/
        $sql = 'SELECT SUM(sumadvpay) AS sumadvpay,adv_id FROM lz_stats_log WHERE adv_id=? AND day=?';
        $res = Db::query($sql,[$param['uid'],$param['yesterday']]);

        return $res;
    }

    /**
     * 获取当月支付
     */
    public function getMonthMoney($param)
    {
        $sql = 'SELECT SUM(a.sumadvpay) AS sumadvpay FROM lz_stats_new AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c ON a.tc_id = c.id WHERE a.adv_id=? AND a.day>=? AND a.day<=? ';
        $res = Db::query($sql,[$param['uid'],$param['firstday'],$param['yesterday']]);
        return $res;
    }

    /**
     * 获取广告商余额
     */
    public function advMoney($uid)
    {
        $sql = 'SELECT money FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        if (empty($res)) {
            return 0;
        }else{
            return $res['0'];
        }
    }

    /**
     * 获取广告商stats当日消耗(已无用)
     */
    public function advStats($uid,$date)
    {
       // $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay),a.plan_type,a.web_deduction,b.money,c.price FROM lz_stats_log AS a LEFT JOIN lz_users AS b ON a.uid=b.uid LEFT JOIN lz_plan_price AS c ON a.tc_id=c.id WHERE a.adv_id=? AND a.day=? ORDER BY plan_type';
        $sql = 'SELECT SUM(sumadvpay) as sumadvpay FROM lz_stats_log WHERE adv_id=? AND day=? ';
        $res = Db::query($sql,[$uid,$date]);
        //dump(db::getlastsql());exit
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }

    }
    
    

    /**
     * 获取账户设置的基本信息
     */
    public function getBasic($uid)
    {
        $sql = 'SELECT uid,username,password,mobile,qq,email,tel,idcard FROM lz_users WHERE uid=? ';
        $res = Db::query($sql,[$uid]);

        return $res;
    }

    /**
     *  修改账户的基本信息
     */
    public  function accountEdit($uid,$data)
    {
        $map = array(
            'uid'=>$uid,
        );
        $res = Db::NAME('users')->WHERE($map)->UPDATE($data);
        Db::NAME('users_log')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     *  查询当前密码
     */
    public function getPwd($uid)
    {
        $map=array(
            'uid' =>$uid,
        );
        $res = Db::NAME('users')->WHERE($map)->FIND();
        return $res;
    }

    /**
     *   修改密码
     */
    public function passEdit($uid,$data)
    {
        $map=array(
            'uid' =>$uid,
        );
        $res = Db::NAME('users')->WHERE($map)->UPDATE($data);
        Db::NAME('users_log')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     *   充值记录
     */
    public function rechargeLog($params)
    {
        $sql = 'SELECT a.money,a.uid,b.uid,b.id,b.money AS rechargeMoney,b.ctime,b.payinfo FROM lz_users AS a LEFT JOIN lz_paylog AS b 
        ON a.uid=b.uid WHERE a.uid=? AND b.ctime !="" ORDER BY b.id';
        $res = Db::query($sql,[$params['uid']]);
        return $res;
    }

    /**
     * 获取广告商基本余额提醒
     */
    public function getSetAdv()
    {
        $sql = 'SELECT adv_money FROM lz_setting';
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res['0'];
        }
    }

/***************************************************计划管理*****************************************************/
    /**
     * 所有广告列表
     */
    public function planLstCount($param)
    {
        $sql = 'SELECT count(pid) as count FROM lz_plan ';
        if(empty($param['type'])){
            $sql.= ' WHERE uid=? ';
            $res = Db::query($sql,[$param['uid']]);
        }else{
            $sql.= ' WHERE uid=? AND plan_type=? ';
            $res = Db::query($sql,[$param['uid'],$param['type']]);
        }
        return $res[0]['count'];
    }

    /**
     * 得到当页下的数据列表
     */
    public function getPlanLst($offset,$count,$param)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.budget,a.clearing,a.status,
        b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id ';
        if(empty($param['type'])){
            $sql.= ' WHERE a.uid=? ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[$param['uid'],$offset,$count]);
        }else{
            $sql.= ' WHERE a.uid=? AND plan_type=? ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[$param['uid'],$param['type'],$offset,$count]);
        }
        return $res;
    }

    /**
     * 得到所有的数据列表
     */
    public function getPlanAll($param)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.budget,a.clearing,a.status,
        b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id WHERE a.uid=? ORDER BY a.plan_type';

        $res = Db::query($sql,[$param['uid']]);
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
     * 获取分类列表
     */
    public function getLstByType($type = '')
    {
        if(empty($type)){
            $sql = 'SELECT class_id,class_name,type FROM lz_classes ';
            $res = Db::query($sql);
        }else{
            $sql = 'SELECT class_id,class_name,type FROM lz_classes WHERE type=? ';
            $res = Db::query($sql,[$type]);
        }
        return $res;
    }

    /**
     * add data
     * param data
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $data['status'] = 2;
        $res = Db::NAME('plan')->INSERT($data);
        return $res;
    }

    /**
     * 编辑
     * param data array 修改数据
     */
    public function editOne($data,$pid)
    {
        $map = array(
            'pid'=>$pid,
        );
        $res = Db::NAME('plan')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     * 编辑页面的初期显示
     * param pid 计划id
     */
    public function getOne($pid)
    {
        $sql = 'SELECT a.pid,a.plan_name,a.plan_type,a.budget,a.clearing,
        a.deduction,a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.class_id,
        a.price_info,a.resuid,a.limitsiteid,c.username,b.class_name,b.type,c.uid,c.money
        FROM lz_plan as a LEFT JOIN lz_classes as b  ON a.class_id=b.class_id
        LEFT JOIN lz_users as c ON a.uid=c.uid  WHERE a.pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

/***************************************************广告管理*****************************************************/
    /**
     * 所有广告列表
     */
    public function adLstCount($param)
    {
        $sql = 'SELECT count(a.ad_id) as count FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id ';
        if(empty($param['pname'])){
            $sql.= ' WHERE b.uid=? ';
            $res = Db::query($sql,[$param['uid']]);
        }else{
            $sql.= ' WHERE b.uid=? AND b.plan_name=? ';
            $res = Db::query($sql,[$param['uid'],$param['pname']]);
        }
        return $res[0]['count'];
    }

    /**
     * 得到当页下的广告列表
     */
    public function adLst($offset,$count,$param)
    {
        $sql = 'SELECT a.ad_id,a.tpl_id,d.username,d.uid,a.status,b.plan_name,
        a.pid,b.plan_type,a.files,a.imageurl,a.width,a.height,c.tplname FROM lz_ads as a LEFT JOIN lz_plan as b
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid ';
        if(empty($param['pname'])){
            $sql.= ' WHERE b.uid=? ORDER BY a.ad_id DESC LIMIT ?,? ';
            $res = Db::query($sql,[$param['uid'],$offset,$count]);
        }else{
            $sql.= ' WHERE b.uid=? AND b.plan_name=? ORDER BY a.ad_id DESC LIMIT ?,? ';
            $res = Db::query($sql,[$param['uid'],$param['pname'],$offset,$count]);
        }
        return $res;
    }

    /**
     * 获取上传图片的服务器地址
     */
    public function getImgService()
    {
        $sql = 'SELECT img_server FROM lz_setting';
        $res = Db::query($sql);
        $res['0'] =  empty($res) ? '' : $res['0'];
        return  $res['0'];
    }

    /**
     * 得到所有的计划名称
     */
    public function getAdsAll($param)
    {
        $sql = 'SELECT plan_name FROM lz_plan WHERE uid=? ORDER BY plan_name';

        $res = Db::query($sql,[$param['uid']]);
        return $res;
    }

    /**
     * 计划管理下查看广告
     */
    public function adPlanLst($param)
    {
        $sql = 'SELECT a.ad_id,a.tpl_id,d.username,d.uid,a.status,b.plan_name,
        a.pid,b.plan_type,a.imageurl,a.width,a.height,c.tplname FROM lz_ads as a LEFT JOIN lz_plan as b
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid WHERE a.pid=? AND b.uid=? ORDER BY a.ad_id DESC';

        $res = Db::query($sql,[$param['pid'],$param['uid']]);
        return $res;
    }

    /**
     *  新建广告下得到计划列表
     * param data
     */
    public function getAll($uid)
    {
        $sql = 'SELECT pid,uid,plan_name,plan_type FROM lz_plan WHERE uid=? AND status = 1';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 新建广告下广告类型 list
     * param data array 修改数据
     */
    public function adsTypeLst()
    {
        $sql = 'SELECT a.tpl_id,a.adtype_id,a.tplname,a.tpltype,a.customspecs,a.customcolor,a.sort,a.status,a.ctime,
        a.htmlcontrol,b.type_name,b.stats_type FROM lz_admode as a
        LEFT JOIN lz_adtype AS b ON a.adtype_id=b.adtype_id';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  广告类型选择  得到数据
     */
    public function getAdHtml($id)
    {
        $sql = 'SELECT a.tpl_id,a.adtype_id,a.tplname,a.htmlcontrol,b.specs,b.stylename
        FROM lz_admode as a  LEFT JOIN lz_adstyle as b ON a.tpl_id=b.tpl_id WHERE a.tpl_id=?';
        $res = Db::query($sql,[$id]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     * 添加广告
     */
    public function addOne($data)
    {
        $params['pid'] = $data['ads_pid'];
        $params['uid'] = $data['uid'];
        $params['adname'] = $data['ad_name'];
        $params['tpl_id']= $data['adtpl_id'];
        $params['files'] = empty($data['file'])?'':$data['file'];
        $params['imageurl'] = empty($data['imageurl'])?'':$data['imageurl'];
        $params['url'] = empty($data['url'])?'':$data['url'];
        $params['width'] = empty($data['width'])?'':$data['width'];
        $params['height'] = empty($data['height'])?'':$data['height'];
        $params['status'] = 2;
        $params['ctime'] = time();
        $res = Db::NAME('ads')->INSERT($params);
        return $res;
    }

    /**
     * 编辑广告
     */
    public function edit($data,$aid)
    {
        $map = array(
            'ad_id'=>$aid,
        );
        $res = Db::NAME('ads')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     * 编辑
     * param aid 广告id
     */
    public function getAdsOne($aid)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,a.uid,a.priority,a.status,a.adinfo,b.plan_name,
        a.files,a.imageurl,a.url,a.width,a.height,b.plan_type,c.tplname,c.customspecs
        FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id WHERE a.ad_id=? ';
        $res = Db::query($sql,[$aid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     * 编辑广告 下广告类型 list
     * param data array 修改数据
     */
    public function adsTypeOne($tpl_id)
    {
        $sql = 'SELECT a.tpl_id,a.adtype_id,a.tplname,a.tpltype,a.customspecs,a.customcolor,a.sort,a.status,a.ctime,
        a.htmlcontrol,b.type_name,b.stats_type FROM lz_admode AS a
        LEFT JOIN lz_adtype AS b ON a.adtype_id=b.adtype_id WHERE a.tpl_id=?';
        $res = Db::query($sql,[$tpl_id]);
        $res[0] = empty($res[0]) ? '' : $res[0];
        return $res[0];
    }

/***************************************************效果报告*****************************************************/
    /**
     * 获取当天报告
     */
    public function getPerformance($param)
    {
         $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM '.$stats.' AS a LEFT JOIN
        lz_plan as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c ON a.tc_id = c.id  WHERE a.adv_id=? AND a.day>=? AND a.day<=? AND a.adv_id!=0
        GROUP BY a.day ORDER BY a.day DESC,b.plan_name DESC ';
        $res = Db::query($sql,[$param['uid'],$param['startday'],$param['endday']]);

        return $res;
    }

    /**
     * 获取今日数据   综合报告
     */
    public function getTodayPerformance($param)
    {
        $day = date('Y-m-d');
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM lz_stats_log AS a LEFT JOIN
        lz_plan as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c ON a.tc_id = c.id  WHERE a.adv_id=? AND a.day=? AND a.adv_id!=0
        GROUP BY a.day ORDER BY a.day DESC,b.plan_name DESC  ';
        $res = Db::query($sql,[$param['uid'],$day]);
        return $res;
    }

    /**
     * 获取报告   计划报告 和 广告报告
     */
    public function getPerformanceFortype($param)
    {
        $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        if($param['type'] == 'plan'){
            $type = 'a.pid';
        }else{
            $type = 'a.ad_id';
        }
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM '.$stats.' AS a LEFT JOIN
        (SELECT pid,plan_name,bigpname FROM lz_plan WHERE uid=?) as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c
        ON a.tc_id = c.id  WHERE a.adv_id=? AND a.day>=? AND a.day<=? AND a.adv_id!=0 AND b.plan_name!=?
        GROUP BY '.$type.',a.day ORDER BY a.day DESC,b.plan_name DESC  ';
        $res = Db::query($sql,[$param['uid'],$param['uid'],$param['startday'],$param['endday'],'']);

        return $res;
    }

    /**
     * 获取计划报告检索下的当天报告
     */
    public function getPlanPerformance($param)
    {
        $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        $plan_name = ltrim($param['plan_name']);
        $sql = 'SELECT a.views,a.num,sum(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM '.$stats.' AS a LEFT JOIN
        (SELECT pid,plan_name,bigpname FROM lz_plan WHERE uid=?) as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c
        ON a.tc_id =c.id WHERE a.adv_id=? AND plan_name LIKE "%'.$plan_name.'%"
        AND a.day>=? AND a.day<=? AND a.adv_id!=0 AND b.plan_name!=? GROUP BY b.plan_name,a.day ORDER BY a.day DESC,b.plan_name DESC ';

        $res = Db::query($sql,[$param['uid'],$param['uid'],$param['startday'],$param['endday'],'']);
        return $res;
    }

    /**
     * 获取当天报告检索下的当天报告
     */
    public function getAdsPerformance($param)
    {
        $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        $sql = 'SELECT a.views,a.num,sum(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM '.$stats.' AS a LEFT JOIN
        (SELECT pid,plan_name,bigpname FROM lz_plan WHERE uid=?) as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c
        ON a.tc_id =c.id WHERE a.adv_id=? AND a.ad_id=?
        AND a.day>=? AND a.day<=? AND a.adv_id!=0 AND b.plan_name!=? GROUP BY a.day ORDER BY a.day DESC,b.plan_name DESC ';
        $res = Db::query($sql,[$param['uid'],$param['uid'],$param['ad_id'],$param['startday'],$param['endday'],'']);

        return $res;
    }

    /**
     * 获取报告   计划报告 和 广告报告
     */
    public function getTodayPerformanceFortype($param)
    {
        $day = date('Y-m-d');
        if($param['type'] == 'plan'){
            $type = 'a.pid';
        }else{
            $type = 'a.ad_id';
        }
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM lz_stats_log AS a LEFT JOIN
        (SELECT pid,plan_name,bigpname FROM lz_plan WHERE uid=?) as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c
        ON a.tc_id = c.id  WHERE a.adv_id=? AND a.day=? AND a.adv_id!=0 AND b.plan_name!=?
        GROUP BY '.$type.',a.day ORDER BY a.day DESC,b.plan_name DESC  ';
        $res = Db::query($sql,[$param['uid'],$param['uid'],$day,'']);
        return $res;
    }

    /**
     * 获取计划报告检索下的当天报告
     */
    public function getPlanTodayPerformance($param)
    {
        $day = date('Y-m-d');
        $plan_name = ltrim($param['plan_name']);
        $sql = 'SELECT a.views,a.num,sum(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM lz_stats_log AS a LEFT JOIN
        (SELECT pid,plan_name,bigpname FROM lz_plan WHERE uid=?) as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c
        ON a.tc_id =c.id WHERE a.adv_id=? AND plan_name LIKE "%'.$plan_name.'%"
        AND a.day=? AND a.adv_id!=0 AND b.plan_name!=? GROUP BY b.plan_name,a.day ORDER BY a.day DESC,b.plan_name DESC ';

        $res = Db::query($sql,[$param['uid'],$param['uid'],$day,'']);
        return $res;
    }

    /**
     * 获取当天报告检索下的当天报告
     */
    public function getAdsTodayPerformance($param)
    {
        $day = date('Y-m-d');
        $sql = 'SELECT a.views,a.num,sum(a.sumadvpay) as sumadvpay,a.plan_type,a.day,a.pid,a.ad_id,b.plan_name,b.bigpname,c.pricedv FROM lz_stats_log AS a LEFT JOIN
        (SELECT pid,plan_name,bigpname FROM lz_plan WHERE uid=?) as b ON a.pid=b.pid LEFT JOIN lz_plan_price AS c
        ON a.tc_id =c.id WHERE a.adv_id=? AND a.ad_id=?
        AND a.day=? AND a.adv_id!=0 AND b.plan_name!=? GROUP BY a.day ORDER BY a.day DESC,b.plan_name DESC ';
        $res = Db::query($sql,[$param['uid'],$param['uid'],$param['ad_id'],$day,'']);

        return $res;
    }

}
