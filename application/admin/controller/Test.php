<?php
/* 数据监控
 * @date   2017-8-11
 */
namespace app\admin\controller;

use think\Request;
use think\Db;

class Test extends Admin
{
    /**
     * pv监控列表
     */
    public function test()
    {
     exit;
        $request = Request::instance();
        $pageParam = $request->param('');
        $pageParam['yester'] = '2017-09-18';
        $pageParam['end'] = '2017-09-17';
        $pageParam['front'] = '2017-09-11';

        $sql = 'SELECT t.views,t.uid,a.username FROM (SELECT SUM(views) as views,uid FROM lz_stats_new WHERE day=? GROUP BY uid)
                as t LEFT JOIN lz_users as a ON a.uid=t.uid WHERE t.views>200 ORDER BY t.views DESC';
        $yester= Db::query($sql,[$pageParam['yester']]);

        $sql = 'SELECT ceiling(t.views/7) as views,t.uid,a.username FROM (SELECT SUM(views) as views,uid FROM lz_stats_new WHERE day>=? AND day<=? GROUP BY uid)
                as t LEFT JOIN lz_users as a ON a.uid=t.uid WHERE t.views>200 ORDER BY t.views DESC';
        $seven = Db::query($sql,[$pageParam['front'],$pageParam['end']]);

        foreach($yester as $key=>$value){
            foreach($seven as $k=>$v){
                if($value['uid'] == $v['uid']){

                    if(($value['views'] - $v['views'])/$v['views'] <= -0.3){
                        $data[]  = array(
                            'uid' => $value['uid'],
                            'username' => $value['username'],
                            'notice' => '昨日访问量为'.$value['views'].';前7日平均访问量为'.$v['views'].';增减量为  '.ceil(($value['views'] - $v['views'])/$v['views'] * 100).'%',
                            'status' => ($value['views'] - $v['views'])> 0 ? 1 : 2 //1为暴增，2为暴减
                        );
                    }

                }
            }
        }
        dump($data);exit;
        $this->assign('one',$data);
        $this->assign('page','');
        return $this->fetch('monitor\monitor-pvlist');
    }


    public function test2()
    {
        $sql = " select t.pid,b.plan_name,t.sumadvpay from ( select  pid,SUM(sumadvpay) as sumadvpay from lz_stats_new where adv_id=1021  AND  `day`<='2017-08-31' AND `day`>='2017-08-01'  GROUP BY pid  ) as  t LEFT JOIN lz_plan as b ON t.pid=b.pid  ; ";
        $yester= Db::query($sql);
        // dump($yester);

        foreach ($yester as $key => $value) {
            if(empty($value['plan_name'])){
                $value['plan_name'] = 'null';
            }
            echo $value['pid'],'-------',$value['plan_name'],'-------',$value['sumadvpay'],'<br>';
        }
    }

}
