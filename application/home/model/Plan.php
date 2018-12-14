<?php
namespace app\home\model;
use think\Db;

class Plan 
{
    /**
     * 得到该商务下待审计划 top50
     * param data
     */
    public function getPlan($uid)
    {
        $sql = 'SELECT a.uid,a.pid,a.plan_name,a.plan_type,a.clearing,
        b.uid,b.serviceid,b.username FROM lz_plan as a LEFT JOIN lz_users as b ON a.uid=b.uid  
        WHERE a.status=2 and b.serviceid=? ORDER BY a.ctime DESC LIMIT 0,50';
        $res = Db::query($sql,[$uid]);
        return $res;
    }

    /**
     * 更新计划状态
     * param pid status 状态
     */
    public function updateStatus($pid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'pid'=>$pid,
        );
        $res = Db::name('plan')->where($map)->update($data);
        return $res;
    }

    /**
     * 得到该商务下的待审计划
     * param uid
     */
    public function getPlanNum($uid)
    {
        $sql = 'SELECT count(a.uid) as count FROM lz_plan as a LEFT JOIN lz_users as b ON a.uid=b.uid  
        WHERE a.status=2 and b.serviceid=?';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0]['count'];
        }
    }

}