<?php
/**
 * 计划
 * @date   2018-07-01
 */
namespace app\admin\model;
use think\Db;
header("Content-type: text/html; charset=utf-8"); 
class Newtest extends \think\Model
{
    /**
     * 得到测试计划列表
     * param data
     */
    public function getLst($params)
    {
        $sql = 'SELECT a.pid,a.uid,a.type,a.run_terminal,a.plan_name,a.plan_type,a.budget,a.clearing,a.status,a.ads_sel_status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,
         a.deduction,a.web_deduction,c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b ON a.class_id=b.class_id LEFT JOIN 
         lz_users as c ON a.uid=c.uid ';
         if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }

            if($params['types'] == 'all'){
                $sql.= 'WHERE '.$sort.' LIKE "%'.$sele.'%" and a.type = 2  ORDER BY a.pid DESC';
            }else{
                $sql.= 'WHERE '.$sort.' LIKE "%'.$sele.'%" and a.type = 2 AND a.status="'.$params['types'].'" ORDER BY a.pid DESC';
            }
            $res = Db::query($sql);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                if($params['types'] == 'all'){
                    $sql.= 'WHERE  a.run_terminal='.$mobile.' and a.type = 2 ORDER BY a.pid DESC';
                }else{
                    $sql.= 'WHERE  a.run_terminal='.$mobile.' and a.type = 2 AND a.status="'.$params['types'].'" ORDER BY a.pid DESC';
                }
            }else{
                if($params['types'] == 'all'){
                    $sql.= 'WHERE a.type = 2 ORDER BY a.pid DESC ';
                }else{
                    $sql.= 'WHERE a.type = 2 AND a.status="'.$params['types'].'" ORDER BY a.pid DESC ';
                }

            }
            $res = Db::query($sql);
        }
        return $res; 
    }

    /**
     * 审核测试  列表
     * 站长审核 - 广告位审核
     */
    public function audit(){
        $sql = 'SELECT id as a_id,adz_id,u_id,type,time,status,user as username,shenhe,ad_id FROM lz_user_test ORDER BY time DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 提交测试 待审核
     * 状态 审核1 一审和2
     */

    public function user_test($date){
        Db::name('user_test')->insert($date);
        //返回插入数据的id
        $res = Db::name('user_test')->getLastInsID();
        return $res;
    }

    //申请过的更新时间
    public function updateTest($param){
        $data = array(
            'time' => time(),
            'shenhe'=>$param['shenhe'],
            'user'=>$param['user']
        );
        $where = array(
            'type'=>$param['type'],
            'status'=>$param['status']
        );
        if(isset($param['adz_id'])){
            $where['adz_id'] = $param['adz_id'];
            $res = DB::name('user_test')->WHERE($where)->update($data);
        }else{
            $where['u_id'] = $param['u_id'];
            $res = DB::name('user_test')->WHERE($where)->update($data);
        }

        return $res;
    }

    //查询有无二次申请过
    public function getTest($params){
        $sql = 'SELECT id as a_id,adz_id,u_id,type,time,status,user as username,shenhe,ad_id FROM lz_user_test WHERE u_id=? AND type=?';
        $res = Db::query($sql,[$params['u_id'],$params['type']]);
        return $res;
    }

    //查询有无二次申请过
    public function getadzTest($params){
        $sql = 'SELECT id as a_id,adz_id,u_id,type,time,status,user as username,shenhe,ad_id FROM lz_user_test WHERE adz_id=? AND type=?';
        $res = Db::query($sql,[$params['adz_id'],$params['type']]);
        return $res;
    }

   //删除审核数据， 单条
    public function del_audit($id){

        $map = array(
            'id'=>$id,
        );
        $res = Db::name('user_test')->where($map)->delete();
        return $res;
    }


    //查询审核列表
    public function find_user($id)
    {
        //获取计划根据类型匹配计划名称
        $data['id'] = $id;
        $re = Db::name('user_test')->where($data)->find();
        return $re;
    }

    //激活 未审核数据，单条
    public function edit_audit($res,$typename){
        //只匹配锁定状态下的 测试计划
        $sql= 'SELECT pid,plan_name,status,checkplan FROM lz_plan WHERE type = 2 AND status=0 AND plan_name LIKE "%'.$typename.'%"';
        $like_plan = Db::query($sql);
        return $like_plan;
       
    }

    //更新匹配到的计划数据
    public function updatePlan($where,$data){
        $res = Db::name('plan')->where($where)->update($data);
        return $res;
    }

    //审核激活测试站长请求
    public function updateWeb($id,$pid){
        $where = array('id'=>$id);
        $map = array(
            'ad_id'=>$pid,
            'shenhe'=> 2,
        );
        $res = Db::name('user_test')->where($where)->update($map);
        return $res;
    }


    //锁定 审核状态下的数据 单条
    public function edit_audit_s($id){
        $map = array(
            'shenhe' => 1,
            'time' =>time(),
        );
        $where = array(
            'id'=>$id,
        );
        $res = Db::name('user_test')->where($where)->update($map);
        return $res;
    }
 /**************************************************星级管理  model************************************/
    public function starData($data){
        if($data['type'] != '0'){
            $res = Db::name('star')->where($data)->find();
        }else{
            $res = Db::name('star')->find();
        }
        return $res;
    }

    public function statInfo()
    {
        $res = Db::table('lz_stat_copy')->select();
        return $res;
    }

    public function AddStar($param)
    {
        $id = Db::name('stat_copy')->insertGetId(['type'=>$param['type']]);
        unset($param['type']);
        $param['type'] = $id;
        $res = Db::name('star')->insert($param);
        return $res;
    }

    //星级 表
    public function editStar($param,$where){
        $res = Db::name('star')->where($where)->update($param);
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
        $res = Db::name('plan')->where($map)->update($data);
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
     * 计划列表下更新金额
     * param pid 计划id status 状态
     */
    public function updatePriority($pid,$data)
    {
        $map = array(
            'pid'=>$pid,
        );
        $res = Db::name('plan')->where($map)->update($data);
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
        $res = Db::name('plan')->where($map)->update($data);
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
        $res = Db::name('plan')->where($map)->update($data);
        return $res;
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
            'type' => 2
        );
        $res = Db::name('plan')->where($map)->delete();
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

    //计划编辑时 获取所有广告商
    public function ad_user(){
        $sql = 'SELECT uid,username,money FROM lz_users WHERE money>=0 AND type=2';
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
     * 计划分类查询
     */
    public function planclassList()
    {
        $one =Db::query('SELECT * FROM lz_classes WHERE type=2 ORDER BY class_id DESC');
        return $one;
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
    //手动添加下载数
    public function adv_download($id,$data)
    {
        $map = array(
            'id'=>$id,
        );
        $res = Db::table('lz_adv_cs')->where($map)->find();
        $update = [
            'download' => $data['download'],
            'cost'     => @round($res['sumadvpay']/$res['download']*100,2),
        ];
        $up = Db::table('lz_adv_cs')->where($map)->update($update);
        return $up;
    }
    //扣量备注
    public function adv_downloads($id,$data)
    {
        $map = array(
            'id'=>$id,
        );
        $up = Db::table('lz_adv_cs')->where($map)->update($data);
        return $up;
    } 
    //备注
    public function adv_download_s($id,$data)
    {
        $map = array(
            'id'=>$id,
        );
        $up = Db::table('lz_adv_cs')->where($map)->update($data);
        return $up;
    }

    /** *****************************测试站长报表 model***********************************************/

    //测试站长报表数据
    public function getWebtoday($params,$uid)
    {
        $sql = 'SELECT a.day,a.pid,a.uid,SUM(a.views) AS views,SUM(a.click_num) AS click_num,SUM(a.sumadvpay) AS sumadvpay,b.pid,b.plan_name,c.type,c.id,e.username from lz_stats_log AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_user_test AS c ON a.uid=c.u_id LEFT JOIN lz_users AS d ON a.uid=d.uid LEFT JOIN lz_users AS e ON d.serviceid=e.uid ';
        if(empty($uid)){
            $sql.= " WHERE b.type=2 AND a.day=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$params['endday']]);
        }else{
            $sql.= " WHERE b.type=2 AND a.uid=? AND a.day=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$uid,$params['endday']]);
        }
        return $res;
    }

    //测试站长报表数据
    public function getWeb($params,$uid)
    {
        $sql = 'SELECT a.day,a.pid,a.uid,SUM(a.views) AS views,SUM(a.click_num) AS click_num,SUM(a.sumadvpay) AS sumadvpay,b.pid,b.plan_name,c.type,c.id,e.username from lz_stats_new AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_user_test AS c ON a.uid=c.u_id LEFT JOIN lz_users AS d ON a.uid=d.uid LEFT JOIN lz_users AS e ON d.serviceid=e.uid ';
        if(empty($uid)){
            $sql.= " WHERE b.type=2 AND a.day>=? AND a.day<=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$params['startday'],$params['endday']]);
        }else{
            $sql.= " WHERE b.type=2 AND a.uid=? AND a.day>=? AND a.day<=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$uid,$params['startday'],$params['endday']]);
        }
        return $res;
    }


/*******************************测试广告位  model************************************************************/
  //测试广告位报表数据
    public function getAdztoday($params,$id)
    {
        $sql = 'SELECT a.day,a.pid,a.uid,a.adz_id,SUM(a.views) AS views,SUM(a.click_num) AS click_num,SUM(a.sumadvpay) AS sumadvpay,b.pid,b.plan_name,c.type,c.id,c.adz_id,e.username from lz_stats_log AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_user_test AS c ON a.adz_id=c.adz_id LEFT JOIN lz_users AS d ON a.uid=d.uid LEFT JOIN lz_users AS e ON d.serviceid=e.uid ';
        if(empty($id)){
            $sql.= " WHERE b.type=2 AND a.day=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$params['endday']]);
        }else{
            $sql.= " WHERE b.type=2 AND a.adz_id=? AND a.day=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$id,$params['endday']]);
        }
        return $res;
    }

    //测试广告位报表数据
    public function getAdz($params,$id)
    {
        $sql = 'SELECT a.day,a.pid,a.uid,a.adz_id,SUM(a.views) AS views,SUM(a.click_num) AS click_num,SUM(a.sumadvpay) AS sumadvpay,b.pid,b.plan_name,c.type,c.id,c.adz_id,e.username from lz_stats_new AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_user_test AS c ON a.adz_id=c.adz_id LEFT JOIN lz_users AS d ON a.uid=d.uid LEFT JOIN lz_users AS e ON d.serviceid=e.uid ';
        if(empty($id)){
            $sql.= " WHERE b.type=2 AND a.day>=? AND a.day<=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$params['startday'],$params['endday']]);
        }else{
            $sql.= " WHERE b.type=2 AND a.adz_id=? AND a.day>=? AND a.day<=? AND c.type!='' GROUP BY a.adz_id,a.pid,a.day,c.type";
            $res = Db::query($sql,[$id,$params['startday'],$params['endday']]);
        }
        return $res;
    }


    //查询站长的激活数
    public function getActivte($params){
        $sql = 'SELECT uid,pid,adz_id,day,activt_num,deduction_info,info FROM lz_stats_test WHERE uid=? AND pid=? AND day=?';
        $res = Db::query($sql,[$params['uid'],$params['pid'],$params['day']]);
        if(empty($res)){
            return '';
        }else{
            
            return $res['0'];
        }
    }


    //查询广告位的激活数
    public function getAdzActivte($params){
        $sql = 'SELECT uid,pid,adz_id,day,activt_num,deduction_info,info FROM lz_stats_test WHERE adz_id=? AND pid=? AND day=?';
        $res = Db::query($sql,[$params['adz_id'],$params['pid'],$params['day']]);
        if(empty($res)){
            return '';
        }else{
            
            return $res['0'];
        }
    }


    //查询星级
    public function getStar(){
        $sql = 'SELECT type,yistar,erstar,sanstar,sistar,wustar FROM lz_star';
        $res = Db::query($sql);
        return $res;
    }
    

    //查询需要修改的数据是否存在
    public function getwebStats($params){
        $sql = 'SELECT pid,uid,adz_id,day,activt_num,deduction_info,info FROM lz_stats_test WHERE pid=? AND uid=? AND day=?';
        $res = Db::query($sql,[$params['pid'],$params['uid'],$params['day']]);
        return $res;
    }

    //广告位查询需要修改的数据是否存在
    public function getAdzStats($params){
        $sql = 'SELECT pid,uid,adz_id,day,activt_num,deduction_info,info FROM lz_stats_test WHERE pid=? AND adz_id=? AND day=?';
        $res = Db::query($sql,[$params['pid'],$params['adz_id'],$params['day']]);
        return $res;
    }


    //报表页面修改数据插入
    public function testInster($data){
        $res = Db::name('stats_test')->insert($data);
        return $res;
    }

    //报表页面修改数据 更新
    public function testUpdate($data,$where){
        $res = db::name('stats_test')->where($where)->update($data);
        return $res;
    }

}