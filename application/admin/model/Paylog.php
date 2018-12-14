<?php
/**
 * 财务管理
 * @date   2016-10-08
 */
namespace app\admin\model;

use think\model;
use think\Db;


class Paylog extends \think\Model
{
    /**
     * 获取站长上一周的钱
     * param data
     */
    public function getWebMoney($params)
    {
        $sql = 'SELECT uid,adz_id,day,SUM(sumpay) as sumpay FROM lz_stats_new WHERE day>=? AND day<=? AND uid!=? GROUP BY uid';
        $res = Db::query($sql,[$params['mon'],$params['sun'],0]);
        return $res;
    }

    /**
     * 获取站长上一周的钱 考虑包天
     * param data
     */
    public function getLastMoney($params)
    {
        $sql = 'SELECT uid,day,SUM(sumpay) as sumpay FROM lz_stats_new WHERE day>=? AND day<=? GROUP BY uid';
        $res = Db::query($sql,[$params['mon'],$params['sun']]);
        return $res;
    }

    /**
     * 获取财务表中的未结算金额
     * param data
     */
    public function paySet($uid,$params)
    {
        $sql = 'SELECT uid,xmoney,day FROM lz_paylog WHERE payinfo=?  AND uid=? AND day>=?';
        $res = Db::query($sql,[4,$uid,$params['sun']]);
        if(empty($res)){
            return '';
        }else{
            return $res['0'];
        }
    }

    /**
     * 将未结算金额插入paylog中
     */
    public function payInster($arr)
    {
        $res = Db::name('paylog')->insert($arr);
        return $res;
    }

    /**
     * 得到站长的列表
     */
    public function getWebList($params)
    {
        $sql = 'SELECT a.uid,a.username,a.type,a.contact,a.money,a.idcard,a.account_name,a.bank_name,a.bank_branch,a.bank_card,b.username as uname,t1.* FROM (SELECT uid,status,min(day) as day,SUM(xmoney) as xmoney FROM lz_paylog WHERE status=3 GROUP BY uid) AS t1 LEFT JOIN lz_users AS a ON t1.uid=a.uid
            LEFT JOIN lz_users AS b ON a.serviceid=b.uid ';
        if(!empty($params['search'])){
            if($params['webmaster'] == 'username'){
                $sele = ltrim($params['search']);
                $sql.=  'WHERE a.type=?  AND t1.xmoney>=? AND a.username like "%'.$sele.'%" ORDER BY t1.xmoney DESC';
                $res = Db::query($sql,[1,100]);
            }elseif($params['webmaster'] == 'uid'){
                $sql.=  'WHERE a.type=?  AND t1.xmoney>=? AND a.uid=? ORDER BY t1.xmoney DESC';
                $res = Db::query($sql,[1,100,$params['search']]);
            }else{
                $sele = ltrim($params['search']);
                $sql.=  'WHERE a.type=?  AND t1.xmoney>=? AND b.username like "%'.$sele.'%" ORDER BY t1.xmoney DESC';
                $res = Db::query($sql,[1,100]);
            }
        }else{
            $sql.=  'WHERE a.type=?  AND t1.xmoney>=? ORDER BY t1.xmoney DESC';
            $res = Db::query($sql,[1,100]);
        }
        return $res;
    }

    // /**
    //  * 得到站长的列表
    //  */
    // public function getWebList($params,$offset,$count)
    // {
    //     $sql = 'SELECT a.uid,a.username,a.type,a.contact,a.money,a.idcard,a.account_name,a.bank_name,a.bank_branch,a.bank_card,
    //         c.username as uname,b.status,min(b.day) as day,SUM(b.xmoney) as xmoney FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a.uid=b.uid LEFT JOIN
    //         lz_users AS c ON a.serviceid=c.uid ';
    //     if(!empty($params['search'])){
    //         if($params['webmaster'] == 'username'){
    //             $sele = ltrim($params['search']);
    //             $sql.=  'WHERE a.type=?  AND b.status=? AND a.username like "%'.$sele.'%" AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid ORDER BY b.xmoney DESC LIMIT ?,?';
    //             $res = Db::query($sql,[1,3,$offset,$count]);
    //         }elseif($params['webmaster'] == 'uid'){
    //             $sql.=  'WHERE a.type=? AND b.status=? AND a.uid=? AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid ORDER BY b.xmoney DESC LIMIT ?,?';
    //             $res = Db::query($sql,[1,3,$params['search'],$offset,$count]);
    //         }else{
    //             $sele = ltrim($params['search']);
    //             $sql.=  'WHERE a.type=? AND b.status=? AND c.username like "%'.$sele.'%" AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY xmoney DESC ORDER BY a.uid LIMIT ?,?';
    //             $res = Db::query($sql,[1,3,$offset,$count]);
    //         }
    //     }else{
    //         $sql.=  'WHERE a.type=? AND b.status=? AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid ORDER BY xmoney DESC LIMIT ?,?';
    //         $res = Db::query($sql,[1,3,$offset,$count]);
    //     }

    //     return $res;
    // }


    /**
     *  统计列表的个数
     */
    public function clearingCount($params)
    {
        $sql = 'SELECT a.uid,a.username,c.username as uname,b.status,SUM(b.xmoney) as xmoney FROM lz_users AS a LEFT JOIN lz_paylog AS b ON b.uid=a.uid LEFT JOIN
              lz_users AS c ON a.serviceid=c.uid ';
        if(!empty($params['search'])){
            if($params['webmaster'] == 'username'){
                $sele = ltrim($params['search']);
                $sql.= 'WHERE a.type=? AND b.status=?  AND a.username like "%'.$sele.'%" AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid';
                $res = Db::query($sql,[1,3]);
            }elseif($params['webmaster'] == 'uid'){
                $sql.= 'WHERE a.type=? AND b.status=?  AND a.uid=? AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid';
                $res = Db::query($sql,[1,3,$params['search']]);
            }else{
                $sele = ltrim($params['search']);
                $sql.= 'WHERE a.type=? AND b.status=?  AND c.username like "%'.$sele.'%" AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid';
                $res = Db::query($sql,[1,3]);
            }
        }else{
            $sql.= 'WHERE a.type=? AND b.status=? AND (SELECT SUM(xmoney) FROM lz_paylog WHERE a.uid=uid) >=100 GROUP BY b.uid';
            $res = Db::query($sql,[1,3]);
        }
        return count($res);
    }

    /**
     *  更新站长余额
     */
    public function updateWebMoney($uid,$money)
    {
        $sql = 'UPDATE lz_users SET money=? where uid=?';
        $res = Db::execute($sql,[$money,$uid]);
        return $res;
    }


    /**
     *  更新paylog
     */
    public function payStatus($arr)
    {
        $sql = 'UPDATE lz_paylog SET status=?,xmoney=? where uid=? AND status=? ';
        $res = Db::execute($sql,[0,0,$arr['0'],3]);
        return $res;
    }


    /**
     * 得到应付的总费用
     */
    public function getCope()
    {
        $sql = 'SELECT uid,SUM(xmoney) as xmoney,min(day) as day FROM lz_paylog as a WHERE (SELECT SUM(xmoney) FROM lz_paylog WHERE uid=a.uid) >=100 
        AND status=? AND payinfo=? GROUP BY uid';
        $res = Db::query($sql,[3,4]);
        return $res;
    }

    /**
     * 得到导出未支付Excel数据
     */
    public function getExcel($uid,$day,$params)
    {
        $sql = 'SELECT t1.*,a.username,a.contact,a.account_name,a.bank_name,a.bank_branch,a.bank_card,b.cpd,b.cpd_day,c.username as uname FROM (SELECT uid,adz_id,site_id,adtpl_id,SUM(sumpay) as sumpay,day FROM lz_stats_new WHERE uid=? AND day>=? AND day<=? GROUP BY adz_id,day) AS t1
            LEFT JOIN lz_users AS a ON t1.uid=a.uid
            LEFT JOIN lz_adzone_copy AS b ON t1.adz_id=b.adz_id AND t1.day=b.cpd_day
            LEFT JOIN lz_users AS c ON a.serviceid=c.uid';
        $res = Db::query($sql,[$uid,$day,$params['sun']]);
        return $res;
    }

    /**
     * 得到已支付Excel数据
     */
    public function getDay($params)
    {
        $sql = 'SELECT uid,day,status,clearingadmin,money,payinfo FROM lz_paylog WHERE day>=? AND day<=? AND status=?';
        $res = Db::query($sql,[$params['sun'],$params['today'],1]);
        return $res;
    }

    /**
     * 得到已支付Excel数据
     */
    public function getExcelpaid($value,$params)
    {
        $sql = 'SELECT t1.*,a.username,a.contact,a.account_name,a.bank_name,a.bank_branch,a.bank_card,b.cpd,b.cpd_day,c.username as uname FROM (SELECT uid,adz_id,site_id,adtpl_id,SUM(sumpay) as sumpay,day FROM lz_stats_new WHERE uid=? AND day>=? AND day<=? GROUP BY adz_id,day) AS t1
            LEFT JOIN lz_users AS a ON t1.uid=a.uid
            LEFT JOIN lz_adzone_copy AS b ON t1.adz_id=b.adz_id AND t1.day=b.cpd_day
            LEFT JOIN lz_users AS c ON a.serviceid=c.uid';
        $res = Db::query($sql,[$value['uid'],$value['clearingadmin'],$params['sun']]);
        return $res;
    }

    /**
     * 得到站长上一周的金额
     *  param date
     */
    public function webMoney($uid,$params)
    {
        $sql = 'SELECT a.uid,a.adz_id,a.day,SUM(a.sumpay) as sumpay,b.money,b.type,c.cpd FROM lz_stats_new AS a LEFT JOIN lz_users 
                 AS b ON a.uid=b.uid LEFT JOIN lz_adzone_copy AS c ON a.day=c.cpd_day AND a.adz_id=c.adz_id 
                 WHERE b.uid=? AND a.day>=? AND a.day<=?';
        $res = Db::query($sql,[$uid,$params['mon'],$params['sun']]);
        return $res;
    }

    /**
     * 得到广告位包天的钱
     *  param date
     */
    public function adzMoney($params)
    {
        $sql = 'SELECT uid,SUM(cpd) AS cpd,adz_id,cpd_day FROM lz_adzone_copy WHERE uid=? AND cpd_day>=? AND cpd_day<=? GROUP BY adz_id';
        $res = Db::query($sql,[$params['uid'],$params['mon'],$params['sun']]);
        return $res;
    }

    /**
     * 得到站长已支付列表
     * param data
     */
    public function paided($params,$offset,$count)
    {
        $sql = 'SELECT b.id,b.uid,a.bank_name,a.bank_card,a.account_name,a.money as money,b.money AS actualMoney,a.username,b.ctime,b.day,b.status,c.username as uname,
             b.clearingtype,b.payinfo FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a.uid=b.uid LEFT JOIN lz_users AS c ON a.serviceid=c.uid';
        if(!empty($params['search'])){
            if($params['paylog'] == 'username'){
                $sele = ltrim($params['search']);
                $sql.=  ' WHERE a.type=?  AND b.status=? AND a.username like "%'.$sele.'%" ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,1,$offset,$count]);
            }elseif ($params['paylog'] == 'uid') {
                $sql.=  ' WHERE a.type=?  AND b.status=? AND a.uid=? ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,1,$params['search'],$offset,$count]);
            }elseif ($params['paylog'] == 'uname' && $params['type'] != 2) {
                $sele = ltrim($params['search']);
                $sql.=  ' WHERE a.type=?  AND b.status=? AND c.username like "%'.$sele.'%"  ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,1,$offset,$count]);
            }else{
                $sele = ltrim($params['search']);
                $sql.=  ' WHERE a.type=?  AND b.status=? AND c.username like "%'.$sele.'%"  ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,1,$offset,$count]);
            }
        }else{
            $sql.= ' WHERE a.type=? AND b.status=?  ORDER BY id DESC LIMIT ?,? ';
            $res = Db::query($sql,[1,1,$offset,$count]);

        }

        return $res;

    }

    /**
     *  统计已支付的个数
     */
    public function paidCount($params)
    {
        $sql = 'SELECT COUNT(b.status) AS count,c.username as uname FROM lz_users AS a
             LEFT JOIN lz_paylog AS b ON a.uid=b.uid LEFT JOIN lz_users AS c ON a.serviceid=c.uid ';
        if(!empty($params['search'])){
            if($params['paylog'] == 'username'){
                $sele = ltrim($params['search']);
                $sql.= ' WHERE b.status=? AND b.type=? AND a.username like "%'.$sele.'%" ';
                $res = Db::query($sql,[1,1]);
            }elseif ($params['paylog'] == 'uid') {
                $sql.= ' WHERE b.status=? AND b.type=? AND a.uid=? ';
                $res = Db::query($sql,[1,1,$params['search']]);
            }elseif ($params['paylog'] == 'uname') {
                $sele = ltrim($params['search']);
                $sql.= ' WHERE b.status=? AND b.type=? AND c.username like "%'.$sele.'%" ';
                $res = Db::query($sql,[1,1]);
            }
        }else{
            $sql.= ' WHERE b.status=? AND b.type=? ';
            $res = Db::query($sql,[1,1]);
        }
        return $res[0]['count'];
    }

    /**
     *  等待支付
     */
    public function waitPay($params,$offset,$count)
    {
        $sql = 'SELECT b.id,b.uid,a.bank_name,a.bank_card,a.account_name,a.money,b.money AS actualMoney,a.username,b.ctime,b.day,b.status,c.username as uname,
             b.clearingtype,b.payinfo,b.ctime FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a.uid=b.uid LEFT JOIN lz_users AS c ON a.serviceid=c.uid ';
        if(!empty($params['search'])) {
            if($params['payinfo'] == 'username') {
                $sele = ltrim($params['search']);
                $sql.=  ' WHERE a.type=?  AND b.status=? AND a.username like "%'.$sele.'%" AND b.money>0 ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,0,$offset,$count]);
            }elseif ($params['payinfo'] == 'uid') {
                $sql.=  ' WHERE a.type=?  AND b.status=? AND a.uid=? AND b.money>0 ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,0,$params['search'],$offset,$count]);
            }elseif ($params['payinfo'] == 'uname') {
                $sele = ltrim($params['search']);
                $sql.=  ' WHERE a.type=?  AND b.status=? AND c.username like "%'.$sele.'%" AND b.money>0 ORDER BY b.id DESC LIMIT ?,?';
                $res = Db::query($sql,[1,0,$offset,$count]);
            }
        }else{
            $sql.= ' WHERE b.status=? AND b.type=? AND b.money>0 ORDER BY id DESC LIMIT ?,? ';
            $res = Db::query($sql,[0,1,$offset,$count]);
        }
        return $res;
    }

    /**
     *  统计等待支付个数
     */
    public function waitCount($params)
    {
        $sql = 'SELECT COUNT(b.status) AS count,b.day,c.username as uname FROM lz_users AS a
             LEFT JOIN lz_paylog AS b ON a.uid=b.uid LEFT JOIN lz_users AS c ON a.serviceid=c.uid  ';
        if(!empty($params['search'])){
            if($params['payinfo'] == 'username'){
                $sele = ltrim($params['search']);
                $sql.= ' WHERE b.status=? AND b.type=? AND a.username like "%'.$sele.'%" AND b.money>0';
                $res = Db::query($sql,[0,1]);
            }elseif ($params['payinfo'] == 'uid') {
                $sql.= ' WHERE b.status=? AND b.type=? AND a.uid=? AND b.money>0';
                $res = Db::query($sql,[0,1,$params['search']]);
            }elseif ($params['payinfo'] == 'uname') {
                $sele = ltrim($params['search']);
                $sql.= ' WHERE b.status=? AND b.type=? AND c.username like "%'.$sele.'%" AND b.money>0';
                $res = Db::query($sql,[0,1]);
            }
        }else{
            $sql.= ' WHERE b.status=? AND b.type=? AND b.money>0';
            $res = Db::query($sql,[0,1]);
        }
        return $res[0]['count'];
    }



    /**
     *  结算等待支付后更新状态为已支付
     */
    public function updateStatus($uid)
    {
        $map = array(
            'id' =>$uid,
        );
        $data = array(
            'status' => '1',
            'day' => date('Y-m-d',time()),
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     *  删除等待支付后更新站长的金额
     */
    public function updateMoney($uid)
    {
        $sql = 'UPDATE lz_users SET money=money+? WHERE uid=? ';
        $res = Db::execute($sql,[$uid['sum'],$uid['uid']]);
        return $res;
    }

    /**
     * 删除等待支付后更新paylog金额
     */
    public function updatePay($uid)
    {
        $sql = 'UPDATE lz_paylog SET status=?,xmoney=?,money=?,payinfo=? where id=? AND uid=? ';
        $res = Db::execute($sql,[3,$uid['sum'],0,4,$uid['id'],$uid['uid']]);
        return $res;
    }

    /**
     *  支付记录
     */
    public function setlog($data)
    {
        $res = Db::name('paylog')->insert($data);
        return $res;
    }

    /**
     *充值管理广告商充值记录
     *param date
     */
    public function adsRecharge($params,$offset,$count)
    {
        $sql = 'SELECT b.id,a.money as cmoney,a.username,b.ctime,b.money,b.status,b.uid,b.clearingadmin,b.payinfo,b.day
             FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            if($params['recharges'] == 'username'){
                $sql.=' WHERE b.type=? AND a.username =? ORDER BY id DESC LIMIT ?,?  ';
                $res = Db::query($sql,[2,$params['search'],$offset,$count]);
            }elseif($params['recharges'] == 'uid'){
                $sql.=' WHERE b.type=? AND b.uid =? ORDER BY id DESC LIMIT ?,?  ';
                $res = Db::query($sql,[2,$params['search'],$offset,$count]);
            }else{
                $startday = substr($params['search'],0,stripos($params['search'],'至'));
                $endday = substr($params['search'],stripos($params['search'],'至')+3);
                $sql.=' WHERE b.type=? AND b.day>=? AND b.day<=? ORDER BY id DESC LIMIT ?,?  ';
                $res = Db::query($sql,[2,$startday,$endday,$offset,$count]);
            }
        }else{
            $sql.= ' WHERE b.type=? AND b.ctime !="" ORDER BY id DESC LIMIT ?,?  ';
            $res = Db::query($sql,[2,$offset,$count]);
        }
        return $res;
    }

    /**
     *  统计广告商的个数
     */
    public function adsCount($params)
    {
        $sql = 'SELECT COUNT(b.id) AS count FROM lz_users AS a
             LEFT JOIN lz_paylog AS b ON a.uid=b.uid ';
        if(!empty($params['search'])){
            if($params['recharges'] == 'username'){
                $sql.= 'WHERE a.type=? AND a.username=? ';
                $res = Db::query($sql,[2,$params['search']]);
            }elseif($params['recharges'] == 'uid'){
                $sql.='WHERE b.type=? AND b.uid =?';
                $res = Db::query($sql,[2,$params['search']]);
            }else{
                $startday = substr($params['search'],0,stripos($params['search'],'至'));
                $endday = substr($params['search'],stripos($params['search'],'至')+3);
                $sql.=' WHERE b.type=? AND b.day>=? AND b.day<=?';
                $res = Db::query($sql,[2,$startday,$endday]);
            }
        }else{
            $sql .= ' WHERE a.type=?';
            $res = Db::query($sql,[2]);
        }
        return $res[0]['count'];
    }

    /**
     * 获取广告商日消耗(日支出)
     */
    public function advReportNow($uid)
    {
        $param = date("Y-m-d");
        $sql = 'SELECT a.views,a.num,SUM(a.sumadvpay),a.plan_type,a.web_deduction,b.money,c.price FROM lz_stats_new AS a LEFT JOIN lz_users AS b
        ON a.uid=b.uid LEFT JOIN lz_plan_price AS c ON a.tc_id=c.id WHERE a.adv_id=? AND a.day=? ORDER BY plan_type';

        $res = Db::query($sql,[$uid,$param]);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }

    }

    /**
     *  手动充值
     *  查询 会员表 广告商是否存在
     */
    public function advUserOne($username)
    {
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
            return "";
        }else{
            return $res[0];
        }
    }

    /**
     *  充值金额 广告商
     */
    public function advertiserPay($params,$adv_res)
    {
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
     *  批量删除充值记录
     */
    public function del($ids)
    {
        Db::startTrans();
        try {
            $Paylog = new Paylog;
            $res = $Paylog::destroy($ids);
            Db::commit();
            if($res>0){
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
     * 获取站长stats当天的数据
     */
    public function webMoneyPay($uid,$date)
    {
        $sql = 'SELECT SUM(a.sumpay) as money,a.adz_id,b.cpd,b.cpd_day FROM lz_stats_new AS a LEFT JOIN lz_adzone_copy AS b 
        ON a.adz_id =b.adz_id WHERE a.uid=? AND a.day=?';
        $res = Db::query($sql,[$uid,$date]);
        if(empty($res)){
            return 0;
        }else{
            return $res['0'];
        }
    }

    /**
     *  获取广告商充值记录列表
     */
    public function advList($params)
    {
        $sql = 'SELECT b.id,a.money as cmoney,a.username,b.ctime,b.money,b.status,b.uid,b.clearingadmin,b.payinfo,b.day
             FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a.uid=b.uid ';
        if(empty($params['search'])){
            $sql.=' WHERE b.type=? ORDER BY id DESC';
            $res = Db::query($sql,[2]);
        }else{
            if($params['select'] == 'username'){
                $sql.=' WHERE b.type=? AND a.username =? ORDER BY id DESC';
                $res = Db::query($sql,[2,$params['search']]);
            }elseif($params['select'] == 'uid'){
                $sql.=' WHERE b.type=? AND b.uid =? ORDER BY id DESC ';
                $res = Db::query($sql,[2,$params['search']]);
            }elseif($params['select'] == 'date'){
                $startday = substr($params['search'],0,stripos($params['search'],'至'));
                $endday = substr($params['search'],stripos($params['search'],'至')+3);
                $sql.=' WHERE b.type=? AND b.day>=? AND b.day<=? ORDER BY id DESC ';
                $res = Db::query($sql,[2,$startday,$endday]);
            }
        }
        return $res;
    }
    //站长已支付列表
    public function zhifu()
    {
        $sql = 'SELECT b.id,b.uid,a.username,a.bank_name,a.bank_card,a.account_name,a.money as money,b.money AS actualMoney,a.username,b.ctime,b.day,b.status,c.username as uname,
             b.clearingtype,b.payinfo FROM lz_users AS a LEFT JOIN lz_paylog AS b ON a.uid=b.uid LEFT JOIN lz_users AS c ON a.serviceid=c.uid';

        $sql.= ' WHERE a.type=? AND b.status=?  ORDER BY id DESC ';
        $res = Db::query($sql,[1,1]);


        return $res;


    }
    /**
     * 得到站长的列表
     */
    public function zhanzhangList()
    {
        $sql = 'SELECT a.uid,a.username,a.type,a.contact,a.money,a.idcard,a.account_name,a.bank_name,a.bank_branch,a.bank_card,b.username as uname,t1.* FROM (SELECT uid,status,min(day) as day,SUM(xmoney) as xmoney FROM lz_paylog  GROUP BY uid) AS t1 LEFT JOIN lz_users AS a ON t1.uid=a.uid
            LEFT JOIN lz_users AS b ON a.serviceid=b.uid ';
        $sql.=  ' ORDER BY t1.xmoney DESC';
        $res = Db::query($sql);
        return $res;
    }
}
