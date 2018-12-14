<?php
/**
 * 逻辑层 处理数据逻辑
 * 会员
 * @date   2016-8-15 14:48:32
 */
namespace app\home\logic;
use think\Loader;

class Users 
{
    /**
     * 得到厂商 计划 广告 top50
     * param uid
     */
    public function getTop($uid)
    {
        //得到厂商top50
        $res['adver'] = Loader::model('Users')->getAdser($uid);

        //得到计划top50
        $res['plan'] = Loader::model('Plan')->getPlan($uid);

        //得到广告top50
        $res['ads'] = Loader::model('Ads')->getAds($uid);

        return $res;
    }

    /**
     * 得到个数
     * param uid
     */
    public function getNum($uid)
    {
        $time = strtotime(date("Y-m-d"));

        //查询当天下属新增会员个数
        $num['add'] = Loader::model('Users')->getTodaynum($uid,$time);

        //得到待审广告商个数
        $num['adver'] = Loader::model('Users')->getAdserNum($uid,$time);

        //得到待审计划个数
        $num['plan'] = Loader::model('Plan')->getPlanNum($uid,$time);

        //得到待审广告个数
        $num['ads'] = Loader::model('Ads')->getAdsNum($uid,$time);

        return $num;
    }

}