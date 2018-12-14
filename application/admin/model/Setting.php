<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class Setting extends \think\Model
{
    
    /**
     * 得到列表
     * param data
     */
    public function getone()
    {
        $sql = 'SELECT id,cpc_deduction,cpm_deduction,cpv_deduction,cps_deduction,cpa_deduction,sitename,cpc,cpm,cpv,
        cps,cpa,domain_limit,pv_step,maximum_clicks,opne_affiliate_register,opne_advertiser_register,register_status,add_website,
        site_status,24_hours_register_num,ban_ip_register,login_check_code,registered_check_code,register_add_money_on,main_server,
        js_server,img_server,jump_server,https_server,adv_server,least_money,adv_money,bigclick_status FROM lz_setting ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 更新基本设置
     */
    public function UpdateBasic($data)
    {
        $map = array(
            'id'=>'1',
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 查询更新服务器地址的数据
     */
    public function Batchfind($data)
    {
        $title = $data['type'];
        $contfindurl = $data['find_url'];

        $sql = 'select ad_id from lz_ads where '.$title.' like "%'.$contfindurl.'%" ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 批量更新服务器地址
     */
    public function Batchupdate($data,$ad_id)
    {
        $title = $data['type'];
        $contfindurl = $data['find_url'];
        $contupdateurl = $data['update_url'];
        $sql = 'UPDATE lz_ads SET '.$title.'= REPLACE('.$title.',"'.$contfindurl.'","'.$contupdateurl.'") WHERE ad_id in ('.$ad_id.')';

        $res = Db::execute($sql);
        return $res;
    }

    /**
     * 查询权限内容
     */
    public function getRolesName()
    {
        $sql = 'SELECT id,name,title,status FROM lz_auth_rule';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询权限内容
     */
    public function getRoles()
    {
        $sql = 'SELECT a.uid,b.title,c.username FROM lz_auth_group_access as a LEFT JOIN lz_auth_group as b
        ON a.group_id=b.id LEFT JOIN lz_administrator as c ON a.uid=c.id';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询管理员权限
     */
    public function getAuthName($id)
    {
        $sql = 'SELECT name FROM lz_auth_rule WHERE id=?';
        $res = Db::query($sql,[$id]);
        $res[0] = empty($res[0]) ? '' : $res[0];
        return $res[0];
    }

	/**
     * yyblizz 账号下首页查询要提示的广告位和计划
     */
    public function getlizzlist()
    {
        $sql = 'SELECT a.id,a.pid,a.adz_id,a.uid,a.plan_name,a.adz_name,a.type,a.terminal,b.username,c.username as customer
                FROM lz_reminding as a LEFT JOIN lz_users as b ON a.uid=b.uid LEFT JOIN lz_users as c ON b.serviceid=c.uid
                ORDER BY a.pid DESC,a.id DESC ';
        $res = Db::query($sql);
        $res = empty($res) ? array() : $res;
        return $res;
    }

    /**
     * 删除 首页 yyblizz 账号下不需要显示的计划或者广告位
     */
    public function delLizzlist($id)
    {
        $sql = 'DELETE FROM lz_reminding WHERE id = ?';
        $res = Db::query($sql,[$id]);
        return $res;
    }

    /**
     * 查询管理员权限
     */
    public function getAuth($id)
    {
        $sql = 'SELECT a.group_id,b.title,b.rules FROM lz_auth_group_access as a LEFT JOIN lz_auth_group as b
        ON a.group_id=b.id WHERE a.uid=?';
        $res = Db::query($sql,[$id]);
        return $res;
    }

    /**
     * 更新管理员的权限
     */
    public function updateAuth($date,$id)
    {
        $map = array(
            'id'=>$id,
        );
        $res = DB::name('auth_group')->where($map)->update($date);
        return $res;
    }

    /**
     * 得到点击数基数
     * param data
     */
    public function get_ads_sel_click()
    {
        $sql = 'SELECT ads_sel_click FROM lz_setting limit 0,1';
        $res = Db::query($sql);
        if (empty($res)) {
            return 0;
        }else{
            return $res[0]['ads_sel_click'];
        }
    }

    /**
     * 修改广告筛选器基数
     * param data
     */
    public function saveAsc($click='')
    {
        $map = array(
            'id'=>'1',
        );
        $data = array(
            'ads_sel_click' => $click
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 获取js的服务器地址
     */
    public function getJsService()
    {
        $sql = 'SELECT js_server FROM lz_setting';
        $res = Db::query($sql);
        if (empty($res)) {
            return '';
        }else{
            return $res['0'];
        }
    }

    /**
     * 判断此用户是不是超级管理员，处理一个function中的细节权限（页面扣量）
     */
    public function getDedutionAuth($uid)
    {
        $sql = 'SELECT group_id FROM lz_auth_group_access WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        if (empty($res)) {
            return '';
        }else{
            return $res['0'];
        }
    }

}
