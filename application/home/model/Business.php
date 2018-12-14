<?php
namespace app\home\model;
use think\Model;
use think\Db;

class Business extends Model
{
    /**
     * 查询当天新增下属会员
     */
    public function getAdvs($id)
    {
        $sql = 'SELECT uid FROM lz_users WHERE serviceid=?';
        $res = Db::query($sql,[$id]);
        return $res;
    }

    /**
     * 全局配置域名
     */
    public function globalList()
    {
        $sql = 'select img_server,js_server,jump_server,domain_limit from lz_setting';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询商务当日业绩
     */
    public function getMoney($id,$day)
    {
        $sql = 'SELECT SUM(sumadvpay) AS money FROM lz_stats_log WHERE adv_id=? AND day=?';
        $res = Db::query($sql,[$id,$day]);
        $res[0] = empty($res[0]) ? '' : $res[0];
        return $res[0];
    }

    /**
     * 查询线下厂商的个数
     */
    public function getAdvListCount($uid,$params)
    {
        $sql = 'SELECT count(uid) as count FROM lz_users ';
        if(empty($params['num'])){
            $sql.= ' WHERE serviceid=? ';
            $res = Db::query($sql,[$uid]);

        }elseif($params['selectName'] == 'uname'){
            $sql.= ' WHERE serviceid=? AND username=? ';
            $res = Db::query($sql,[$uid,$params['num']]);

        }else{
            $sql.= ' WHERE serviceid=? AND uid=? ';
            $res = Db::query($sql,[$uid,$params['num']]);
        }
        return $res[0]['count'];
    }

    /**
     * 查询线下厂商(降序)
     */
    public function getAdvDes($date)
    {
        $sql = 'SELECT t1.uid,t1.username,t1.money,t1.contact,t1.qq,t1.status,t1.ad_count,t2.plan_count from
        (SELECT a.uid,a.username,a.money,a.contact,a.qq,a.status,a.ctime,count(t.uid)as ad_count FROM lz_users as a LEFT JOIN (SELECT uid FROM lz_ads ) t ON a.uid=t.uid  WHERE a.serviceid=? GROUP BY a.uid ) as  t1
        LEFT JOIN
        (SELECT a.uid,count(t.uid)as plan_count FROM lz_users as a LEFT JOIN (SELECT uid FROM lz_plan ) t ON a.uid=t.uid  WHERE a.serviceid=? GROUP BY a.uid ) as t2
        ON t1.uid=t2.uid ';
        if(empty($date['params']['num'])){
            $sql.= ' ORDER BY '.$date['params']['sortName'].' DESC ';
            $res = Db::query($sql,[$date['uid'],$date['uid']]);

        }elseif($date['params']['selectName'] == 'uname'){
            $sql.= ' WHERE t1.username=? ORDER BY '.$date['params']['sortName'].' DESC ';
            $res = Db::query($sql,[$date['uid'],$date['uid'],$date['params']['num']]);

        }else{
            $sql.= ' WHERE t1.uid=? ORDER BY '.$date['params']['sortName'].' DESC ';
            $res = Db::query($sql,[$date['uid'],$date['uid'],$date['params']['num']]);
        }
        return $res;
    }

    /**
     * 查询线下厂商(升序)
     */
    public function getAdvAs($date)
    {
        $sql = 'SELECT t1.uid,t1.username,t1.money,t1.contact,t1.qq,t1.status,t1.ad_count,t2.plan_count from
        (SELECT a.uid,a.username,a.money,a.contact,a.qq,a.status,a.ctime,count(t.uid)as ad_count FROM lz_users as a LEFT JOIN (SELECT uid FROM lz_ads ) t ON a.uid=t.uid  WHERE a.serviceid=? GROUP BY a.uid ) as  t1
        LEFT JOIN
        (SELECT a.uid,count(t.uid)as plan_count FROM lz_users as a LEFT JOIN (SELECT uid FROM lz_plan ) t ON a.uid=t.uid  WHERE a.serviceid=? GROUP BY a.uid ) as t2
        ON t1.uid=t2.uid ';
        if(empty($date['params']['num'])){
            $sql.= ' ORDER BY '.$date['params']['sortName'].' ';
            $res = Db::query($sql,[$date['uid'],$date['uid']]);

        }elseif($date['params']['selectName'] == 'uname'){
            $sql.= ' WHERE t1.username=? ORDER BY '.$date['params']['sortName'].' ';
            $res = Db::query($sql,[$date['uid'],$date['uid'],$date['params']['num']]);

        }else{
            $sql.= ' WHERE t1.uid=? ORDER BY '.$date['params']['sortName'].' ';
            $res = Db::query($sql,[$date['uid'],$date['uid'],$date['params']['num']]);
        }
        return $res;
    }

    /**
     * 获取线下广告商今日消耗(日支出)
     */
    public function advReportNow($date)
    {
      $sql = 'SELECT (a.adv_id) as uid,SUM(a.sumadvpay) as sumadvpay FROM lz_stats_log AS a LEFT JOIN lz_users AS b ON a.adv_id=b.uid WHERE b.serviceid=? AND a.day=? GROUP BY a.adv_id ';
        $res = Db::query($sql,[$date['uid'],$date['day']]);
        //dump(db::getlastsql());exit;
        return $res;
    }

    /**
     * 获取线下广告商昨日日消耗(日支出)
     */
    public function advReportYes($date)
    {
       $sql = 'SELECT (a.adv_id) as uid,SUM(a.sumadvpay) as sumadvpay FROM lz_stats_new AS a LEFT JOIN lz_users AS b ON a.adv_id=b.uid WHERE b.serviceid=? AND a.day=? GROUP BY a.adv_id ';
        $res = Db::query($sql,[$date['uid'],$date['yesday']]);
        //dump(db::getlastsql());exit;
        return $res;
    }
    /**
     * 获取账户设置的基本信息
     */
    public function getBasic($uid)
    {
        $sql = 'SELECT uid,username,password,mobile,qq,email,tel,idcard FROM lz_users WHERE uid=? ';
        $res = Db::query($sql,[$uid]);

        return $res;
    }

    /**
     *  修改账户的基本信息
     */
    public  function accountEdit($uid,$data)
    {
        $map = array(
            'uid'=>$uid,
        );
        $res = Db::name('users')->where($map)->update($data);
        //数据同步users_log
        Db::name('users_log')->where($map)->update($data);
        return $res;
    }

    /**
     *  查询当前密码
     */
    public function getPwd($uid)
    {
        $map=array(
            'uid' =>$uid,
        );
        $res = Db::name('users')->where($map)->find();
        return $res;
    }

    /**
     *   修改密码
     */
    public function passEdit($uid,$data)
    {
        $map=array(
            'uid' =>$uid,
        );
        $res = Db::name('users')->where($map)->update($data);
        //数据同步users_log
        Db::name('users_log')->where($map)->update($data);
        return $res;
    }

    /**
     * 所有广告列表
     */
    public function planLstCount($data,$params)
    {
        if(!empty($params['typename'])){
            if($params['type'] == 'pid'){
                $where = ' where a.pid ="'.$params['typename'].'" ';
            }elseif($params['type'] == 'pname'){
                $where = ' where a.plan_name ="'.$params['typename'].'" ';
            }elseif($params['type'] == 'zid'){
                $where = ' where c.uid ="'.$params['typename'].'" ';
            }else{
                $where = ' where c.username ="'.$params['typename'].'" ';
            }
        }else{
            $where = 'where c.serviceid = '.$data['uid'].' ';
        }
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.budget,a.clearing,
        a.deduction,a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid '.$where.' ORDER BY a.pid DESC ';

        $res = Db::query($sql);
        return $res;
    }

    /**
     * 得到列表
     * param data
     */
    public function getLst($data,$offset,$count)
    {
        if(!empty($data['typename'])){
            if($data['type'] == 'pid'){
                $where = ' where a.pid ="'.$data['typename'].'" AND c.serviceid = '.$data['uid'].'';
            }elseif($data['type'] == 'pname'){
                $where = ' where a.plan_name ="'.$data['typename'].'" AND c.serviceid = '.$data['uid'].'';
            }elseif($data['type'] == 'zid'){
                $where = ' where c.uid ="'.$data['typename'].'" AND c.serviceid = '.$data['uid'].' ';
            }else{
                $where = ' where c.username ="'.$data['typename'].'" AND c.serviceid = '.$data['uid'].'  ';
            }
        }else{
            $where = 'where c.serviceid = '.$data['uid'].' ';
        }
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.budget,a.clearing,
        a.deduction,a.status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,c.serviceid,c.uid,
        c.username,b.class_name,b.type FROM lz_plan as a LEFT JOIN lz_classes as b
        ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid  '.$where.'  ORDER BY a.pid DESC LIMIT ?,? ';
        $res = Db::query($sql,[$offset,$count]);
        return $res;
    }

    /**
     * 更新状态
     * param pid 计划id status 状态
     */
    public function updateStatus($pid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'pid'=>$pid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 所有广告 个数
     */
    public function adLstCount($data,$params)
    {
        if(!empty($params['typename'])){
            if($params['type'] == 'adid'){
                $where = ' where a.ad_id ="'.$params['typename'].'" AND d.serviceid = '.$data['uid'].'';
            }elseif($params['type'] == 'pid'){
                $where = ' where a.pid ="'.$params['typename'].'" AND d.serviceid = '.$data['uid'].'';
            }elseif($params['type'] == 'zid'){
                $where = ' where d.uid ="'.$params['typename'].'" AND d.serviceid = '.$data['uid'].' ';
            }elseif($params['type'] == 'pname'){
                $where = ' where b.plan_name ="'.$params['typename'].'" AND d.serviceid = '.$data['uid'].' ';
            }else{
                $where = ' where d.username ="'.$params['typename'].'" AND d.serviceid = '.$data['uid'].'  ';
            }
        }else{
            $where = 'where d.serviceid="'.$data['uid'].'" ';
        }

        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,a.imageurl,b.plan_name,
        a.pid,b.plan_type,a.width,a.height,c.tplname,c.customspecs,d.serviceid FROM lz_ads as a LEFT JOIN lz_plan as b
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid '.$where.'  ';

        $res = Db::query($sql);
        return $res;
    }

    /**
     * 所有广告列表
     */
    public function adLst($data,$offset,$count)
    {
        if(!empty($data['typename'])){
            if($data['type'] == 'adid'){
                $where = ' where a.ad_id ="'.$data['typename'].'" AND d.serviceid = '.$data['uid'].'';
            }elseif($data['type'] == 'pid'){
                $where = ' where a.pid ="'.$data['typename'].'" AND d.serviceid = '.$data['uid'].'';
            }elseif($data['type'] == 'zid'){
                $where = ' where d.uid ="'.$data['typename'].'" AND d.serviceid = '.$data['uid'].' ';
            }elseif($data['type'] == 'pname'){
                $where = ' where b.plan_name ="'.$data['typename'].'" AND d.serviceid = '.$data['uid'].' ';
            }else{
                $where = ' where d.username ="'.$data['typename'].'" AND d.serviceid = '.$data['uid'].'  ';
            }
        }else{
            $where = 'where d.serviceid="'.$data['uid'].'" ';
        }
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,a.imageurl,b.plan_name,
        a.pid,b.plan_type,a.width,a.height,c.tplname,c.customspecs,d.serviceid FROM lz_ads as a LEFT JOIN lz_plan as b
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid '.$where.'  ';

            $sql.=' ORDER BY a.ad_id DESC  LIMIT ?,? ';
            $res = Db::query($sql,[$offset,$count]);

        return $res;
    }

    /**
     * 更新状态  广告
     * status 状态
     */
    public function upStatus($adid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'ad_id'=>$adid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }
}