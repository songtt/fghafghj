<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class Deduction extends \think\Model
{
    /**
     * 得到列表
     * param data
     */
    public function getone()
    {
        $sql = 'SELECT cpc_deduction,cpm_deduction,adv_cpc_deduction,adv_cpm_deduction FROM lz_setting WHERE id=1';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 更新网站扣量
     */
    // public function siteDeduction($params)
    // {
    //     $map = array(
    //         'site_id'=>$params['site_id'],
    //     );
    //     $res = DB::name('site')->where($map)->update($params);
    //     return $res;
    // }


    /**
     * 查询网站扣量
     */
    // public function getSite()
    // {
    //     $sql = 'SELECT site_id,sitename,web_deduction,adv_deduction FROM lz_site';
    //     $res = Db::query($sql);
    //     return $res;
    // }

    /**
     * 查询网站扣量
     */
    // public function postSite($params)
    // {
    //     $sql = 'SELECT site_id,sitename,web_deduction,adv_deduction FROM lz_site WHERE sitename=?';
    //     $res = Db::query($sql,[$params['sitename']]);
    //     return $res;
    // }

    /**
     * 查询广告位扣量
     */
    public function getZone()
    {
        $sql = 'SELECT adz_id,uid,zonename,web_deduction,adv_deduction FROM lz_adzone';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 更新广告位扣量
     */
    public function zoneDeduction($params)
    {
       $map = array(
            'adz_id'=>$params['adz_id'],
        );
       $arr = array(
            'web_deduction' =>$params['web_deduction'] ,
            'adv_deduction' =>$params['adv_deduction']);

        $res = DB::name('adzone')->where($map)->update($arr);
        return $res;
    }

    /**
     * 查询站长是否存在
     */
    public function postZone($params)
    {
        $sql = 'SELECT adz_id,zonename,web_deduction,adv_deduction FROM lz_adzone WHERE adz_id=?';
        $res = Db::query($sql,[$params['webname']]);
        return $res;
    }

    /**
     * 更新站长扣量
     */
    public function webDeduction($params)
    {
        $map = array(
            'uid'=>$params['uid'],
        );
        $data = array(
            'web_deduction' => $params['web_deduction'],
            'adv_deduction' => $params['adv_deduction']
        );
        $res = DB::name('users')->where($map)->update($data);
        DB::name('users_log')->where($map)->update($data);
        return $res;
    }


    /**
     * 查询站长扣量
     */
    public function getWeb()
    {
        $sql = 'SELECT uid,username,web_deduction,adv_deduction FROM lz_users WHERE type=1';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询网站扣量
     */
    public function postWeb($params)
    {
        $sql = 'SELECT uid,username,web_deduction,adv_deduction FROM lz_users WHERE type=1 AND username=?';
        $res = Db::query($sql,[$params['username']]);
        return $res;
    }

    /**
     * 更新广告扣量
     */
    public function adsDeduction($params)
    {
        $map = array(
            'ad_id'=>$params['ad_id'],
        );
        $res = DB::name('ads')->where($map)->update($params);
        return $res;
    }

    /**
     * 查询广告扣量
     */
    public function getAds()
    {
        $sql = 'SELECT ad_id,adname,web_deduction,adv_deduction FROM lz_ads';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询广告扣量
     */
    public function postAds($params)
    {
        $sql = 'SELECT ad_id,adname,web_deduction,adv_deduction FROM lz_ads WHERE adname=?';
        $res = Db::query($sql,[$params['adname']]);
        return $res;
    }

    /**
     * 更新计划扣量
     */
    public function planDeduction($params)
    {
        $map = array(
            'pid'=>$params['pid'],
        );
        $res = DB::name('plan')->where($map)->update($params);
        return $res;
    }

    /**
     * 查询计划扣量
     */
    public function getPlan()
    {
        $sql = 'SELECT pid,plan_name,web_deduction,deduction FROM lz_plan';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询计划扣量
     */
    public function postPlan($params)
    {
        $sql = 'SELECT pid,plan_name,web_deduction,deduction FROM lz_plan WHERE plan_name=?';
        $res = Db::query($sql,[$params['plan_name']]);
        return $res;
    }

    /**
     * 更新全局扣量
     */
    public function UpdateDeduction($params)
    {
        $map = array(
            'id'=>1,
        );
        $res = DB::name('setting')->where($map)->update($params);
        return $res;
    }
}
