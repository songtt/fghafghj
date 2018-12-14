<?php

namespace app\admin\model;
use think\Db;

class Monitor extends \think\Model
{
    /**
     * 获取昨日统计数据
     */
    public function getlastviews($params)
    {
        $sql = 'SELECT t.views,t.uid,a.username FROM (SELECT SUM(views) as views,uid FROM lz_stats_new WHERE day=? GROUP BY uid)
                as t LEFT JOIN lz_users as a ON a.uid=t.uid WHERE t.views>2000 ORDER BY t.views DESC';
        $res = Db::query($sql,[$params['yester']]);
        return $res;
    }

    /**
     * 获取前7日统计数据
     */
    public function getsevenviews($params)
    {
        $sql = 'SELECT ceiling(t.views/7) as views,t.uid,a.username FROM (SELECT SUM(views) as views,uid FROM lz_stats_new WHERE day>=? AND day<=? GROUP BY uid)
                as t LEFT JOIN lz_users as a ON a.uid=t.uid WHERE t.views>2000 ORDER BY t.views DESC';
        $res = Db::query($sql,[$params['front'],$params['end']]);
        return $res;
    }

    /**
     * 查看最开始一天是否有数据
     */
    public function getfrontviews($params,$uid)
    {
        $sql = 'SELECT SUM(views) as views,uid FROM lz_stats_new WHERE day=? AND uid=?';
        $res = Db::query($sql,[$params['front'],$uid]);
        return $res;
    }

    /**
     * 获取昨日点击率
     */
    public function getlastclick($params)
    {
        $sql = 'SELECT t.click_num/t.views as click_percent,t.views,t.uid,a.username FROM (SELECT SUM(views) as views,SUM(click_num) as click_num,uid FROM lz_stats_new WHERE day=? GROUP BY uid)
                as t LEFT JOIN lz_users as a ON a.uid=t.uid WHERE t.views>2000 ORDER BY t.views DESC';
        $res = Db::query($sql,[$params['yester']]);
        return $res;
    }

    /**
     * 获取前7日点击率
     */
    public function getsevenclick($params)
    {
        $sql = 'SELECT ceiling(t.views/7) as views,t.click_num/t.views as click_percent,t.uid,a.username FROM (SELECT SUM(views) as views,SUM(click_num) as click_num,uid FROM lz_stats_new WHERE day>=? AND day<=? GROUP BY uid)
                as t LEFT JOIN lz_users as a ON a.uid=t.uid WHERE t.views>2000 ORDER BY t.views DESC';
        $res = Db::query($sql,[$params['front'],$params['end']]);
        return $res;
    }

}