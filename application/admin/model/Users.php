<?php
/**
 * 会员
 * @date   2016-6-15
 */
namespace app\admin\model;
use think\Db;

class Users extends \think\Model
{
    /**
     * 得到列表 客服/商务公用
     * param data
     */
    public function getList($type,$params)
    {
        $sql ='SELECT a.uid,a.username,a.email,a.qq,a.tel,a.type,a.status,a.serviceid FROM lz_users AS a LEFT JOIN lz_users AS b ON a.serviceid=b.uid ';

        if(!empty($params['search'])){
            $param = $params['search'];
            $sort = 'a.'.$params['type'];
            $sql.= ' WHERE a.type=? AND '.$sort.' like "%'.$param.'%" GROUP BY a.uid ORDER BY a.uid DESC ';
            $res = Db::query($sql,[$type]);
        }else{
            $sql.= ' WHERE a.type=? GROUP BY a.uid ORDER BY a.uid DESC ';
            $res = Db::query($sql,[$type]);
        }
        return $res;
    }
     /**
     * 查询商务当月业绩
     * param data
     */
    public function getSWList($type,$month)
    {
        $day = date('Y-m-d');
        if($month['monthEnd'] == $day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        $sql = 'SELECT  (t3.serviceid) as uid,SUM(money) as money  from (  select SUM(c.sumadvpay) as money ,t2.uid,t2.serviceid from ( SELECT uid,b.serviceid from (  SELECT uid as kefuid,username from lz_users as a  WHERE a.type=?  ) as t1  LEFT JOIN lz_users as b ON t1.kefuid=b.serviceid  ) t2 LEFT JOIN '.$stats.' as c ON t2.uid=c.adv_id WHERE c.day>=? AND c.day <=? GROUP BY c.adv_id  )   t3  GROUP BY t3.serviceid  ORDER BY t3.serviceid ';
        $res = Db::query($sql,[$type,$month['monthBegin'],$month['monthEnd']]);
        // dump(db::getlastsql());exit;
        return $res;
    }

     /**
     * 查询客服当月业绩
     * param data
     */
    public function getKFList($type,$month,$params)
    {
        $day = date('Y-m-d');
        if($month['monthEnd'] == $day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        $sql ='SELECT a.uid,SUM(c.sumpay) AS money FROM lz_users AS a LEFT JOIN lz_users AS b ON b.serviceid=a.uid LEFT JOIN
            (SELECT SUM(sumpay) as sumpay,uid,day FROM '.$stats.' WHERE day>=? AND day<=? GROUP BY uid) as c ON b.uid=c.uid
            WHERE a.type=? AND c.day>=? AND c.day <= ? ';
        if(!empty($params['search'])){
            $param = $params['search'];
            $sort = 'a.'.$params['type'];
            $sql.= ' AND '.$sort.' like "%'.$param.'%" GROUP BY a.uid,b.serviceid';
            $res = Db::query($sql,[$month['monthBegin'],$month['monthEnd'],$type,$month['monthBegin'],$month['monthEnd']]);
        }else{
            $sql.= ' GROUP BY a.uid,b.serviceid ';
            $res = Db::query($sql,[$month['monthBegin'],$month['monthEnd'],$type,$month['monthBegin'],$month['monthEnd']]);
        }
        return $res;
    }
    /**
     * 得到广告商列表
     * param data
     */
    public function getList1($offset,$count,$type,$params)
    {
        $sql ='SELECT b.username AS uname,a.uid,a.username,a.password,a.type,a.settled_amount,a.nosettled_amount,
        a.customer_cervice,a.email,a.qq,a.tel,a.mobile,a.contact,a.idcard,a.money,a.account_name,a.bank_name,
        a.bank_branch,a.bank_card,a.status,a.insite,a.remark,a.serviceid,a.login_time,a.adv_deduction FROM lz_users AS a LEFT JOIN
        lz_users AS b ON a.serviceid=b.uid ';
        if(!empty($params['search'])){
            $param = $params['search'];
            $sort = 'a.'.$params['type'];
            $sql.= ' WHERE a.type=? AND '.$sort.' like "%'.$param.'%" GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,? ';
            $res = Db::query($sql,[$type,$offset,$count]);
        }else{
            $sql.= ' WHERE a.type=? GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,? ';
            $res = Db::query($sql,[$type,$offset,$count]);
        }
        return $res;
    }

    /**
     * 得到广告商每日消耗的
     * param data
     */
    public function advMoney($uid,$date)
    {
        $sql = 'SELECT SUM(sumadvpay) as money FROM lz_stats_new WHERE uid=? AND day=?';
        $res = Db::query($sql,[$uid,$date]);
        if (empty($res)) {
            return 0;
        }else{
            return $res['0'];
        }
    }

    /**
     * 得到站长列表
     * param data
     */
    public function getWebList($offset,$count,$type,$params)
    {
        $sql ='SELECT b.username AS uname,a.uid,a.username,a.password,a.type,a.customer_cervice,a.email,a.qq,a.tel,
        a.mobile,a.contact,a.idcard,a.money,a.account_name,a.bank_name,a.bank_branch,a.bank_card,a.status,a.insite,
        a.remark,a.serviceid,a.login_time,a.web_deduction,a.cpd_type,SUM(c.money) as settled_money FROM lz_users AS a LEFT JOIN lz_users AS b ON
        a.serviceid=b.uid LEFT JOIN lz_paylog AS c ON a.uid=c.uid ';
        if ($params['cpd_type']=='') {
            $typeSql = '';
        }else{
            $typeSql = ' AND a.cpd_type='.$params['cpd_type'].'';
        }
        if(!empty($params['search'])){
            $param = $params['search'];
            if($params['type'] == 'uname'){
                $sort = 'b.username';
            }else{
                $sort = 'a.'.$params['type'];
            }

			$params['status'] = isset($params['status'])? $params['status']: 'users_all';

            if($params['status'] == 'users_all'){
                $sql.= ' WHERE a.type=? AND '.$sort.' like "%'.$param.'%" '.$typeSql.' GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,? ';
                $res = Db::query($sql,[$type,$offset,$count]);
            }else{
                $sql.= ' WHERE a.type=? AND '.$sort.' like "%'.$param.'%" AND a.status = ? '.$typeSql.' GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,? ';
                $res = Db::query($sql,[$type,$params['status'],$offset,$count]);
            }

        }else{

			$params['status'] = isset($params['status'])? $params['status']: 'users_all';

            if($params['status'] == 'users_all'){
                $sql.= ' WHERE a.type=? '.$typeSql.' GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,? ';
                $res = Db::query($sql,[$type,$offset,$count]);
            }else{
                $sql.= ' WHERE a.type=? AND a.status = ? '.$typeSql.' GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,? ';
                $res = Db::query($sql,[$type,$params['status'],$offset,$count]);
            }

        }
        return $res;
    }
    
    /**
     * 获取站长当天收入
     */
    public function webReportNow($params)
    {
        $sql = 'SELECT b.uid,SUM(a.sumpay) as sumpay,SUM(c.cpd) as cpd FROM (SELECT SUM(sumpay) as sumpay,uid,adz_id,day FROM lz_stats_log WHERE day=? GROUP BY adz_id) AS a LEFT JOIN lz_users AS b ON a.uid=b.uid LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy  as c ON c.cpd_day=a.day AND a.adz_id=c.adz_id  WHERE b.type=? GROUP BY b.uid';
        
        $res = Db::query($sql,[$params['day'],1]);
        //dump(db::getlastsql());exit;
        return $res;
    }
    /**
     * 获取站长昨天的收入
     */
    public function webReportYes($param)
    {
        $sql = 'SELECT b.uid,SUM(a.sumpay) as sumpay FROM (SELECT SUM(sumpay) as sumpay,uid,adz_id,day FROM lz_stats_new WHERE day=? GROUP BY adz_id) AS a LEFT JOIN lz_users AS b ON a.uid=b.uid  WHERE b.type=? GROUP BY b.uid';
        $res = Db::query($sql,[$param['yesterday'],1]);
        return $res;
    }

    /**
     * 获取广告商今日消耗(日支出)
     */
    public function advReportNow($param)
    {   
        $sql = 'SELECT (t.adv_id) as uid,SUM(t.sumadvpay) as sumadvpay FROM (SELECT SUM(sumadvpay) AS sumadvpay,adv_id FROM lz_stats_log WHERE day=? GROUP BY adv_id) AS t LEFT JOIN lz_users AS a ON t.adv_id=a.uid WHERE a.type=? GROUP BY t.adv_id';
        //SELECT a.uid,SUM(t.sumadvpay) as sumadvpay FROM lz_users AS a LEFT JOIN (SELECT SUM(sumadvpay) AS sumadvpay,adv_id FROM lz_stats_log WHERE day='2017-07-28' GROUP BY adv_id) AS t ON t.adv_id=a.uid WHERE a.type='2' GROUP BY a.uid ORDER BY a.uid DESC;
        $res = Db::query($sql,[$param['day'],2]);
        return $res;
    }
    /*public function advReportNow($param)
    {
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay),a.plan_type,a.web_deduction,b.money,c.price FROM lz_stats_log AS a LEFT JOIN lz_users AS b
        ON a.uid=b.uid LEFT JOIN lz_plan_price AS c ON a.tc_id=c.id WHERE a.adv_id=? AND a.day=? ORDER BY plan_type';

        $res = Db::query($sql,[$param['adv_id'],$param['day']]);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }

    }*/

    /**
     * 获取广告商昨日日消耗(日支出)
     */
    public function advReportYes($param)
    {
        $sql = 'SELECT (t.adv_id) as uid,SUM(t.sumadvpay) as sumadvpay FROM (SELECT SUM(sumadvpay) AS sumadvpay,adv_id FROM lz_stats_new WHERE day=? GROUP BY adv_id) AS t LEFT JOIN lz_users AS a ON t.adv_id=a.uid WHERE a.type=? GROUP BY t.adv_id';
        $res = Db::query($sql,[$param['yesday'],2]);
        return $res;

    }
   /* public function advReportYes($param)
    {
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay),a.plan_type,a.web_deduction,b.money,c.price FROM lz_stats_new AS a LEFT JOIN lz_users AS b
        ON a.uid=b.uid LEFT JOIN lz_plan_price AS c ON a.tc_id=c.id WHERE a.adv_id=? AND a.day=? ORDER BY plan_type';

        $res = Db::query($sql,[$param['adv_id'],$param['day']]);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }

    }
*/
    /**
     *  查询 会员表 广告商是否存在
     */
    public function advUserOne($username){
        $data = array(
            'username'=>$username,
            'type'    => '2',
        );
        $res = Db::name('users')->where($data)->find();
        return $res;
    }

    /**
     *  查询充值的最低限额
     */
    public function least()
    {
        $sql = 'SELECT least_money FROM lz_setting';
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }

    }

    /**
     *  充值金额 广告商
     */
    public function advertiserPay($params,$adv_res){

        $data = array(
            'money'    =>$adv_res['money']+$params['money'],
        );
        $where = array(
            'uid'=> $adv_res['uid'],
        );
        $res = Db::name('users')->where($where)->update($data);


        return $res;
    }

    /**
     *  充值记录
     */
    public function paylog($data)
    {
        $res = Db::name('paylog')->insert($data);
        return $res;
    }

    /**
     * 得到客服列表    2017-5-12
     */
    public function getCustLst($value)
    {
        $sql = 'SELECT uid,username,qq,tel,mobile,contact,status FROM lz_users WHERE type=? GROUP BY uid ORDER BY uid DESC';
        $res = Db::query($sql,[$value['type']+2]);
        return $res;
    }

    /**
     * 得到客服和商务的名称
     * param data
     */
    public function getName($value)
    {
        $sql = 'SELECT username FROM lz_users WHERE uid=? AND type=?';
        $res = Db::query($sql,[$value['serviceid'],$value['type']+2]);
        return $res;
    }

    /**
     * 得到客服管理和商务管理名下人员的数量
     * param data
     */
    
    public function getNumber($value)
    {
        $sql = 'SELECT COUNT(serviceid) AS number FROM lz_users WHERE serviceid=? AND type=?';
        $res = Db::query($sql,[$value['uid'],$value['type']-2]);
        return $res;
    }


    /**
     * 在编辑页面下得到客服管理和商务管理人员的信息
     * param data
     */
    public function getNumberList($type)
    {
        $sql = 'SELECT uid,username,password,type,settled_amount,nosettled_amount,
        customer_cervice,email,qq,tel,mobile,contact,idcard,money,account_name,bank_name,
        bank_branch,bank_card,status,insite,remark,serviceid,login_time FROM lz_users WHERE type=?';
        $res = Db::query($sql,[$type+2]);
        return $res;
    }

    /**
     * 得到列表的数据个数
     * param data
     */
    public function getListCount($type)
    {
        $sql = 'SELECT b.username AS uname,a.uid,a.username,COUNT(a.uid) as count FROM lz_users AS a LEFT JOIN lz_users AS b ON
        a.serviceid=b.uid ';
        $sql.= ' WHERE a.type=? ';
        $res = Db::query($sql,[$type]);
        return $res[0]['count'];
    }

    /**
     * 得到列表的数据个数
     * param data
     */
    public function getListCount1($type,$params)
    {
        $sql = 'SELECT b.username AS uname,a.uid,a.username,COUNT(a.uid) as count FROM lz_users AS a LEFT JOIN lz_users AS b ON
        a.serviceid=b.uid ';
        $params['cpd_type'] = isset($params['cpd_type'])?$params['cpd_type']:'';
        if ($params['cpd_type']=='') {
            $typeSql = '';
        }else{
            $typeSql = ' AND a.cpd_type='.$params['cpd_type'].'';
        }
        if(!empty($params['search'])){
            $param = $params['search'];
            if($params['type'] == 'uname'){
                $sort = 'b.username';
            }else{
                $sort = 'a.'.$params['type'];
            }

			$params['status'] = isset($params['status'])? $params['status']: 'users_all';

            if($params['status'] == 'users_all'){
                $sql.= ' WHERE a.type=? AND '.$sort.' like "%'.$param.'%" '.$typeSql.'';
                $res = Db::query($sql,[$type]);
            }else{
                $sql.= ' WHERE a.type=? AND '.$sort.' like "%'.$param.'%" AND a.status = ? '.$typeSql.'';
                $res = Db::query($sql,[$type,$params['status']]);
            }

        }else{

			$params['status'] = isset($params['status'])? $params['status']: 'users_all';
            
            if($params['status'] == 'users_all'){
                $sql.= ' WHERE a.type=? '.$typeSql.'';
                $res = Db::query($sql,[$type]);
            }else{
                $sql.= ' WHERE a.type=? AND a.status = ? '.$typeSql.'';
                $res = Db::query($sql,[$type,$params['status']]);
            }
        }
        return $res[0]['count'];
    }

    /**
     * 查询名下人员和名下厂商
     * param data
     */
    public function getBelongList($offset,$count,$uid,$type)
    {

        $sql ='SELECT b.username AS uname,a.uid,a.username,a.password,a.type,a.customer_cervice,a.email,a.qq,a.tel,
        a.mobile,a.contact,a.idcard,a.money,a.account_name,a.bank_name,
        a.bank_branch,a.bank_card,a.status,a.insite,a.remark,a.serviceid,a.login_time,a.cpd_type,SUM(c.money) as settled_money
        FROM lz_users AS a LEFT JOIN lz_users AS b ON a.serviceid=b.uid LEFT JOIN lz_paylog AS c ON a.uid=c.uid
        WHERE a.type=? AND a.serviceid=? GROUP BY a.uid ORDER BY a.uid DESC LIMIT ?,?';

        $res = Db::query($sql,[$type-2,$uid,$offset,$count]);
        return $res;
    }
    /**
     * 查询名下人员和名下厂商的数据个数
     * param data
     */
    public function getBelongListCount($uid,$type)
    {
        $sql = 'SELECT COUNT(uid) as count FROM lz_users WHERE type=? AND serviceid=?';
        $res = Db::query($sql,[$type-2,$uid]);
        return $res[0]['count'];
    }

    /**
     * 新建用户
     * param data
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $res = Db::name('users')->INSERT($data);
        //将数据插入users临时表中
        Db::name('users_log')->INSERT($data);
        return $res;
    }

    /**
     * 验证注册新会员时用户名是否唯一
     * param data
     */
    public function validation($data,$type)
    {
        $sql = 'SELECT uid FROM lz_users WHERE username=? AND type=?';
        $res = Db::query($sql,[$data['username'],$type]);
        return $res;
    }

    /**
     * 更新状态
     * param uid status 状态
     */
    public function updateStatus($uid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'uid'=>$uid,
        );
        $res = $this::WHERE($map)->UPDATE($data);
        //将数据同步users_log
        Db::table('lz_users_log')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     * 站长锁定的情况下 同步锁定名下广告位和网站
     * param uid status 状态
     */
    public function updateAdzStatus($uid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'uid'=>$uid,
        );
        $res = Db::table('lz_adzone')->WHERE($map)->UPDATE($data);
        Db::table('lz_site')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     * 更新在页面上修改总金额等
     * param uid status 状态
     */
    public function editor($params)
    {
        $map = array(
            'uid'=>$params['uid'],
        );
        $res = $this::WHERE($map)->UPDATE($params);
        return $res;
    }

    /**
     * 站长列表直接在页面上修改扣量
     */
    public function webDeduction($params)
    {
        $map = array(
            'uid'=>$params['uid'],
        );
        $res = $this::WHERE($map)->UPDATE($params);
        //将数据同步users_log
        Db::table('lz_users_log')->WHERE($map)->UPDATE($params);
        return $res;
    }

    /**
     * 删除
     * param
     */
    public function delOne($uid)
    {
        $map = array(
            'uid'=>$uid,
        );
        $res = $this::WHERE($map)->DELETE();
        //将数据同步users_log
        Db::table('lz_users_log')->WHERE($map)->DELETE();
        return $res;
    }

    /**
     * 查看站长用户旗下是否有网站
     * param uid 用户id
     */
    public function siteOne($uid)
    {
        $sql = 'SELECT site_id FROM lz_site WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 编辑页面 获取用户数据
     * param uid 计划id
     */
    public function getOne($uid)
    {
        $sql = 'SELECT uid,username,nickname,password,type,web_deduction,adv_deduction,settled_amount,nosettled_amount,customer_cervice,email,qq,tel,mobile,
        contact,idcard,money,account_name,bank_name,bank_branch,bank_card,status,domain_limit,insite,remark,serviceid,
        login_time,login_ip,ctime,cpd_type FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     * 编辑
     * param data array 修改数据
     */
    public function editOne($uid,$data)
    {
        $map = array(
            'uid'=>$uid,
        );
        $res = $this::WHERE($map)->UPDATE($data);
        //将数据同步users_log
        // Db::table('lz_users_log')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     * 获取所有余额>=1000的广告商
     * param data
     */
    public function Lst()
    {
        $sql = 'SELECT uid,username,password,type,settled_amount,nosettled_amount,
        customer_cervice,email,qq,tel,mobile,contact,idcard,money,account_name,bank_name,
        bank_branch,bank_card,status,insite,remark,serviceid,login_time FROM lz_users WHERE money>=1000';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询该身份下所有的会员
     * param data
     */
    public function getNumList($params)
    {
        $sql = 'SELECT uid,username FROM lz_users WHERE type=?';
        $res = Db::query($sql,[$params['type']]);
        return $res;
    }

    /**
     * 获取我的业绩所有的数据(客服)
     * param data
     */
    public function getCusNum($params)
    {
        $sql = 'SELECT a.uid,a.adv_id,a.day,a.sumpay,a.sumadvpay,t.serviceid  FROM lz_stats_new as a LEFT JOIN
        (SELECT uid,serviceid FROM lz_users WHERE serviceid=?) t ON a.uid=t.uid WHERE day>=? AND day<=? AND t.serviceid=? ORDER BY a.day DESC';

        $res = Db::query($sql,[$params['uid'],$params['frontDay'],$params['endDay'],$params['uid']]);
        return $res;
    }

    /**
     * 查询客服当月业绩    客服业绩是不牵扯包天
     */
    public function getCusNumPay($text_advid,$data)
    {

        $sql = 'SELECT SUM(sumpay) as money,day,uid FROM lz_stats_new WHERE uid IN ('.$text_advid.') AND day>=? AND day <= ? GROUP BY day,uid';
        $res = Db::query($sql,[$data['frontDay'],$data['endDay']]);
        return $res;
    }

    /**
     * 查询客服今日业绩    客服业绩是不牵扯包天
     */
    public function getCusNumPayForDay($text_advid,$data)
    {

        $sql = 'SELECT SUM(sumpay) as money,day,uid FROM lz_stats_log WHERE uid IN ('.$text_advid.') AND day=? GROUP BY day,uid';
        $res = Db::query($sql,[$data['day']]);
        return $res;
    }
    /**
     * 获取我的当月业绩(客服)
     * param data
     */
    public function getCusMoney($data)
    {
        $sql = 'SELECT a.uid,a.adv_id,a.day,sum(a.sumpay),t.serviceid  FROM lz_stats_new as a LEFT JOIN
        (SELECT uid,serviceid FROM lz_users WHERE serviceid=?) t ON a.uid=t.uid WHERE day>=? AND day<=? AND t.serviceid=? ORDER BY a.day DESC';
        $res = Db::query($sql,[$data['uid'],$data['frontDay'],$data['endDay'],$data['uid']]);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }

    }

//    /**
//     * 查询客服的当月业绩
//     * param data
//     */
//    public function getCusMoneyList($data)
//    {
//        $sql = 'SELECT a.uid,a.serviceid,b.money FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a. uid = b.uid WHERE a.serviceid=? AND b.ctime>=? AND b.ctime <= ?';
//        $res = Db::query($sql,[$data['uid'],$data['frontDay'],$data['endDay']]);
//        return $res;
//    }

    /**
     * 查询客服当月新增下属会员
     */
    public function getWebs($date)
    {
        $sql = 'SELECT uid FROM lz_users WHERE serviceid=?';
        $res = Db::query($sql,[$date]);
        return $res;
    }

    /**
     * 查询当天新增下属会员
     */
    public function getAdzone($date)
    {
        $sql = 'SELECT adz_id FROM lz_adzone WHERE uid=?';
        $res = Db::query($sql,[$date]);
        return $res;
    }

    // /**
    //  * 查询客服当月业绩
    //  */
    // public function getWebPay($id,$month)
    // {
    //     $sql = 'SELECT SUM(a.sumpay) as money,a.adz_id,a.uid,a.day,b.cpd,b.cpd_day,c.cpd_status FROM lz_stats_new as a LEFT JOIN lz_adzone_copy as b ON a.adz_id=b.adz_id
    //     AND a.day=b.cpd_day LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id WHERE a.uid=? AND a.day>=? AND a.day <= ? GROUP BY a.adz_id,a.day';
    //     $res = Db::connect('db_config')->query($sql,[$id,$month['monthBegin'],$month['monthEnd']]);
    //     return $res;
    // }




    /**
     * 查询客服当月业绩
     */
    public function monthCusMoney($id,$month)
    {
        $sql = 'SELECT SUM(a.sumpay) as money,a.day,b.cpd,b.cpd_day,c.cpd_status FROM lz_stats_new as a LEFT JOIN lz_adzone_copy as b ON a.adz_id=b.adz_id
        AND a.day=b.cpd_day LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id WHERE a.adz_id=? AND a.day>=? AND a.day <= ? GROUP BY a.day';
        $res = Db::query($sql,[$id,$month['monthBegin'],$month['monthEnd']]);
        return $res;
    }

    /**
     * 获取我的业绩所有的数据(商务)
     * param data
     */
    public function getBusNum($params)
    {
        $sql = 'SELECT a.uid,a.adv_id,a.day,a.sumpay,a.sumadvpay,t.serviceid  FROM lz_stats_new as a LEFT JOIN
        (SELECT uid,serviceid FROM lz_users WHERE serviceid=?) t ON a.adv_id=t.uid WHERE day>=? AND day<=? AND t.serviceid=? ORDER BY a.day DESC';

        $res = Db::query($sql,[$params['uid'],$params['frontDay'],$params['endDay'],$params['uid']]);
        return $res;
    }

    /**
     * 获取我的当月业绩(商务)
     * param data
     */
    public function getBusMoney($data)
    {
        $sql = 'SELECT a.uid,a.adv_id,a.day,sum(a.sumadvpay),t.serviceid  FROM lz_stats_new as a LEFT JOIN
        (SELECT uid,serviceid FROM lz_users WHERE serviceid=?) t ON a.adv_id=t.uid WHERE day>=? AND day<=? AND t.serviceid=? ORDER BY a.day DESC';
        $res = Db::query($sql,[$data['uid'],$data['frontDay'],$data['endDay'],$data['uid']]);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     * 查询客服当月业绩
     */
    public function getadvNumPay($text_advid,$data)
    {

        $sql = 'SELECT SUM(a.sumadvpay) as money,a.day,a.uid,b.cpd FROM lz_stats_new as a LEFT JOIN lz_adzone_copy as b ON a.adz_id=b.adz_id
        AND a.day=b.cpd_day WHERE a.adv_id IN ('.$text_advid.') AND a.day>=? AND a.day <= ? GROUP BY a.day';
        $res = Db::query($sql,[$data['frontDay'],$data['endDay']]);
        return $res;
    }

    /**
     * 查询客服当月业绩
     */
    public function getBusNumPay($text_advid,$data)
    {
        $sql = 'SELECT SUM(a.sumadvpay) as money,a.day,a.uid FROM lz_stats_new as a LEFT JOIN lz_adzone_copy as b ON a.adz_id=b.adz_id
        AND a.day=b.cpd_day WHERE a.adv_id IN ('.$text_advid.') AND a.day>=? AND a.day <= ? GROUP BY a.day';
        $res = Db::query($sql,[$data['frontDay'],$data['endDay']]);
        // dump(db::getlastsql());exit;
        return $res;
    }

    /**
     * 查询商务今日业绩
     */
    public function getBusNumPayForday($text_advid,$data)
    {
        $sql = 'SELECT SUM(a.sumadvpay) as money,a.day,a.uid FROM lz_stats_log as a LEFT JOIN lz_adzone_copy as b ON a.adz_id=b.adz_id
        AND a.day=b.cpd_day WHERE a.adv_id IN ('.$text_advid.') AND a.day= ? GROUP BY a.day';
        $res = Db::query($sql,[$data['day']]);
        // dump(db::getlastsql());exit;
        return $res;
    }

    /**
     * 查询当天新增下属会员
     */
    public function getAdvs($id,$type)
    {
        $sql = 'SELECT uid FROM lz_users WHERE serviceid=? AND type=?';
        $res = Db::query($sql,[$id,$type]);
        return $res;
    }

  

    /**
     * 广告计划--跳转广告商
     * param uid 用户id
     */
    public function getAdvOne($uid)
    {
        $sql = 'SELECT b.username AS uname,a.contact,a.mobile,a.status,a.qq,a.money,a.uid,a.username,
        a.email,a.idcard,a.remark FROM lz_users AS a LEFT JOIN lz_users AS b ON a.serviceid=b.uid 
        WHERE a.type=2 and a.uid=?';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 得到计划列表的个数
     * param data
     */
    public function planLstCount($params)
    {
        $sql = 'SELECT count(pid) as count FROM lz_plan WHERE uid=?';
        $res = Db::query($sql,[$params['uid']]);
        return $res[0]['count'];
    }

    /**
     * 得到计划列表
     * param data
     */
    public function getPlanLst($offset,$count,$params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.priority,a.budget,a.clearing,a.ads_sel_status,a.run_terminal,
        a.deduction,a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,
        c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid 
	 WHERE a.uid=? ORDER BY a.pid DESC LIMIT ?,? ';
        $res = Db::query($sql,[$params['uid'],$offset,$count]);
        return $res;
    }

    /**
     * 广告商管理下查看广告
     * param data
     */
    public function adPlanLst($uid)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,a.imageurl,a.files,b.plan_name,
        a.pid,a.width,a.height,b.plan_type,b.gradation,c.tplname,c.customspecs FROM lz_ads AS a LEFT JOIN lz_plan AS b
        ON a.pid=b.pid LEFT JOIN lz_admode AS c ON a.tpl_id=c.tpl_id LEFT JOIN lz_users AS d ON b.uid=d.uid WHERE a.uid=?';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 获取上传图片的服务器地址
     */
    public function getImgService()
    {
        $sql = 'SELECT img_server FROM lz_setting';
        $res = Db::query($sql);
        $res = empty($res) ? '' : $res['0'];
        return $res;
    }

    /**
     * 站长管理下查看网站列表的个数
     * param data
     */
    public function siteCount($params)
    {
        $sql = 'SELECT a.uid,b.uid AS userid FROM lz_site AS a LEFT JOIN  lz_users AS b ON a.uid = b.uid WHERE
        a.uid = b.uid AND a.uid=?';
        $res = Db::query($sql,[$params['uid']]);
        $res['count'] = count($res);
        return $res['count'];
    }

    /**
     * 站长管理下查看网站列表的数据
     * param data
     */
    public function siteList($offset,$count,$params)
    {
        $sql = 'SELECT a.site_id,a.uid,a.sitename,a.siteurl,a.class_id,a.day_ip,a.status,a.star,a.https,b.uid AS userid,b.username,
        c.class_id,c.class_name,c.type FROM lz_site AS a LEFT JOIN lz_users AS b ON  a.uid=b.uid LEFT JOIN lz_classes AS c
        ON a.class_id = c.class_id WHERE a.uid =b.uid AND a.uid=? LIMIT ?,?';
        $res = Db::query($sql,[$params['uid'],$offset,$count]);
        return $res;
    }

    /**
     * 查询网站表
     */
    public function siteViews($day,$params)
    {
        $sql = 'SELECT a.site_id,SUM(e.views) as views FROM lz_site AS a LEFT JOIN lz_users AS b ON a.uid=b.uid
                LEFT JOIN lz_classes AS c ON a.class_id = c.class_id LEFT JOIN lz_adzone as d ON a.site_id=d.site_id
                LEFT JOIN lz_stats_new as e ON d.adz_id=e.adz_id WHERE e.day=? AND a.uid=?';
        $res = Db::query($sql,[$day,$params['uid']]);
        return $res;
    }

    /**
     * 站长管理下查看广告位列表的个数
     * param data
     */
    public function adzoneCount($params)
    {
        $sql = 'SELECT count(adz_id) AS count FROM lz_adzone WHERE uid=?';
        $res = Db::query($sql,[$params['uid']]);
        return $res[0]['count'];
    }

    /**
     * 站长管理下查看广告位列表的数据
     * param data
     */
    public function adzone($offset,$count,$params)
    {
        $sql = 'SELECT a.adz_id,a.uid,a.zonename,a.adtpl_id,a.adstyle_id,a.plantype,a.width,a.height,a.cpd_status,a.system_type,f.cpd as cp,a.status,
        a.cpd_startday,a.cpd_endday,b.uid AS userid,
        b.username,c.tpl_id,c.tplname,d.style_id,e.plan_type FROM lz_adzone AS a LEFT JOIN lz_users AS b ON a.uid =b.uid LEFT JOIN lz_admode
        AS c ON a.adtpl_id = c.tpl_id LEFT JOIN lz_adstyle AS d ON a.adstyle_id =d.style_id LEFT JOIN lz_plan AS e ON a.plantype = e.pid LEFT JOIN lz_adzone_copy AS f ON a.adz_id=f.adz_id
        WHERE a.uid =b.uid AND
        a.adtpl_id = c.tpl_id AND a.uid=? GROUP BY a.adz_id  ORDER BY a.adz_id DESC LIMIT ?,?';
        $res = Db::query($sql,[$params['uid'],$offset,$count]);
        return $res;
    }

    /**
     *  查询会员类型
     */
    public function getType($uid)
    {
        $sql = 'SELECT type FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     * 查询该身份下所有的会员
     * param data
     */
    public function getLogMoney()
    {
        $sql = 'SELECT id,money,uid,status FROM lz_user_money_tmp ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 修改总金额等
     * param uid status 状态
     */
    public function getMoney($params,$day)
    {
        // $sql = 'SELECT money FROM lz_users WHERE uid=?';
        $sql = 'SELECT sumadvpay as money FROM lz_stats_log WHERE uid=? AND day=?';
        $res = Db::query($sql,[$params['uid'],$day]);
        return $res;
    }

    /**
     * 修改总金额等
     * param uid status 状态
     */
    public function editorMoney($params,$money)
    {
        $map = array(
            'uid'=>$params['uid'],
        );
        $res = $this::WHERE($map)->UPDATE($money);
        return $res;
    }

    /**
     * 批量删除已经处理过的log数据
     * param data
     */
    public function deleteLog($idfirst,$idlast)
    {
        $sql = 'delete from lz_user_money_tmp where id>=? and id<=?; ';
        $res = Db::execute($sql,[$idfirst,$idlast]);

        //重置主键id
        $sql = 'alter table lz_user_money_tmp drop id';
        Db::execute($sql);
        $sql = 'alter table lz_user_money_tmp add id int auto_increment primary key ';
        Db::execute($sql);
        return $res;
    }

    /**
     * 获取站长余额
     */
    // public function webMoney($uid)
    // {
    //     $sql = 'SELECT money FROM lz_users WHERE uid=?';
    //     $res = Db::query($sql,[$uid]);

    //     return $res;
    // }

    /**
     * 获取站长stats当天数据
     */
    public function webMoneyPay($uid,$date)
    {
        $sql = 'SELECT SUM(a.sumpay) as money,a.adz_id,b.cpd,b.cpd_day,c.cpd_status FROM lz_stats_new AS a LEFT JOIN lz_adzone_copy AS b 
        ON a.adz_id =b.adz_id and a.day=b.cpd_day LEFT JOIN lz_adzone AS c 
        ON c.adz_id =a.adz_id WHERE a.uid=? AND a.day=? GROUP BY adz_id';
       $res = Db::query($sql,[$uid,$date]);
        return $res;
    }

    /**
     * 查询今日的站长消耗，并判断是否按当前日排序
     */
    public function gettodaysumpay($param)
    {
        $sort_day = ($param['sort_day'] == 1) ? ' ORDER BY t.sumpay' : '';
        $sort_type = ($param['sort_type'] == 1) ? ' DESC' : '';
        //如果不按照当前日排序，则去掉整个ORDER BY
        if(empty($sort_day)){
            $sort_type = '';
        }
        $sql = 'SELECT a.username,a.uid,a.status,t.sumpay,t.day FROM
                (SELECT uid,day,SUM(sumpay) AS sumpay FROM lz_stats_log WHERE day=? GROUP BY uid) AS t LEFT JOIN
                lz_users AS a ON a.uid=t.uid WHERE t.sumpay!=0 '.$sort_day.$sort_type;
        $res = Db::query($sql,[$param['time']]);
        return $res;
    }

    /**
     * 查询站长消耗，并判断是否按当前日排序
     */
    public function getsumpay($param,$time,$sortday)
    {
        $sort_day = ($param['sort_day'] == $sortday) ? ' ORDER BY t.sumpay' : '';
        $sort_type = ($param['sort_type'] == 1) ? ' DESC' : '';
        //如果不按照当前日排序，则去掉整个ORDER BY
        if(empty($sort_day)){
            $sort_type = '';
        }
        $sql = 'SELECT a.username,a.uid,a.status,t.sumpay,t.day FROM
                (SELECT uid,day,SUM(sumpay) AS sumpay FROM lz_stats_new WHERE day=? GROUP BY uid) AS t LEFT JOIN
                lz_users AS a ON a.uid=t.uid WHERE t.sumpay!=0 '.$sort_day.$sort_type;
        $res = Db::query($sql,[$time]);
        return $res;
    }

}