<?php
/**
 * 广告
 * date 2016-8-15 16:29:49
 */
namespace app\home\model;
use think\Model;
use think\Db;

class Ads extends Model
{
    /**
     * 得到该商务下待审广告 top50
     * param data
     */
    public function getAds($uid)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,a.status,b.plan_name,
        a.pid,b.plan_type,a.width,a.height,c.tplname FROM lz_ads as a LEFT JOIN lz_plan as b 
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid 
        WHERE a.status=2 and d.serviceid=? ORDER BY a.ctime DESC LIMIT 0,50';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 更新广告状态
     * param ad_id status 状态
     */
    public function updateStatus($ad_id,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'ad_id'=>$ad_id,
        );
        $res = Db::name('ads')->where($map)->update($data);
        return $res;
    }

    /**
     * 得到该商务下的待审广告
     * param uid
     */
    public function getAdsNum($uid)
    {
        $sql = 'SELECT count(a.ad_id) as count FROM lz_ads as a LEFT JOIN lz_plan as b 
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid 
        WHERE a.status=2 and d.serviceid=?';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0]['count'];
        }
    }

}