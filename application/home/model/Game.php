<?php
namespace app\home\model;
use think\Model;
use think\Db;

class Game extends Model
{
    /**
     * 所有计划列表页数  游戏推广
     */
    public function gameplanLstCount($params)
    {
        $sql = 'SELECT count(a.pid) as count,a.uid,a.plan_name,a.plan_type,b.username FROM lz_plan  AS a LEFT JOIN lz_users AS b ON a.uid=b.uid LEFT JOIN lz_plan_associated as c ON a.pid=c.pid ';
        if(!empty($params['search'])){
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'b.'.$params['plan'];
            }
            $sele = $params['search'];
            $sql.=' WHERE '.$sort.' LIKE "%'.$sele.'%"  and a.type=1 AND c.uid=?';
            $res = Db::query($sql,[$params['game_uid']]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.checkplan LIKE "%'.$mobile.'%" and a.type=1 AND c.uid=?';
            }else{
                $sql.= 'WHERE a.type=1 AND c.uid=?';
            }
            $res = Db::query($sql,[$params['game_uid']]);
        }
        return $res['0']['count'];
    }

    /**
     * 得到列表
     * param data
     */
    public function gamegetLst($offset,$count,$params)
    {
        $sql = 'SELECT a.pid,a.uid,a.plan_name,a.plan_type,a.budget,a.clearing,a.status,a.ads_sel_status,a.checkplan,a.restrictions,a.sitelimit,a.mobile_price,a.priority,a.deduction,a.web_deduction,c.username,b.class_name,b.type,d.totalbudget,d.num FROM lz_plan as a LEFT JOIN lz_classes as b ON a.class_id=b.class_id LEFT JOIN lz_users as c ON a.uid=c.uid LEFT JOIN lz_game_totalbudget as d ON a.pid=d.pid LEFT JOIN lz_plan_associated as e ON a.pid=e.pid ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['plan'] !== 'username'){
                $sort = 'a.'.$params['plan'];
            }else{
                $sort = 'c.'.$params['plan'];
            }
            $sql.= 'WHERE  '.$sort.' LIKE "%'.$sele.'%" and a.type=1 AND e.uid=? ORDER BY a.pid DESC LIMIT ?,? ';
            $res = Db::query($sql,[$params['game_uid'],$offset,$count]);
        }else{
            if(!empty($params['mobile'])){
                $mobile = $params['mobile'];
                $sql.= 'WHERE a.checkplan LIKE "%'.$mobile.'%" and a.type=1 AND e.uid=? ORDER BY a.pid DESC LIMIT ?,? ';

            }else{
                $sql.= 'WHERE a.type=1 AND e.uid=? ORDER BY a.pid DESC LIMIT ?,? ';

            }

            $res = Db::query($sql,[$params['game_uid'],$offset,$count]);
        }
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
        $res = Db::name('plan')->where($map)->update($data);
        return $res;
    }

    /**
     * 更新广告状态
     * param pid 计划id status 状态
     */
    public function updateAdsStatus($pid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'pid'=>$pid,
        );
        $res = Db::name('ads')->where($map)->update($data);
        return $res;
    }

    /**
     * 查询复制计划的数据
     */
    public function planCopy($pid)
    {
        $sql = 'SELECT uid,plan_name,bigpname,run_terminal,run_type,run_model,gradation,price,price_1,price_2,price_3,price_4,price_5,pricedv,mobile_price,price_info,budget,plan_type,deduction,web_deduction,clearing,restrictions,resuid,sitelimit,limitsiteid,adzlimit,limitadzid,pkey,linkurl,cookie,checkplan,class_id,ads_sel_views,ads_sel_status,status,delay_show_status,ctime,type,priority FROM lz_plan WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        return $res[0];
    }

    /**
     * 更新游戏推广计划总限额表次数
     */
    public function gameUpdateNum($params)
    {
        $map = array(
            'pid' => $params['pid'],
        );
        $num = array(
            'num' => $params['num'],
        );
        $res = Db::name('game_totalbudget')->where($map)->update($num);
        if($res == false){
            $num = '';
        }
        return $num;

    }

    /**
     * 得到数据报表的数据个数   游戏推广
     * param data
     */
    public function gameLstCount($params,$game_uid)
    {
        /*if($params['id'] == 'class_name'){
            $id = 'c.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'c.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='plan_list')
        {
            $id = 'a.'.'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $mobile = empty($params['mobile']) ? '' : $params['mobile'];
        $sql = 'SELECT count(a.pid) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_plan as b ON a.pid=b.pid LEFT JOIN lz_site as d ON a.site_id=d.site_id
         LEFT JOIN lz_classes as c ON d.class_id=c.class_id LEFT JOIN lz_plan_associated as e ON e.uid = '.$game_uid.' ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    //默认
                    $sql = $this->gameplanLstCountNoparam($game_uid);
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);

            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND b.pid = e.pid GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);
            }
        }
        if(empty($res)){
            return 0;
        }else{
            return count($res);
        }
    }

    /**
     * 得到数据报表的数据个数,无参数
     * varsion 2.0
     */
    public function gameplanLstCountNoparam($game_uid)
    {
        $sql = 'SELECT b.pid from lz_stats_new as b LEFT JOIN lz_plan as c ON b.pid = c.pid LEFT JOIN lz_plan_associated as d ON d.uid = '.$game_uid.' WHERE b.day>=? AND b.day<=? AND c.type=1 AND b.pid = d.pid GROUP BY b.day,b.pid ';
        return $sql;
    }

    /**
     * 得到数据报表的所有数据(游戏推广)
     */
    public function gameplanTotal($params,$game_uid)
    {
        $sort = 'a.'.$params['sort'];

        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='plan_list')
        {
            $id = 'a.'.'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
                $id = '';
            }
        }
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,
a.web_deduction,a.adv_deduction,a.sumprofit,a.sumpay,a.sumadvpay,a.day,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,
a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,
a.web_click_num,a.adz_click_num,e.gradation,e.price,e.price_1,a.adv_id,e.price_2,e.price_3,e.price_4,e.price_5,
e.pricedv,b.plan_name,b.checkplan,b.run_terminal,c.star,d.cpd,d.cpd_day,f.class_name FROM lz_stats_new as a LEFT JOIN
lz_plan as b ON a.pid=b.pid  LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id LEFT JOIN lz_adzone_copy as d
ON a.day=d.cpd_day AND a.adz_id=d.adz_id LEFT JOIN lz_plan_price as e ON a.tc_id=e.id LEFT JOIN lz_site as g
ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON g.class_id=f.class_id LEFT JOIN lz_plan_associated as h ON h.uid = '.$game_uid.' ';
        $res = $this->_gamegetResForPlan($params,$sql,$sort,$id,$game_uid);
        return $res;
    }

    /**
     * 游戏推广计划  默认数据
     **/
    public function _gamegetResForPlan($params,$sql,$sort,$id,$game_uid)
    {
        if($sort == 'a.'){
            $sort = $sort.'day';
        }else{
            $sort = $sort;
        }
        $mobile = empty($params['mobile']) ? '':$params['mobile'];
        if(empty($params['numid']))
        {
            if(empty($params['type']))
            {
                if(empty($params['mobile']))
                {
                    $sql = $this->gameplanlstRes($game_uid);
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);

            }
            else
            {
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);

            }
        }
        else
        {
            if(empty($params['type'])){
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);

            }else{
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 AND h.pid = a.pid ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);
            }
        }
        return $res;
    }

    /**
     * 游戏推广计划 数据
     **/
    public function gameplanlstRes($game_uid)
    {
        $sql = 'SELECT t1.*,a.plan_name,a.run_terminal,a.plan_type from (SELECT day,pid,adz_id,SUM(views) as views,
SUM(click_num) as click_num,SUM(adv_deduction) as adv_deduction,SUM(adv_num) as adv_num,SUM(sumadvpay) as sumadvpay,
SUM(sumprofit) as sumprofit,SUM(download) as download,SUM(sumpay) as sumpay,heavy_click_num from lz_stats_new WHERE day>=?
 AND day<=? GROUP BY day,pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid LEFT JOIN lz_plan_associated as b
 ON b.uid = '.$game_uid.' WHERE a.type=1 AND b.pid = a.pid ';
        return $sql;
    }
}
