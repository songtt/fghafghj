<?php
namespace app\home\model;
use think\Model;
use think\Db;

class Customer extends Model
{

    /**
     * 查询客服当月新增下属会员
     */
    public function get_Web($date)
    {
        $sql = 'SELECT uid FROM lz_users WHERE serviceid=?';
        $res = Db::query($sql,[$date]);
        return $res;
    }

    /**
     * 查询当天新增下属会员
     */
    public function getWebAdzone($date)
    {
        $sql = 'SELECT adz_id FROM lz_adzone WHERE uid=?';
        $res = Db::query($sql,[$date]);
        return $res;
    }

    /**
     * 查询客服当月业绩
     */
    public function monthCusMoney($id,$month)
    {
        $sql = 'SELECT SUM(t.sumpay) as sumpay FROM (SELECT SUM(sumpay) AS sumpay,uid FROM lz_stats_new WHERE day>=? AND day<=?
                GROUP BY uid) AS t LEFT JOIN lz_users AS a ON t.uid=a.uid WHERE a.serviceid=?';
        $res = Db::connect('db_query_config')->query($sql,[$month['monthBegin'],$month['monthEnd'],$id]);
        return $res;
    }

    /**
     * 查询当天新增下属会员
     */
    public function getTodaynum($date,$time)
    {
        $sql = 'SELECT count(uid) as count FROM lz_users WHERE serviceid=? AND ctime>=?';
        $res = Db::query($sql,[$date,$time]);
        return $res[0]['count'];
    }

    /**
     * 查询待审会员ＴＯＰ５０
     */
    public function getUsers($date)
    {
        $sql = 'SELECT uid,username,regip,ctime FROM lz_users WHERE serviceid=? AND status=? LIMIT 0,50';
        $res = Db::query($sql,[$date,2]);
        return $res;
    }

    /**
     * 查询待审会员个数
     */
    public function getUsersCount($date)
    {
        $sql = 'SELECT count(uid) as count FROM lz_users WHERE serviceid=? AND status=? ';
        $res = Db::query($sql,[$date,2]);
        return $res[0]['count'];
    }

    /**
     * 查询待审网站ＴＯＰ５０
     */
    public function getSite($uid)
    {
        $sql = 'SELECT a.site_id,a.sitename,a.siteurl,a.beian,b.username,c.class_name FROM lz_site as a LEFT JOIN lz_users as b
        ON a.uid=b.uid LEFT JOIN lz_classes as c ON a.class_id=c.class_id WHERE a.status=? AND b.serviceid=? LIMIT 0,50';

        $res = Db::query($sql,[2,$uid]);
        return $res;
    }

    /**
     * 查询当天新增下属会员
     */
    public function getWebs($date)
    {
        $sql = 'SELECT uid FROM lz_users WHERE serviceid=?';
        $res = Db::query($sql,[$date]);
        return $res;
    }
     /**
     * 查询当天名下站长广告位
     */
    public function getToAdzone($date)
    {
        $sql = 'SELECT a.adz_id,a.uid,a.status,a.zonename,a.width,a.height,b.uid AS userid,b.username,c.tplname FROM lz_adzone AS a LEFT JOIN lz_users AS b ON a.uid =b.uid LEFT JOIN lz_admode AS c ON a.adtpl_id = c.tpl_id WHERE a.status=2 AND a.uid =b.uid  AND b.serviceid=? GROUP BY a.adz_id ORDER BY a.adz_id DESC ';
        $res = Db::query($sql,[$date]);
        return $res;
    }

    /**
     * 查询客服今日业绩
     */
    public function getMoney($id,$day)
    {
        $sql = 'SELECT SUM(t.sumpay) as sumpay FROM (SELECT SUM(sumpay) AS sumpay,uid FROM lz_stats_log WHERE day=?
                GROUP BY uid) AS t LEFT JOIN lz_users AS a ON t.uid=a.uid WHERE a.serviceid=?';
        $res = Db::query($sql,[$day,$id]);
        return $res;
    }

    /**
     * 查询待审网站个数
     */
    public function getSiteCount($uid)
    {
        $sql = 'SELECT count(a.site_id) as count FROM lz_site as a LEFT JOIN lz_users as b
        ON a.uid=b.uid WHERE a.status=? AND b.serviceid=?';

        $res = Db::query($sql,[2,$uid]);
        return $res[0]['count'];
    }

    /**
     * 全局配置域名
     */
    public function globalList()
    {
        $sql = 'select img_server,js_server,jump_server,domain_limit from lz_setting';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询线下会员的个数
     */
    public function getUsersListCount($uid,$params)
    {
        $sql = 'SELECT count(uid) as count FROM lz_users ';
        if(empty($params['num'])){
            $sql.= ' WHERE serviceid=? ';
            $res = Db::query($sql,[$uid]);

        }elseif($params['selectName'] == 'uname'){
            $sql.= ' WHERE serviceid=? AND username=? ';
            $res = Db::query($sql,[$uid,$params['num']]);

        }else{
            $sql.= ' WHERE serviceid=? AND uid=? ';
            $res = Db::query($sql,[$uid,$params['num']]);
        }
        return $res[0]['count'];
    }
    /**
     * 查询线下会员60天内收益情况
     */
     public function getreportProfit($date)
     {
        $sql =  'SELECT a.uid FROM lz_users AS a LEFT JOIN lz_stats_new as b ON a.uid=b.uid WHERE a.serviceid=? AND b.day <= ? AND b.day>=? AND a.type=1 GROUP BY a.uid';
        $res = Db::query($sql,[$date['uid'],$date['now'],$date['twoMonth']]);
         
        return $res;
        
     }
     /**
     * 查询线下会员今日收益
     */
     public function getreportNow($date)
     {
        $sql =  'SELECT t.*,SUM(b.sumpay) as sumpay FROM lz_stats_log as b LEFT JOIN (SELECT a.uid,a.username,a.money,SUM(c.money) as settled_money FROM lz_users AS a LEFT JOIN lz_paylog AS c ON a.uid=c.uid  WHERE a.serviceid=? GROUP BY a.uid) as t  ON t.uid = b.uid WHERE b.day = ?  GROUP BY t.uid';
        $res = Db::query($sql,[$date['uid'],$date['now']]);
         
        return $res;
        
     }
    /**
     * 查询线下会员(降序)
     */
    public function getUsersDes($date)
    {
        $sql = 'SELECT a.uid,a.username,a.type,a.customer_cervice,a.email,a.qq,a.tel,
        a.mobile,a.contact,a.idcard,a.money,a.account_name,a.bank_name,a.bank_branch,a.bank_card,a.status,a.insite,
        a.remark,a.serviceid,a.cpd_type,SUM(c.money) as settled_money FROM lz_users AS a LEFT JOIN lz_users AS b ON
        a.serviceid=b.uid LEFT JOIN lz_paylog AS c ON a.uid=c.uid WHERE a.type=1 AND a.serviceid=?';
         if($date['params']['sortName'] == 'ctime' || $date['params']['sortName'] == 'sumpay'){
            $sort = 'a.ctime';
        }else{
            $sort = $date['params']['sortName'];
        }
        if(empty($date['params']['num'])){
            $sql.= ' GROUP BY a.uid ORDER BY '.$sort.' DESC ';
            $res = Db::query($sql,[$date['uid']]);

        }elseif($date['params']['selectName'] == 'uname'){
            $sql.= ' AND a.username=? GROUP BY a.uid ORDER BY '.$sort.' DESC';
            $res = Db::query($sql,[$date['uid'],$date['params']['num']]);

        }else{
            $sql.= ' AND a.uid=? GROUP BY a.uid ORDER BY '.$sort.' DESC ';
            $res = Db::query($sql,[$date['uid'],$date['params']['num']]);
        }
        return $res;
    }

    /**
     * 查询线下会员(升序)
     */
    public function getUsersAs($date)
    {
        $sql = 'SELECT a.uid,a.username,a.type,a.money,a.account_name,a.status,a.insite,
        a.remark,a.serviceid,a.cpd_type,SUM(c.money) as settled_money FROM lz_users AS a LEFT JOIN lz_users AS b ON
        a.serviceid=b.uid LEFT JOIN lz_paylog AS c ON a.uid=c.uid WHERE a.type=1 AND a.serviceid=? ';
        if($date['params']['sortName'] == 'ctime' || $date['params']['sortName'] == 'sumpay'){
            $sort = 'a.ctime';
        }else{
            $sort = $date['params']['sortName'];
        }
        if(empty($date['params']['num'])){
            $sql.= '  GROUP BY a.uid ORDER BY '.$sort.' ';
            $res = Db::query($sql,[$date['uid']]);

        }elseif($date['params']['selectName'] == 'uname'){
            $sql.= ' AND username=? GROUP BY a.uid ORDER BY '.$sort.' ';
            $res = Db::query($sql,[$date['uid'],$date['params']['num']]);

        }else{
            $sql.= ' AND uid=? GROUP BY a.uid ORDER BY '.$sort.' ';
            $res = Db::query($sql,[$date['uid'],$date['params']['num']]);
        }
        return $res;
    }

    /**
     * 查询线下网站
     */
    public function getSitelist($offset,$count,$date)
    {
        $sql = 'SELECT a.site_id,a.uid,a.sitename,a.beian,a.siteinfo,a.day_ip,a.status,b.username,c.class_name FROM lz_site as a
        LEFT JOIN lz_users as b ON a.uid=b.uid LEFT JOIN lz_classes as c ON a.class_id=c.class_id ';
        if(empty($date['params']['num'])){
            $sql.= ' WHERE b.serviceid=? LIMIT ?,?';
            $res = Db::query($sql,[$date['uid'],$offset,$count]);

        }elseif($date['params']['selectName'] == 'sitename'){
            $sql.= ' WHERE b.serviceid=? AND a.sitename=? LIMIT ?,?';
            $res = Db::query($sql,[$date['uid'],$date['params']['num'],$offset,$count]);

        }elseif($date['params']['selectName'] == 'userid'){
            $sql.= ' WHERE b.serviceid=? AND a.uid=? LIMIT ?,?';
            $res = Db::query($sql,[$date['uid'],$date['params']['num'],$offset,$count]);

        }elseif($date['params']['selectName'] == 'siteid'){
            $sql.= ' WHERE b.serviceid=? AND a.site_id=? LIMIT ?,?';
            $res = Db::query($sql,[$date['uid'],$date['params']['num'],$offset,$count]);

        }else{
            $sql.= ' WHERE b.serviceid=? AND b.username=? LIMIT ?,?';
            $res = Db::query($sql,[$date['uid'],$date['params']['num'],$offset,$count]);
        }

        return $res;
    }

    /**
     * 查询线下网站的个数
     */
    public function getSitelistCount($uid,$params)
    {
        $sql = 'SELECT COUNT(a.site_id) as count FROM lz_site as a
        LEFT JOIN lz_users as b ON a.uid=b.uid LEFT JOIN lz_classes as c ON a.class_id=c.class_id ';
        if(empty($params['num'])){
            $sql.= ' WHERE b.serviceid=? ';
            $res = Db::query($sql,[$uid]);

        }elseif($params['selectName'] == 'sitename'){
            $sql.= ' WHERE b.serviceid=? AND a.sitename=?';
            $res = Db::query($sql,[$uid,$params['num']]);

        }elseif($params['selectName'] == 'userid'){
            $sql.= ' WHERE b.serviceid=? AND a.uid=?';
            $res = Db::query($sql,[$uid,$params['num']]);

        }elseif($params['selectName'] == 'siteid'){
            $sql.= ' WHERE b.serviceid=? AND a.site_id=?';
            $res = Db::query($sql,[$uid,$params['num']]);

        }else{
            $sql.= ' WHERE b.serviceid=? AND b.username=?';
            $res = Db::query($sql,[$uid,$params['num']]);
        }
        return $res[0]['count'];
    }

    /**
     * 更新网站状态
     * param uid status 状态
     */
    public function updateSiteStatus($siteid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'site_id'=>$siteid,
        );
        $res = Db::name('site')->WHERE($map)->UPDATE($data);
        return $res;
    }

    /**
     * 更新会员状态
     * param uid status 状态
     */
    public function updateUsersStatus($uid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'uid'=>$uid,
        );
        $res = Db::name('users')->WHERE($map)->UPDATE($data);
        //数据同步users_log
        Db::name('users_log')->WHERE($map)->UPDATE($data);
        return $res;
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
        $res = Db::name('users')->where($map)->update($data);
        //数据同步users_log
        Db::name('users_log')->where($map)->update($data);
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
        $res = Db::name('users')->where($map)->find();
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
        $res = Db::name('users')->where($map)->update($data);
          //数据同步users_log
        Db::name('users_log')->where($map)->update($data);
        return $res;
    }


    /**
     *  重置客服名下站长的密码   默认重置为123456
     */
    public function passwordReset($uid,$password)
    {
        $map=array(
            'uid' => $uid,
        );
        $data=array(
            'password' => $password,
        );
        $res = Db::name('users')->where($map)->update($data);
        //数据同步users_log
        Db::name('users_log')->where($map)->update($data);
        return $res;
    }

    /**
     *  导出excel得到客服名下的站长id
     */
    public function getUid($custId,$cpd_type)
    {
        $sql = 'SELECT uid FROM lz_users WHERE serviceid=? AND status=1 AND cpd_type=?';
        $res = Db::query($sql,[$custId,$cpd_type]);
        return $res;
    }

    /**
     *  得到该客服名下站长的最近结算时间
     */
    public function getPayday($uid)
    {
        $sql = 'SELECT MAX(day) as payday,uid FROM lz_paylog WHERE uid=? AND type=? AND status=?';
        $res = Db::query($sql,[$uid,1,1]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     *  得到该客服名称
     */
    public function getCusname($uid)
    {
        $sql = 'SELECT username FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     *  导出excel得到没结算过站长的结算信息
     */
    public function getPayInfo($key,$params)
    {
        $sql = 'SELECT t1.*,a.username,a.contact,a.account_name,a.bank_name,a.bank_branch,a.bank_card,b.cpd,b.cpd_day,c.siteurl FROM (SELECT uid,adz_id,site_id,adtpl_id,SUM(sumpay) as sumpay,day,MAX(ui_adzone) as ui_adzone FROM lz_stats_new WHERE uid=? AND day<=? GROUP BY adz_id,day) AS t1
            LEFT JOIN lz_users AS a ON t1.uid=a.uid
            LEFT JOIN lz_adzone_copy AS b ON t1.adz_id=b.adz_id AND t1.day=b.cpd_day
            LEFT JOIN lz_site AS c ON t1.site_id=c.site_id';
        $res = Db::query($sql,[$key,$params['sun']]);
        return $res;
    }

    /**
     *  导出excel得到结算过站长的结算信息
     */
    public function getPaidInfo($key,$value,$params)
    {
        $sql = 'SELECT t1.*,a.username,a.contact,a.account_name,a.bank_name,a.bank_branch,a.bank_card,b.cpd,b.cpd_day,c.siteurl FROM (SELECT uid,adz_id,site_id,adtpl_id,SUM(sumpay) as sumpay,day,MAX(ui_adzone) as ui_adzone FROM lz_stats_new WHERE uid=? AND day>? AND day<=? GROUP BY adz_id,day) AS t1
            LEFT JOIN lz_users AS a ON t1.uid=a.uid
            LEFT JOIN lz_adzone_copy AS b ON t1.adz_id=b.adz_id AND t1.day=b.cpd_day
            LEFT JOIN lz_site AS c ON t1.site_id=c.site_id';
        $res = Db::query($sql,[$key,$value,$params['sun']]);
        return $res;
    }

    /**
    *  站长排重点击  每天中最大值
    */
    public function webClickPay($key,$params)
    {
        $sql = 'SELECT uid,MAX(web_click_num) as web_click_num,day,SUM(click_num) as click_num FROM lz_stats_new WHERE uid=? AND day<=? GROUP BY uid,day';
        $res = Db::query($sql,[$key,$params['sun']]);
        return $res;
    }

    /**
    *  站长排重点击 每天中最大值
    */
    public function webClickPaid($key,$value,$params)
    {
        $sql = 'SELECT uid,MAX(web_click_num) as web_click_num,day,SUM(click_num) as click_num FROM lz_stats_new WHERE uid=? AND day>? AND day<=? GROUP BY uid,day';
        $res = Db::query($sql,[$key,$value,$params['sun']]);
        return $res;
    }

    /**
    *  客服编辑名下站长合作模式
    */
    public function updateCpd($params)
    {
        $sql = 'UPDATE lz_users SET cpd_type=? WHERE uid=?';
        $res = Db::query($sql,[$params['cpd_type'],$params['uid']]);
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

}