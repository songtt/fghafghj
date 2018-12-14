<?php
/**
 * 会员
 * @date   2016-8-15 14:48:32
 */
namespace app\home\model;
use think\Db;
use think\Model;

class Users extends Model
{
    /**
     * 得到该商务下待审厂商 top50
     * param data
     */
    public function getAdser($uid)
    {
        $sql = 'SELECT * FROM lz_users WHERE status=2 and serviceid=? ORDER BY ctime DESC LIMIT 0,50';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 得到列表
     * param data
     */
    public function getList($offset,$count,$type)
    {
        $sql ='SELECT b.username AS uname,a.uid,a.username,a.password,a.type,a.settled_amount,a.nosettled_amount,
        a.customer_cervice,a.email,a.qq,a.tel,a.mobile,a.contact,a.idcard,a.deduction,a.money,a.account_name,a.bank_name,
        a.bank_branch,a.bank_card,a.status,a.insite,a.remark,a.serviceid,a.login_time FROM lz_users AS a LEFT JOIN
        lz_users AS b ON a.serviceid=b.uid WHERE a.type=? ORDER BY a.uid DESC LIMIT ?,?';
        $res = Db::query($sql,[$type,$offset,$count]);
        return $res;
    }

    /**
     * 商务后台-广告商管理-会员管理
     * param uid
     */
    public function getLstByAdver($uid)
    {
        $sql =' SELECT uid,username,money,contact,qq,status FROM lz_users as a WHERE a.serviceid=? ';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 更新会员状态
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
        $res = Db::name('users')->where($map)->update($data);
        Db::name('users_log')->where($map)->update($data);
        return $res;
    }

    /**
     * 查询当天新增下属会员
     */
    public function getTodaynum($uid,$time)
    {
        $sql = 'SELECT count(uid) as count FROM lz_users WHERE serviceid=? AND ctime>=?';
        $res = Db::query($sql,[$uid,$time]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0]['count'];
        }
    }

    /**
     * 得到该商务下待审厂商
     * param data
     */
    public function getAdserNum($uid)
    {
        $sql = 'SELECT count(uid) as count FROM lz_users WHERE status=2 and serviceid=? ';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0]['count'];
        }
    }

    /**
     * 获取js的服务器地址
     */
    public function getJsService()
    {
        $sql = 'SELECT js_server,https_server FROM lz_setting';
        $res = Db::query($sql);
        $res['0'] = empty($res) ? '' : $res['0'];
        return $res['0'];
    }

    /**
     * 获取js的服务器地址
     */
    public function getUname($id)
    {
        $sql = 'SELECT username FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$id]);
        $res['0'] = empty($res) ? '' : $res['0'];
        return $res['0'];
    }

    /**
     * 查询该身份下所有的会员
     * param data
     */
    public function getLogMoney()
    {
        $sql = 'SELECT id,money,uid FROM lz_user_money_tmp ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 修改总金额等
     * param uid status 状态
     */
    public function getMoney($params)
    {
        $sql = 'SELECT money FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$params['uid']]);
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
        $res = Db::query($sql,[$idfirst,$idlast]);

        //重置主键id
        $sql = 'alter table lz_user_money_tmp drop id';
        Db::query($sql);
        $sql = 'alter table lz_user_money_tmp add id int auto_increment primary key ';
        Db::query($sql);
        return $res;
    }

    /**
     * 获取广告位域名
     */
    public function getAdzService($adz_id)
    {
        $sql = 'SELECT domain_name as js_server,domain_name as https_server FROM lz_adz_domain WHERE adz_id=?';
        $res = Db::query($sql,[$adz_id]);
        $res['0'] = empty($res) ? '' : $res['0'];
        return $res['0'];
    }
    
}