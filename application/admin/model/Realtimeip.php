<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class Realtimeip extends \think\Model
{

    /**
     * 只查询实时IP表当页显示的数据
     */
    public function ipLst($offset,$count,$params)
    {
        $sql = 'SELECT a.id,a.uid,a.pid,a.ad_id,a.ip,a.regional,a.type,a.day,a.validity,a.stress_number,a.records_time,
        b.pid,b.plan_name,b.plan_type,c.uid,c.username FROM lz_realtimeip as a LEFT JOIN lz_plan as b ON a.pid=b.pid
        LEFT JOIN lz_users as c ON a.uid=c.uid';
        if(empty($params['ipnum'])){
            if(empty($params['plan'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? LIMIT ?,? ';
                $res = Db::query($sql,[$params['day'],$params['day1'],$offset,$count]);
            }else{
                $sql.= ' WHERE b.pid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                $res = Db::query($sql,[$params['plan'],$params['day'],$params['day1'],$offset,$count]);
            }
        }else{
            if($params['ipname'] == 'ip'){
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.ip=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['day'],$params['day1'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.ip=? AND b.pid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$params['day'],$params['day1'],$offset,$count]);
                }
            }elseif($params['ipname'] == 'uid'){
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.uid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['day'],$params['day1'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.uid=? AND b.pid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$params['day'],$params['day1'],$offset,$count]);
                }
            }elseif($params['ipname'] == 'pid'){
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.pid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['day'],$params['day1'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.pid=? AND b.pid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$params['day'],$params['day1'],$offset,$count]);
                }
            }else{
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.ad_id=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['day'],$params['day1'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.ad_id=? AND b.pid=? AND a.day>=? AND a.day<=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$params['day'],$params['day1'],$offset,$count]);
                }
            }
        }
        return $res;
    }

    /**
     * 只查询实时IP表当页显示的数据(所有时间段的情况下)
     */
    public function ipAllLst($offset,$count,$params)
    {
        $sql = 'SELECT a.id,a.uid,a.pid,a.ad_id,a.ip,a.regional,a.type,a.day,a.validity,a.stress_number,a.records_time,
        b.pid,b.plan_name,b.plan_type,c.uid,c.username FROM lz_realtimeip as a LEFT JOIN lz_plan as b ON a.pid=b.pid
        LEFT JOIN lz_users as c ON a.uid=c.uid';
        if(empty($params['ipnum'])){
            if(empty($params['plan'])){
                $sql.= ' LIMIT ?,? ';
                $res = Db::query($sql,[$offset,$count]);
            }else{
                $sql.= ' WHERE b.pid=? LIMIT ?,? ';
                $res = Db::query($sql,[$params['plan'],$offset,$count]);
            }
        }else{
            if($params['ipname'] == 'ip'){
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.ip=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.ip=? AND b.pid=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$offset,$count]);
                }
            }elseif($params['ipname'] == 'uid'){
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.uid=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.uid=? AND b.pid=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$offset,$count]);
                }
            }elseif($params['ipname'] == 'pid'){
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.pid=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.pid=? AND b.pid=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$offset,$count]);
                }
            }else{
                if(empty($params['plan'])){
                    $sql.= ' WHERE a.ad_id=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$offset,$count]);
                }else{
                    $sql.= ' WHERE a.ad_id=? AND b.pid=? LIMIT ?,? ';
                    $res = Db::query($sql,[$params['ipnum'],$params['plan'],$offset,$count]);
                }
            }
        }
        return $res;
    }

    /**
     * 获取总IP数
     */
    public function ipCount()
    {
        $sql = 'SELECT MAX(id) as count FROM lz_realtimeip';
        $res = Db::query($sql);
        return $res[0];
    }

    /**
     * 获取所有的计划名称
     */
    public function planName()
    {
        $sql = 'SELECT a.type,a.pid,b.plan_name FROM lz_realtimeip as a LEFT JOIN lz_plan as b ON a.pid=b.pid
        ORDER BY pid';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 批量删除实时IP
     */
    public function delIp($ids)
    {
        $Realtimeip = new Realtimeip;
        $res = $Realtimeip::destroy($ids);
        return $res;
    }

    /**
     * 得到数据报表个数时不同的sql文
     */
    public function _getreportLstCount($params)
    {
        if($params['stats'] == 'plan_list'){
            $sql = 'SELECT count(b.pid) as count FROM lz_plan as a LEFT JOIN lz_stats_new as b ON a.pid=b.pid';
        }elseif($params['stats'] == 'user_list'){
            $sql = 'SELECT count(b.uid) as count FROM lz_users as a LEFT JOIN lz_stats_new as b ON a.uid=b.uid';
        }elseif($params['stats'] == 'ads_list'){
            $sql = 'SELECT count(b.ad_id) as count FROM lz_ads as a LEFT JOIN lz_stats_new as b ON a.ad_id=b.ad_id';
        }else{
            $sql = 'SELECT count(b.adz_id) as count FROM lz_adzone as a LEFT JOIN lz_stats_new as b ON a.adz_id=b.adz_id';
        }
        return $sql;
    }

}
