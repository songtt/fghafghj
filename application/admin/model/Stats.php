<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class Stats extends \think\Model
{


    /***************************************************计划报表*****************************************************/
    /**
     * 得到数据报表的所有数据
     */
    public function planTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'a.'.'pid';
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,a.day,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,e.gradation,e.price,e.price_1,a.adv_id,
            e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv,b.plan_name,b.checkplan,b.run_terminal,c.star,d.cpd,d.cpd_day,f.class_name FROM lz_stats_new as a
            LEFT JOIN lz_plan as b ON a.pid=b.pid  LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id LEFT JOIN
            lz_adzone_copy as d ON a.day=d.cpd_day AND a.adz_id=d.adz_id LEFT JOIN lz_plan_price as e ON a.tc_id=e.id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON g.class_id=f.class_id';
        $res = $this->_getResForPlan($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 计划报表今日数据  查询lz_stats_log
     */
    public function planToStatsLog($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'pid';
        }else{
            $id = $params['id'];
        }
        if(!empty($params['mobile'])){
            switch ($params['mobile']) {
                case 'ios':
                    $mobile = 2;
                    break;
                case 'android':
                    $mobile = 3;
                    break;
                case 'wp':
                    $mobile = 4;
                    break;
            }
        }else{
            $mobile = 0;
        }
        //设备类型run_terminal   0不限 1pc 2ios 3android 4wp
        $sql = 'SELECT t1.day,t1.pid,t1.ad_id,t1.uid,t1.adv_id,t1.adz_id,t1.site_id,t1.views,t1.click_num,t1.adv_deduction,t1.adv_num,t1.sumadvpay,t1.sumprofit,t1.download,t1.sumpay,t1.heavy_click_num,t1.plan_type,a.plan_name,a.run_terminal,a.plan_type FROM (SELECT day,pid,ad_id,uid,adv_id,adz_id,site_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(adv_deduction) AS adv_deduction,SUM(adv_num) AS adv_num,SUM(sumadvpay) AS sumadvpay,SUM(sumprofit) AS sumprofit,SUM(download) AS download,SUM(sumpay) AS sumpay,MAX(heavy_click_num) AS heavy_click_num,plan_type FROM lz_stats_log';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($mobile)){
                    $sql.= ' WHERE day=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid ORDER BY t1.views DESC';
                }else{
                    $sql.= ' WHERE day=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' ORDER BY t1.views DESC';
                }
                $res = Db::query($sql,[$params['day']]);
            }else{
                if(empty($mobile)){
                    $sql.= ' WHERE day=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid  ORDER BY t1.views DESC';
                }else{
                    $sql.= ' WHERE day=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' ORDER BY t1.views DESC';
                }
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                if(empty($mobile)){
                    $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid ORDER BY t1.views DESC';
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' ORDER BY t1.views DESC';
                }
                $res = Db::query($sql,[$params['day'],$params['numid']]);
            }else{
                if(empty($mobile)){
                    $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid ORDER BY t1.views DESC';
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' ORDER BY t1.views DESC';
                }
                $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }

    /**
     * 计划报表今日数据  查询lz_stats_log
     */
    public function gameplanToStatsLog($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'pid';
        }else{
            $id = $params['id'];
        }
        if(!empty($params['mobile'])){
            switch ($params['mobile']) {
                case 'ios':
                    $mobile = 2;
                    break;
                case 'android':
                    $mobile = 3;
                    break;
                case 'wp':
                    $mobile = 4;
                    break;
            }
        }else{
            $mobile = 0;
        }
        //设备类型run_terminal   0不限 1pc 2ios 3android 4wp
        $sql = 'SELECT t1.day,t1.pid,t1.ad_id,t1.uid,t1.adv_id,t1.adz_id,t1.site_id,t1.views,t1.click_num,t1.adv_deduction,t1.adv_num,t1.sumadvpay,t1.sumprofit,t1.download,t1.sumpay,t1.heavy_click_num,t1.plan_type,a.plan_name,a.run_terminal,a.plan_type FROM (SELECT day,pid,ad_id,uid,adv_id,adz_id,site_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(adv_deduction) AS adv_deduction,SUM(adv_num) AS adv_num,SUM(sumadvpay) AS sumadvpay,SUM(sumprofit) AS sumprofit,SUM(download) AS download,SUM(sumpay) AS sumpay,MAX(heavy_click_num) AS heavy_click_num,plan_type FROM lz_stats_log';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($mobile)){
                    $sql.= ' WHERE day=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.type=1';
                }else{
                    $sql.= ' WHERE day=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' AND a.type=1';
                }
                $res = Db::query($sql,[$params['day']]);
            }else{
                if(empty($mobile)){
                    $sql.= ' WHERE day=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.type=1';
                }else{
                    $sql.= ' WHERE day=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' AND a.type=1';
                }
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                if(empty($mobile)){
                    $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.type=1';
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' AND a.type=1';
                }
                $res = Db::query($sql,[$params['day'],$params['numid']]);
            }else{
                if(empty($mobile)){
                    $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.type=1';
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY pid ) as t1 LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.run_terminal='.$mobile.' AND a.type=1';
                }
                $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }
    /**
     * 得到数据报表的所有数据(游戏推广)
     */
    public function gameplanTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,a.day,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,e.gradation,e.price,e.price_1,a.adv_id,
            e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv,b.plan_name,b.checkplan,b.run_terminal,c.star,d.cpd,d.cpd_day,f.class_name FROM lz_stats_new as a LEFT JOIN lz_plan as b ON a.pid=b.pid  LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id LEFT JOIN
            lz_adzone_copy as d ON a.day=d.cpd_day AND a.adz_id=d.adz_id LEFT JOIN lz_plan_price as e ON a.tc_id=e.id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON g.class_id=f.class_id';
        $res = $this->_gamegetResForPlan($params,$sql,$sort,$id);
        return $res;
    }

    public function planlstRes()
    {
        $sql = 'SELECT t1.*,a.plan_name,a.run_terminal,a.plan_type from (SELECT day,pid,adz_id,adv_id,SUM(views) as views,SUM(click_num) as click_num,SUM(adv_deduction) as adv_deduction,SUM(adv_num) as adv_num,SUM(sumadvpay) as sumadvpay,SUM(sumprofit) as sumprofit,SUM(download) as download,SUM(sumpay
        ) as sumpay,MAX(heavy_click_num) AS  heavy_click_num from lz_stats_new WHERE day>=? AND day<=? GROUP BY day,pid ) as t1
        LEFT JOIN lz_plan as a ON t1.pid=a.pid ORDER BY t1.day,t1.views DESC';
        return $sql;
    }

    /**
     * 游戏推广计划 数据
     **/
    public function gameplanlstRes()
    {
        $sql = 'SELECT t1.*,a.plan_name,a.run_terminal,a.plan_type from (SELECT day,pid,adz_id,SUM(views) as views,SUM(click_num) as click_num,SUM(adv_deduction) as adv_deduction,SUM(adv_num) as adv_num,SUM(sumadvpay) as sumadvpay,SUM(sumprofit) as sumprofit,SUM(download) as download,SUM(sumpay
        ) as sumpay,MAX(heavy_click_num) AS  heavy_click_num from lz_stats_new
        WHERE day>=? AND day<=? GROUP BY day,pid ) as t1
        LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.type=1';
        return $sql;
    }

    /**
     * 得到数据报表的数据个数,无参数
     * varsion 2.0
     */
    public function planLstCountNoparam()
    {
        $sql = 'SELECT b.pid from lz_stats_new as b WHERE b.day>=? AND b.day<=? GROUP BY b.day,b.pid ';
        return $sql;
    }

    /**
     * 得到数据报表的数据个数,无参数
     * varsion 2.0
     */
    public function gameplanLstCountNoparam()
    {
        $sql = 'SELECT b.pid from lz_stats_new as b LEFT JOIN lz_plan as c ON b.pid = c.pid WHERE b.day>=? AND b.day<=? AND c.type=1  GROUP BY b.day,b.pid ';
        return $sql;
    }

    /**
     * 得到数据报表的数据个数   游戏推广
     * param data
     */
    public function gameplanLstCount($params)
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
        $sql = 'SELECT count(a.pid) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name FROM lz_stats_new as a LEFT JOIN
            lz_plan as b ON a.pid=b.pid LEFT JOIN lz_site as d ON a.site_id=d.site_id LEFT JOIN lz_classes as c ON
            d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    //默认
                    // $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.pid';
                    $sql = $this->gameplanLstCountNoparam();
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? AND b.type=1 GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 GROUP BY a.day,a.pid';
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
     * 得到数据报表的数据个数
     * param data
     */
    public function planLstCount($params)
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
        $sql = 'SELECT count(a.pid) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name FROM lz_stats_new as a LEFT JOIN
            lz_plan as b ON a.pid=b.pid LEFT JOIN lz_site as d ON a.site_id=d.site_id LEFT JOIN lz_classes as c ON
            d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    //默认
                    // $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.pid';
                    $sql = $this->planLstCountNoparam();
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
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
     * 得到数据报表的所有数据(所有时间的情况下)
     */
    public function planAllTimeTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,a.day,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,e.gradation,e.price,e.price_1,a.adv_id,
            e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv,b.plan_name,b.checkplan,b.run_terminal,c.star,d.cpd,d.cpd_day,f.class_name FROM lz_stats_new as a
            LEFT JOIN lz_plan as b ON a.pid=b.pid  LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id LEFT JOIN
            lz_adzone_copy as d ON a.day=d.cpd_day AND a.adz_id=d.adz_id LEFT JOIN lz_plan_price as e ON a.tc_id=e.id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON g.class_id=f.class_id';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function planAllTimeCount($params)
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
        $sql = 'SELECT count(a.pid) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name FROM lz_stats_new as a LEFT JOIN
            lz_plan as b ON a.pid=b.pid LEFT JOIN lz_site as d ON a.site_id=d.site_id LEFT JOIN lz_classes as c ON
            d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    $sql .= ' GROUP BY a.day,a.pid';
                }else{
                    $sql .= 'WHERE b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE a.plan_type=? GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['numid']]);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,a.pid';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND b.checkplan LIKE "%'.$mobile.'%" GROUP BY a.day,a.pid';
                }
                $res = Db::query($sql,[$params['numid'],$params['type']]);

            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }
    /***************************************************站长报表*****************************************************/

    /**
     * 得到站长报表的所有数据(所有时间的情况下)
     */
    public function webAllTimeTotal($params)
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,
            a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,
            a.uv_adzone,a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.username,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN lz_users as b ON a.uid=b.uid LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id
            LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_site as g ON
            a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }



    /**
     * 得到站长报表的所有数据
     */
    public function webTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        if($params['id'] == 'class_name')
        {
            $id = 'a.'.'uid';
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
        $res = $this->_getResForWeb($params,$sort,$id);
        return $res;
    }


    public function weblstRes()
    {
        $sql = 'SELECT t1.*,a.username,b.cpd,b.cpd_day from
                (SELECT day,pid,uid,adz_id,
                adv_num,download,
                SUM(views) as views,
                SUM(click_num) as click_num,
                SUM(uv_web) as uv_web,
                SUM(ui_web) as ui_web,
                SUM(web_deduction) as web_deduction,
                SUM(web_num) as web_num,
                SUM(sumpay) as sumpay,
				MAX(web_click_num) as web_click_num,
                SUM(sumprofit) as sumprofit from lz_stats_new WHERE day>=? AND day<=? GROUP BY day,adz_id) as t1
                LEFT JOIN lz_users as a ON t1.uid=a.uid
                LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day';

        /*$sql = 'SELECT t1.*,a.username,b.cpd,b.cpd_day from
                (SELECT day,pid,uid,adz_id,adv_num,
                download,web_click_num,views,
                click_num,uv_web,ui_web,web_deduction,
                web_num,sumpay,sumprofit from lz_stats_new WHERE day>=? AND day<=? GROUP BY day,adz_id) as t1
                LEFT JOIN lz_users as a ON t1.uid=a.uid
                LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day';*/
        return $sql;
    }


    /**
     * 得到数据报表的数据个数
     * param data
     */
    public function webLstCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='user_list')
        {
            $id = 'a.'.'uid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(a.uid) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name FROM lz_stats_new as a LEFT JOIN
            lz_users as b ON a.uid=b.uid LEFT JOIN lz_site as d ON a.site_id=d.site_id LEFT JOIN lz_classes as c ON
            d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.uid';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.uid';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,a.uid';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.uid';
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
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function webAllTimeCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='user_list')
        {
            $id = 'a.'.'uid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(a.uid) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name FROM lz_stats_new as a LEFT JOIN
            lz_users as b ON a.uid=b.uid LEFT JOIN lz_site as d ON a.site_id=d.site_id LEFT JOIN lz_classes as c ON
            d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql .= ' GROUP BY a.day,a.uid';
                $res = Db::query($sql);
            }else{
                $sql.= ' WHERE a.plan_type=? GROUP BY a.day,a.uid';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? GROUP BY a.day,a.uid';
                $res = Db::query($sql,[$params['numid']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,a.uid';
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }

    //站长今日的数据
    public function webToStatsLog($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'uid';
        }else{
            $id = $params['id'];
        }
        $sql = 'SELECT t1.day,t1.pid,t1.ad_id,t1.uid,t1.adv_id,t1.adz_id,t1.site_id,t1.views,t1.click_num,t1.web_deduction,t1.web_num,t1.sumadvpay,t1.sumprofit,t1.sumpay,t1.web_click_num,t1.plan_type,t1.uv_web,t1.ui_web,a.username,b.cpd,b.cpd_day FROM (SELECT day,pid,ad_id,uid,adv_id,adz_id,site_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(web_deduction) AS web_deduction,SUM(web_num) AS web_num,SUM(sumadvpay) AS sumadvpay,SUM(sumprofit) AS sumprofit,SUM(sumpay) AS sumpay,MAX(web_click_num) AS web_click_num,plan_type,SUM(uv_web) as uv_web,SUM(ui_web) as ui_web FROM lz_stats_log ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day=? GROUP BY adz_id) as t1  LEFT JOIN lz_users as a ON t1.uid=a.uid LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day']]);
            }else{
                $sql.= ' WHERE day=? AND plan_type=? GROUP BY adz_id) as t1  LEFT JOIN lz_users as a ON t1.uid=a.uid LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY adz_id) as t1  LEFT JOIN lz_users as a ON t1.uid=a.uid LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['numid']]);
            }else{
                $sql.= 'WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY adz_id) as t1  LEFT JOIN lz_users as a ON t1.uid=a.uid LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }

    /**
     * 站长报表跳转数
     */
    public function webJump($day,$params,$table)
    {
        $sql = 'SELECT a.pid,a.uid,SUM(a.click_num) AS jump,a.day,b.type FROM '.$table.' AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid ';
        if(empty($params['numid'])){
            $sql.= ' WHERE b.type=3 AND a.day>=? AND a.day<=? GROUP BY a.pid,a.uid,a.day';
            $res = Db::query($sql,[$day,$params['day1']]);
        }else{
            $sql.= ' WHERE b.type=3 AND a.day>=? AND a.day<=? AND a.'.$params['id'].'=? GROUP BY a.pid,a.uid,a.day';
            $res = Db::query($sql,[$day,$params['day1'],$params['numid']]);
        }
        return $res;
    }
    /***************************************************广告报表*****************************************************/
    /**
     * 得到数据报表的所有数据
     */
    public function adsTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        $sort = $params['sort'] == 'click_cost' ? 'a.day':$sort;
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='ads_list')
        {
            $id = 'a.'.'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                if(strstr($params['numid'],'*')){
                    $size = explode('*',$params['numid']);
                }elseif(strstr($params['numid'],'x')){
                    $size = explode('x',$params['numid']);
                }
                $width = !empty($size[0]) ? $size[0] : 1;
                $height = !empty($size[1]) ? $size[1] : 1;
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,
            a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.day,b.adname,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN                lz_ads as b ON a.ad_id=b.ad_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id
            LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getResForAds($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 广告报表所有搜索条件为空查询语句
     */
    private function adsLstRes()
    {
        $sql = 'SELECT t1.*,a.adname FROM (SELECT day,ad_id,adv_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(download) AS download,SUM(adv_deduction) AS adv_deduction,SUM(adv_num) AS adv_num,SUM(sumprofit) AS sumprofit,SUM(sumadvpay) AS sumadvpay FROM lz_stats_new WHERE day>=? AND day<=? GROUP BY ad_id,day) AS t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id GROUP BY t1.day,t1.ad_id ORDER BY t1.views DESC';
        return $sql;
    }

    /**
     * 广告报表查询条件
     */
    private function _getResForAds($params,$sql,$sort,$id)
    {
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' ||
            $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = '';
            $sort = 'a.'.$params['sort'];
        }
        if($sort == 'a.'){

            $sort = $sort.'day';
        }else{
            $sort = $sort;
        }
        if(empty($params['numid']))
        {
            if(empty($params['type']))
            {
                $sql = $this->adsLstRes();
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }
            else
            {
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }
        else
        {
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);
            }
        }
        return $res;
    }


    /**
     * 得到数据报表的数据个数
     * param data
     */
    public function adsLstCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='ads_list')
        {
            $id = 'a.'.'ad_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                if(strstr($params['numid'],'*')){
                    $size = explode('*',$params['numid']);
                }elseif(strstr($params['numid'],'x')){
                    $size = explode('x',$params['numid']);
                }else{
                    $size = '';
                }
                $width = !empty($size[0]) ? $size[0] : 1;
                $height = !empty($size[1]) ? $size[1] : 1;
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }

        }
        $sql = 'SELECT count(a.ad_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,b.width,b.height,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_ads as b ON a.ad_id=b.ad_id LEFT JOIN lz_site as d ON a.site_id=d.site_id
        LEFT JOIN lz_classes as c ON d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.ad_id';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.ad_id';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,a.ad_id';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.ad_id';
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
     * 得到数据报表的所有数据(所有时间的情况下)
     */
    public function adsAllTimeTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='ads_list')
        {
            $id = 'a.'.'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                if(strstr($params['numid'],'*')){
                    $size = explode('*',$params['numid']);
                }elseif(strstr($params['numid'],'x')){
                    $size = explode('x',$params['numid']);
                }
                $width = !empty($size[0]) ? $size[0] : 1;
                $height = !empty($size[1]) ? $size[1] : 1;
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,
            a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.day,b.adname,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN                lz_ads as b ON a.ad_id=b.ad_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id
            LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function adsAllTimeCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='ads_list')
        {
            $id = 'a.'.'ad_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                if(strstr($params['numid'],'*')){
                    $size = explode('*',$params['numid']);
                }elseif(strstr($params['numid'],'x')){
                    $size = explode('x',$params['numid']);
                }
                $width = !empty($size[0]) ? $size[0] : 1;
                $height = !empty($size[1]) ? $size[1] : 1;
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT count(a.ad_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_ads as b ON a.ad_id=b.ad_id LEFT JOIN lz_site as d ON a.site_id=d.site_id
        LEFT JOIN lz_classes as c ON d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql .= ' GROUP BY a.day,a.ad_id';
                $res = Db::query($sql);
            }else{
                $sql.= ' WHERE a.plan_type=? GROUP BY a.day,a.ad_id';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? GROUP BY a.day,a.ad_id';
                $res = Db::query($sql,[$params['numid']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,a.ad_id';
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }

    /**
     * 广告报表今日数据  查询lz_stats_log
     */
    public function adsToStatsLog($params)
    {
        if($params['id'] != 'size'){
            $id = $params['id'];
            if($params['id'] == 'class_name'){
                $id = 'ad_id';
            }
        }else{
            //尺寸搜索
            if(strstr($params['numid'],'*')){
                $size = explode('*',$params['numid']);
            }elseif(strstr($params['numid'],'x')){
                $size = explode('x',$params['numid']);
            }
            $width = !empty($size[0]) ? $size[0] : 1;
            $height = !empty($size[1]) ? $size[1] : 1;
            $id = 'a.width = '.$width.' AND a.height ';
            $params['numid'] = $height;
        }
        $sql = 'SELECT t1.day,t1.pid,t1.ad_id,t1.uid,t1.adv_id,t1.adz_id,t1.site_id,t1.views,t1.click_num,t1.adv_deduction,t1.adv_num,t1.sumadvpay,t1.sumprofit,t1.download,t1.sumpay,t1.plan_type,a.adname,a.width,a.height FROM (SELECT day,pid,ad_id,uid,adv_id,adz_id,site_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(adv_deduction) AS adv_deduction,SUM(adv_num) AS adv_num,SUM(sumadvpay) AS sumadvpay,SUM(sumprofit) AS sumprofit,SUM(download) AS download,SUM(sumpay) AS sumpay,plan_type FROM lz_stats_log ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day=? GROUP BY ad_id ) as t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day']]);
            }else{
                $sql.= ' WHERE day=? AND plan_type=? GROUP BY ad_id ) as t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            //广告报表下搜索尺寸
            if($params['id'] != 'size'){
                if(empty($params['type'])){
                    $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY ad_id ) as t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['numid']]);
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY ad_id ) as t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
                }
            }else{
                if(empty($params['type'])){
                    $sql.= ' WHERE day=? GROUP BY ad_id ) as t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id WHERE '.$id.'=? ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['numid']]);
                }else{
                    $sql.= ' WHERE day=? AND plan_type=? GROUP BY ad_id ) as t1 LEFT JOIN  lz_ads as a ON t1.ad_id=a.ad_id WHERE '.$id.'=? ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['type'],$params['numid']]);
                }
            }
        }
        return $res;
    }

    /***************************************************广告位报表***************************************************/
    /**
     * 得到数据报表的所有数据
     */
    public function zoneTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='zone_list')
        {
            $id = 'a.'.'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $size = explode('*',$params['numid']);
                $width = !empty($size[0]) ? $size[0] : '';
                $height = !empty($size[1]) ? $size[1] : '';
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,b.width,b.height,
            a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.day,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.zonename,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a
            LEFT JOIN lz_adzone as b ON a.adz_id=b.adz_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id
            LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getRes($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数
     * param data
     */
    public function zoneLstCount($params)
    {
        /*if($params['id'] == 'class_name'){
            $id = 'c.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name'){
            $id = 'c.'.$params['id'];
        }elseif(empty($params['id'])){
            $id = 'a.'.'adz_id';
        }else{
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $size = explode('*',$params['numid']);
                $width = !empty($size[0]) ? $size[0] : '';
                $height = !empty($size[1]) ? $size[1] : '';
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT count(a.adz_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_adzone as b ON a.adz_id=b.adz_id LEFT JOIN lz_site as d ON a.site_id=d.site_id
        LEFT JOIN lz_classes as c ON d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.adz_id';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.adz_id';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,a.adz_id';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.adz_id';
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
     * 得到数据报表的所有数据
     */
    public function zoneNTotal($params)
    {
        if($params['id'] == 'class_name')
        {
            $id = 'e.'.$params['id'];
        }elseif(empty($params['id'])){
            $id = 'pid';
        }else{
            if($params['id'] != 'size'){
                $id = $params['id'];
            }else{
                //尺寸搜索
                $size = explode('*',$params['numid']);
                $width = !empty($size[0]) ? $size[0] : '';
                $height = !empty($size[1]) ? $size[1] : '';
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $stats = 'lz_stats_new';
        $today = date('Y-m-d').date('Y-m-d');
        if($params['time'] == $today){
            $params['day'] = date('Y-m-d');
            $stats = 'lz_stats_log';
        }
        $sql = 'SELECT t1.*,b.width,b.height,d.cpd,d.cpd_day,b.class_id,e.class_name
            FROM (SELECT SUM(views) AS views,SUM(num) AS num,SUM(web_num) AS web_num,SUM(click_num) AS click_num,SUM(web_deduction) AS web_deduction,SUM(sumprofit) AS sumprofit,
            SUM(sumpay) AS sumpay,sum(sumadvpay) AS sumadvpay,SUM(ui_web) AS ui_web,pid,uid,ad_id,adz_id,adv_id,site_id,day,MAX(ui_adzone) AS ui_adzone,plan_type,
            MAX(uv_adzone) AS uv_adzone,MAX(adz_click_num) AS adz_click_num FROM '.$stats.' ';
        $res = $this->_getResForAdz($params,$sql,$id);
        return $res;
    }

    /**
     * 处理广告位报表搜索条件
     */
    private function _getResForAdz($params,$sql,$id)
    {
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone as b ON t1.adz_id=b.adz_id
                        LEFT JOIN lz_adzone_copy as d ON t1.day=d.cpd_day AND t1.adz_id=d.adz_id
                        LEFT JOIN lz_classes as e ON b.class_id=e.class_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone as b ON t1.adz_id=b.adz_id
                        LEFT JOIN lz_adzone_copy as d ON t1.day=d.cpd_day AND t1.adz_id=d.adz_id
                        LEFT JOIN lz_classes as e ON b.class_id=e.class_id WHERE t1.plan_type=? ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['day1'],$params['type']]);
            }
        }else{
            //广告位报表搜索尺寸情况下 处理搜索条件
            if($params['id'] != 'size' && $params['id'] != 'class_name'){
                if(empty($params['type'])){
                    $sql.= ' WHERE day>=? AND day<=? AND '.$id.'=? GROUP BY day,adz_id) AS t1
                            LEFT JOIN lz_adzone as b ON t1.adz_id=b.adz_id
                            LEFT JOIN lz_adzone_copy as d ON t1.day=d.cpd_day AND t1.adz_id=d.adz_id
                            LEFT JOIN lz_classes as e ON b.class_id=e.class_id ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['day1'],$params['numid']]);
                }else{
                    $sql.= ' WHERE day>=? AND day<=? AND '.$id.'=? GROUP BY day,adz_id) AS t1
                            LEFT JOIN lz_adzone as b ON t1.adz_id=b.adz_id
                            LEFT JOIN lz_adzone_copy as d ON t1.day=d.cpd_day AND t1.adz_id=d.adz_id
                            LEFT JOIN lz_classes as e ON b.class_id=e.class_id WHERE t1.plan_type=? ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['day1'],$params['numid'],$params['type']]);
                }
            }else{
                if(empty($params['type'])){
                    $sql.= ' WHERE day>=? AND day<=?  GROUP BY day,adz_id) AS t1
                            LEFT JOIN lz_adzone as b ON t1.adz_id=b.adz_id
                            LEFT JOIN lz_adzone_copy as d ON t1.day=d.cpd_day AND t1.adz_id=d.adz_id
                            LEFT JOIN lz_classes as e ON b.class_id=e.class_id WHERE '.$id.'=? ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['day1'],$params['numid']]);
                }else{
                    $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                            LEFT JOIN lz_adzone as b ON t1.adz_id=b.adz_id
                            LEFT JOIN lz_adzone_copy as d ON t1.day=d.cpd_day AND t1.adz_id=d.adz_id
                            LEFT JOIN lz_classes as e ON b.class_id=e.class_id
                            WHERE t1.plan_type=? AND '.$id.'=? ORDER BY t1.views DESC';
                    $res = Db::query($sql,[$params['day'],$params['day1'],$params['type'],$params['numid']]);
                }
            }

        }
        return $res;
    }

    /**
     * 得到数据报表的所有数据(所有时间的情况下)
     */
    public function zoneAllTimeTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='zone_list')
        {
            $id = 'a.'.'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $size = explode('*',$params['numid']);
                $width = !empty($size[0]) ? $size[0] : '';
                $height = !empty($size[1]) ? $size[1] : '';
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,b.width,b.height,
            a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.day,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,
            a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.zonename,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a
            LEFT JOIN lz_adzone as b ON a.adz_id=b.adz_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id
            LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id
            LEFT JOIN lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 广告位域名的访问 top 10
     **/
    public function zoneDomain($parame)
    {
        $sql = 'SELECT * FROM lz_domain_stats WHERE adz_id = ? AND day = ? ORDER BY num DESC Limit 10';
        $res = DB::query($sql,[$parame['adz_id'],$parame['day']]);
        return $res;

    }

    /**
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function zoneAllTimeCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='zone_list')
        {
            $id = 'a.'.'adz_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $size = explode('*',$params['numid']);
                $width = !empty($size[0]) ? $size[0] : '';
                $height = !empty($size[1]) ? $size[1] : '';
                $id = 'b.width = '.$width.' AND b.height ';
                $params['numid'] = $height;
            }
        }
        $sql = 'SELECT count(a.adz_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_adzone as b ON a.adz_id=b.adz_id LEFT JOIN lz_site as d ON a.site_id=d.site_id
        LEFT JOIN lz_classes as c ON d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql .= ' GROUP BY a.day,a.adz_id';
                $res = Db::query($sql);
            }else{
                $sql.= ' WHERE a.plan_type=? GROUP BY a.day,a.adz_id';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? GROUP BY a.day,a.adz_id';
                $res = Db::query($sql,[$params['numid']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,a.adz_id';
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }

    /**
     *  广告位跳转数
     */
    public function adzJump($day,$params,$table)
    {
        $sql = 'SELECT a.pid,a.adz_id,SUM(a.click_num) AS jump,a.day,b.type FROM '.$table.' AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid ';
        if(empty($params['numid'])){
            $sql.= ' WHERE b.type=3 AND a.day>=? AND a.day<=? GROUP BY a.pid,a.adz_id,a.day';
            $res = Db::query($sql,[$day,$params['day1']]);
        }else{
            $sql.= ' WHERE b.type=3 AND a.day>=? AND a.day<=? AND a.'.$params['id'].'=? GROUP BY a.pid,a.adz_id,a.day';
            $res = Db::query($sql,[$day,$params['day1'],$params['numid']]);
        }
        return $res;
    }

    /***************************************************广告商报表***************************************************/
    /**
     * 得到数据报表的所有数据
     */
    public function advTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='adv_list')
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,
            a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,
            a.uv_ads,a.uv_adzone,a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.username,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN
            lz_users as b ON a.adv_id=b.uid LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d
            ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN
            lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getResForAdv($params,$sql,$sort,$id);
        return $res;
    }
    /**
     * 广告商默认数据
     **/
    public function advlstRes()
    {
        $sql = 'SELECT t1.*,a.username from (SELECT day,
        SUM(views) as views,
        SUM(adv_num) as adv_num,
        SUM(sumadvpay) as sumadvpay,
        SUM(sumprofit) as sumprofit,
        SUM(download) as download,
        SUM(click_num) as click_num,
        SUM(adv_deduction) as adv_deduction,
        adv_id
        from lz_stats_new WHERE day>=? AND day<=? GROUP BY adv_id,day) as t1
        LEFT JOIN lz_users as a ON t1.adv_id=a.uid GROUP BY t1.adv_id,t1.day ORDER BY t1.views DESC';
        return $sql;
    }

    /**
     * 广告商查询单条数据
     **/
    public function advlstResForid($params)
    {
        if($params['id'] == 'class_name')
        {
            $id = 'adv_id';
        }
        elseif(empty($params['id']) && $params['stats']=='adv_list')
        {
            $id = 'pid';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = $params['id'];
            }else{
                $params['numid'] = '';
                $id = '';
            }
        }
        $sql = 'SELECT t1.*,a.username FROM (SELECT day,pid,ad_id,uid,adv_id,adz_id,site_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(adv_deduction) AS adv_deduction,SUM(adv_num) AS adv_num,SUM(sumadvpay) AS sumadvpay,SUM(sumprofit) AS sumprofit,SUM(download) AS download,SUM(sumpay) AS sumpay FROM lz_stats_new WHERE '.$id.'=? AND day>=? AND day<=? GROUP BY adv_id,day) as t1 LEFT JOIN lz_users as a ON t1.adv_id=a.uid ';
        return $sql;
    }

    /**
     * 得到数据报表的数据个数
     * param data
     */
    public function advLstCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='adv_list')
        {
            $id = 'a.'.'adv_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(a.adv_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_users as b ON a.adv_id=b.uid LEFT JOIN lz_site as d ON a.site_id=d.site_id
        LEFT JOIN lz_classes as c ON d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.adv_id';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.adv_id';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,a.adv_id';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.adv_id';
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
     * 得到数据报表的所有数据(所有时间的情况下)
     */
    public function advAllTimeTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='adv_list')
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,
            a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,
            a.uv_ads,a.uv_adzone,a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.username,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN
            lz_users as b ON a.adv_id=b.uid LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d
            ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN
            lz_site as g ON a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function advAllTimeCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='adv_list')
        {
            $id = 'a.'.'adv_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(a.adv_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_users as b ON a.adv_id=b.uid LEFT JOIN lz_site as d ON a.site_id=d.site_id
        LEFT JOIN lz_classes as c ON d.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql .= ' GROUP BY a.day,a.adv_id';
                $res = Db::query($sql);
            }else{
                $sql.= ' WHERE a.plan_type=? GROUP BY a.day,a.adv_id';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? GROUP BY a.day,a.adv_id';
                $res = Db::query($sql,[$params['numid']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,a.adv_id';
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }

    /**
     * 广告商报表今日数据  查询lz_stats_log
     */
    public function advToStatsLog($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'adv_id';
        }else{
            $id = $params['id'];
        }
        $sql = 'SELECT t1.day,t1.pid,t1.ad_id,t1.uid,t1.adv_id,t1.adz_id,t1.site_id,t1.views,t1.click_num,t1.adv_deduction,t1.adv_num,t1.sumadvpay,t1.sumprofit,t1.download,t1.sumpay,t1.plan_type,a.username FROM (SELECT day,pid,ad_id,uid,adv_id,adz_id,site_id,SUM(views) AS views,SUM(click_num) AS click_num,SUM(adv_deduction) AS adv_deduction,SUM(adv_num) AS adv_num,SUM(sumadvpay) AS sumadvpay,SUM(sumprofit) AS sumprofit,SUM(download) AS download,SUM(sumpay) AS sumpay,plan_type FROM lz_stats_log ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day=? GROUP BY adv_id) as t1 LEFT JOIN lz_users as a ON t1.adv_id=a.uid ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day']]);
            }else{
                $sql.= ' WHERE day=? AND plan_type=? GROUP BY adv_id) as t1 LEFT JOIN lz_users as a ON t1.adv_id=a.uid ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY adv_id) as t1 LEFT JOIN lz_users as a ON t1.adv_id=a.uid ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['numid']]);
            }else{
                $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY adv_id) as t1 LEFT JOIN lz_users as a ON t1.adv_id=a.uid ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }
    /***************************************************网站报表*****************************************************/
    /**
     * 得到数据报表的所有数据
     */
    public function siteTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        $sort = $params['sort'] == 'click_cost' ? 'a.day':$sort;
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='site_list')
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,b.siteurl,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,a.uid,
            a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,
            a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.sitename,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN lz_site as b ON                     a.site_id=b.site_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy
            as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_classes as f ON b.class_id=f.class_id';
        $res = $this->_getRes($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数
     * param data
     */
    public function siteLstCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='site_list')
        {
            $id = 'a.'.'site_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(a.site_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_site as b ON a.site_id=b.site_id LEFT JOIN lz_classes as c ON b.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,a.site_id';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.site_id';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,a.site_id';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,a.site_id';
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
     * 得到数据报表的所有数据(所有时间的情况下)
     */
    public function siteAllTimeTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        $sort = $params['sort'] == 'click_cost' ? 'a.day':$sort;
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='site_list')
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,b.siteurl,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,a.uid,
            a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,
            a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.sitename,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN lz_site as b ON                       a.site_id=b.site_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy
            as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_classes as f ON b.class_id=f.class_id';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function siteAllTimeCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='site_list')
        {
            $id = 'a.'.'site_id';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(a.site_id) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
        FROM lz_stats_new as a LEFT JOIN lz_site as b ON a.site_id=b.site_id LEFT JOIN lz_classes as c ON b.class_id=c.class_id ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql .= ' GROUP BY a.day,a.site_id';
                $res = Db::query($sql);
            }else{
                $sql.= ' WHERE a.plan_type=? GROUP BY a.day,a.site_id';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? GROUP BY a.day,a.site_id';
                $res = Db::query($sql,[$params['numid']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,a.site_id';
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }

    /**
     * 网站报表今日数据  查询lz_stats_log
     */
    public function siteToStatsLog($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'site_id';
        }else{
            $id = $params['id'];
        }
        $sql = 'SELECT t1. day, t1.pid, t1.ad_id, t1.uid, t1.adv_id, t1.adz_id, t1.site_id, t1.views, t1.click_num, t1.web_deduction, t1.web_num, t1.sumadvpay, t1.sumprofit, t1.download, t1.sumpay, t1.plan_type, a.sitename, a.siteurl FROM ( SELECT day, pid, ad_id, uid, adv_id, adz_id, site_id, SUM(views) AS views, SUM(click_num) AS click_num, SUM(web_deduction) AS web_deduction, SUM(web_num) AS web_num, SUM(sumadvpay) AS sumadvpay, SUM(sumprofit) AS sumprofit, SUM(download) AS download, SUM(sumpay) AS sumpay, plan_type FROM lz_stats_log ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day=? GROUP BY site_id) as t1 LEFT JOIN lz_site as a ON t1.site_id=a.site_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day']]);
            }else{
                $sql.= ' WHERE day=? AND plan_type=? GROUP BY site_id) as t1 LEFT JOIN lz_site as a ON t1.site_id=a.site_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY site_id) as t1 LEFT JOIN lz_site as a ON t1.site_id=a.site_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['numid']]);
            }else{
                $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY site_id) as t1 LEFT JOIN lz_site as a ON t1.site_id=a.site_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }
    /***************************************************网站分类报表*************************************************/
    /**
     * 网站报表今日数据  查询lz_stats_log
     */
    public function classToStatsLog($params)
    {
        if($params['id'] == 'class_name'){
            $class = 'b.'.$params['id'];
        }else{
            $id = $params['id'];
        }

        $sql = 'SELECT t1. day, t1.pid, t1.ad_id, t1.uid, t1.adv_id, t1.adz_id, t1.site_id, t1.views, t1.click_num, t1.web_deduction, t1.web_num, t1.sumadvpay, t1.sumprofit, t1.download, t1.sumpay, t1.plan_type, b.class_name,a.class_id FROM ( SELECT day, pid, ad_id, uid, adv_id, adz_id, site_id, SUM(views) AS views, SUM(click_num) AS click_num, SUM(web_deduction) AS web_deduction, SUM(web_num) AS web_num, SUM(sumadvpay) AS sumadvpay, SUM(sumprofit) AS sumprofit, SUM(download) AS download, SUM(sumpay) AS sumpay, plan_type FROM lz_stats_log ';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day=? GROUP BY site_id) AS t1 LEFT JOIN lz_site AS a ON t1.site_id = a.site_id LEFT JOIN lz_classes AS b ON a.class_id=b.class_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day']]);
            }else{
                $sql.= ' WHERE day=? AND plan_type=? GROUP BY site_id) AS t1 LEFT JOIN lz_site AS a ON t1.site_id = a.site_id LEFT JOIN lz_classes AS b ON a.class_id=b.class_id ORDER BY t1.views DESC';
                $res = Db::query($sql,[$params['day'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                if($params['id'] == 'class_name'){
                    $sql.= ' WHERE day=? GROUP BY site_id) AS t1 LEFT JOIN lz_site AS a ON t1.site_id = a.site_id LEFT JOIN lz_classes AS b ON a.class_id=b.class_id  WHERE '.$class.'=? ORDER BY t1.views DESC';
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? GROUP BY site_id) AS t1 LEFT JOIN lz_site AS a ON t1.site_id = a.site_id LEFT JOIN lz_classes AS b ON a.class_id=b.class_id ORDER BY t1.views DESC';
                }
                $res = Db::query($sql,[$params['day'],$params['numid']]);
            }else{
                if($params['id'] == 'class_name'){
                    $sql.= ' WHERE day=? AND plan_type=? GROUP BY site_id) AS t1 LEFT JOIN lz_site AS a ON t1.site_id = a.site_id LEFT JOIN lz_classes AS b ON a.class_id=b.class_id  WHERE '.$class.'=? ORDER BY t1.views DESC';
                }else{
                    $sql.= ' WHERE day=? AND '.$id.'=? AND plan_type=? GROUP BY site_id) AS t1 LEFT JOIN lz_site AS a ON t1.site_id = a.site_id LEFT JOIN lz_classes AS b ON a.class_id=b.class_id ORDER BY t1.views DESC';
                }
                $res = Db::query($sql,[$params['day'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }

    /**
     * 得到数据报表的所有数据
     */
    public function classTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        $sort = $params['sort'] == 'click_cost' ? 'a.day':$sort;
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='classes_list')
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,
            a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,
            a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.sitename,f.class_name,f.class_id,e.cpd,e.cpd_day,d.star FROM lz_stats_new as a LEFT JOIN lz_site as b ON                     a.site_id=b.site_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy
            as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_classes as f ON b.class_id=f.class_id ';
        $res = $this->_getRes($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数
     * param data
     */
    public function classLstCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='classes_list')
        {
            $id = 'c.'.'class_name';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(c.class_name) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
            FROM lz_stats_new as a LEFT JOIN lz_site as b ON a.site_id=b.site_id LEFT JOIN lz_classes as c ON b.class_id=c.class_id';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE a.day>=? AND a.day<=? GROUP BY a.day,c.class_name';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,c.class_name';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? AND a.day>=? AND a.day<=? GROUP BY a.day,c.class_name';
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? AND a.day>=? AND a.day<=? GROUP BY a.day,c.class_name';
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
     * 得到数据报表的所有数据(所有时间的情况下)
     */
    public function classAllTimeTotal($params)
    {
        $sort = 'a.'.$params['sort'];
        $sort = $params['sort'] == 'click_cost' ? 'a.day':$sort;
        /*if($params['id'] == 'class_name'){
            $id = 'f.'.$params['id'];
        }else{
            $id = 'a.'.$params['id'];
        }*/
        if($params['id'] == 'class_name')
        {
            $id = 'f.'.$params['id'];
        }
        elseif(empty($params['id']) && $params['stats']=='classes_list')
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
        $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
            a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,
            a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,
            a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.sitename,f.class_name,e.cpd,e.cpd_day,d.star FROM lz_stats_new as a LEFT JOIN lz_site as b ON                     a.site_id=b.site_id LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy
            as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_classes as f ON b.class_id=f.class_id ';
        $res = $this->_getResAll($params,$sql,$sort,$id);
        return $res;
    }

    /**
     * 得到数据报表的数据个数（所有时间的情况下）
     * param data
     */
    public function classAllTimeCount($params)
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
        elseif(empty($params['id']) && $params['stats']=='classes_list')
        {
            $id = 'c.'.'class_name';
        }
        else
        {
            if($params['id'] != 'size'){
                $id = 'a.'.$params['id'];
            }else{
                $params['numid'] = '';
            }
        }
        $sql = 'SELECT count(c.class_name) as count,a.plan_type,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,c.class_name
            FROM lz_stats_new as a LEFT JOIN lz_site as b ON a.site_id=b.site_id LEFT JOIN lz_classes as c ON b.class_id=c.class_id';
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql .= ' GROUP BY a.day,c.class_name';
                $res = Db::query($sql);
            }else{
                $sql.= ' WHERE a.plan_type=? GROUP BY a.day,c.class_name';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                $sql.= ' WHERE '.$id.' =? GROUP BY a.day,c.class_name';
                $res = Db::query($sql,[$params['numid']]);
            }else{
                $sql.= ' WHERE '.$id.' =? AND a.plan_type=? GROUP BY a.day,c.class_name';
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        if (empty($res)) {
            return 0;
        }else{
            return count($res);
        }
    }

    /**
     * 批量删除数据报表
     */
    public function delOne($id)
    {
        $map = array(
            $id[2]=>$id[0],
            'day'=>$id[1],
        );
        $res = $this::where($map)->DELETE();
        return $res;
    }


    /**
     * 得到该天下此广告位一共有几条数据
     */
    public function updateDownload($params)
    {
        $sql = 'UPDATE lz_stats_new SET download = ? WHERE ad_id = ? AND day = ? ORDER BY ctime DESC LIMIT 1';
        $res = Db::execute($sql,[$params['money'],$params['ad_id'],$params['day']]);
        return $res;
    }


    public function _getResForPlan($params,$sql,$sort,$id)
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
                    // $sql.= ' WHERE a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                    $sql = $this->planlstRes();
                }else{
                    $sql = 'SELECT t1.*,a.plan_name,a.run_terminal,a.plan_type,a.gradation,a.checkplan from (SELECT day,ad_id,site_id,ctime,uid,pid,adz_id,adv_id,SUM(views)
                            as views,SUM(click_num) as click_num,SUM(adv_deduction) as adv_deduction,SUM(adv_num) as adv_num,SUM(ui_adzone) as ui_adzone,
                            SUM(web_click_num) as web_click_num,SUM(adz_click_num) as adz_click_num,SUM(ui_web) as ui_web,
                            SUM(sumadvpay) as sumadvpay,SUM(sumprofit) as sumprofit,SUM(download) as download,SUM(sumpay) as sumpay,
                            MAX(heavy_click_num) AS  heavy_click_num from lz_stats_new WHERE day>=? AND day<=?  GROUP BY day,pid ) as t1
                            LEFT JOIN lz_plan as a ON t1.pid=a.pid WHERE a.checkplan LIKE "%'.$mobile.'%"';
                    // $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);

            }
            else
            {
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);

            }
        }
        else
        {
            if(empty($params['type'])){
                if(empty($params['mobile']))
                {
                    $sql = 'SELECT t1.*,a.plan_name,a.run_terminal,a.plan_type from (SELECT a.day,a.pid,a.adz_id,a.adv_id,SUM(a.views) as views,
                        SUM(a.click_num) as click_num,SUM(a.adv_deduction) as adv_deduction,SUM(a.adv_num) as adv_num,
                        SUM(a.sumadvpay) as sumadvpay,SUM(a.sumprofit) as sumprofit,SUM(a.download) as download,SUM(a.sumpay
                        ) as sumpay,MAX(a.heavy_click_num) AS  heavy_click_num FROM lz_stats_new AS a WHERE '.$id.'=? AND day>=? AND day<=?
                        GROUP BY day,pid ) as t1
                        LEFT JOIN lz_plan as a ON t1.pid=a.pid ';
//                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);

            }else{
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);
            }
        }
        return $res;
    }

    /**
     * 游戏推广计划  默认数据
     **/
    public function _gamegetResForPlan($params,$sql,$sort,$id)
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
                    // $sql.= ' WHERE a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                    $sql = $this->gameplanlstRes();
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);

            }
            else
            {
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);

            }
        }
        else
        {
            if(empty($params['type'])){
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.type=1 ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);

            }else{
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.type=1 ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" AND b.type=1 ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);
            }
        }
        return $res;
    }


    public function _getResForWeb($params,$sort,$id)
    {
        if($sort == 'a.'){
            $sort = $sort.'day';
        }else{
            $sort = $sort;
        }
        if(empty($params['numid']))
        {
            if(empty($params['type']))
            {
                $sql = $this->weblstRes();
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }
            else
            {
                $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.username,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a
                LEFT JOIN lz_users as b ON a.uid=b.uid LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id
                LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_site as g ON
                 a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
                $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);
            }
        }
        else
        {
            if(empty($params['type'])){
                if($params['id'] == 'class_name'){
                    $params['id'] = 'uid';
                }
                $sql = 'SELECT t1.*,a.username,b.cpd,b.cpd_day from (SELECT day,pid,uid,adz_id,
                    adv_num,download,
                    SUM(views) as views,
                    SUM(click_num) as click_num,
                    SUM(uv_web) as uv_web,
                    SUM(ui_web) as ui_web,
                    SUM(web_deduction) as web_deduction,
                    SUM(web_num) as web_num,
                    SUM(sumpay) as sumpay,
                    SUM(sumprofit) as sumprofit,
                    MAX(web_click_num) as web_click_num from lz_stats_new WHERE day>=? AND day<=? AND '.$params['id'].'=? GROUP BY day,adz_id) as t1
                    LEFT JOIN lz_users as a ON t1.uid=a.uid
                    LEFT JOIN lz_adzone_copy as b ON t1.adz_id=b.adz_id and t1.day=b.cpd_day';
                //     $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,
                // a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,
                // a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,
                // a.uv_adzone,a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.username,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a LEFT JOIN lz_users as b            ON a.uid=b.uid LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id
                // LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_site as g ON
                // a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
                //     $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                $res = Db::query($sql,[$params['day'],$params['day1'],$params['numid']]);
            }else{
                $sql = 'SELECT a.ctime,a.views,a.num,a.web_num,a.adv_num,a.download,a.click_num,a.click_again,a.effect_num,a.web_deduction,a.adv_deduction,a.sumprofit,a.sumpay,a.sumadvpay,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5,c.pricedv,a.pid,a.uid,a.ad_id,a.adz_id,a.adv_id,a.site_id,a.ui_plan,a.ui_web,a.ui_ads,a.ui_adzone,a.uv_plan,a.uv_web,a.uv_ads,a.uv_adzone,a.day,a.heavy_click_num,a.web_click_num,a.adz_click_num,b.username,e.cpd,e.cpd_day,d.star,f.class_name FROM lz_stats_new as a
                LEFT JOIN lz_users as b ON a.uid=b.uid LEFT JOIN lz_plan_price as c ON a.tc_id=c.id LEFT JOIN lz_adzone as d ON a.adz_id=d.adz_id
                LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN lz_site as g ON
                 a.site_id=g.site_id LEFT JOIN lz_classes as f ON f.class_id=g.class_id';
                $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);
            }
        }
        return $res;
    }
    /**
     * 广告商报表默认数据
     **/
    private function _getResForAdv($params,$sql,$sort,$id)
    {
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' ||
            $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = '';
            $sort = 'a.'.$params['sort'];
        }
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
                    //$sql.= ' WHERE a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                    $sql = $this->advlstRes();
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);

            }
            else
            {
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);

            }
        }
        else
        {
            if(empty($params['type'])){
                if(empty($params['mobile']))
                {
                    // $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                    $sql = $this->advlstResForid($params);
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);

            }else{
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);


            }
        }
        return $res;
    }

    /**
     * 将各个报表相同的sql合并
     */
    private function _getRes($params,$sql,$sort,$id)
    {
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' ||
            $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = '';
            $sort = 'a.'.$params['sort'];
        }
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
                    $sql.= ' WHERE a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['day'],$params['day1']]);

            }
            else
            {
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['type'],$params['day'],$params['day1']]);

            }
        }
        else
        {
            if(empty($params['type'])){
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['day'],$params['day1']]);

            }else{
                if(empty($params['mobile']))
                {
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.'=? AND a.plan_type=? AND a.day>=? AND a.day<=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['type'],$params['day'],$params['day1']]);


            }
        }
        return $res;
    }

    /**
     * 将各个报表相同的sql合并(所有时间段)
     */
    private function _getResAll($params,$sql,$sort,$id)
    {
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' ||
            $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = '';
            $sort = 'a.'.$params['sort'];
        }
        if($sort == 'a.'){
            $sort = $sort.'day';
        }else{
            $sort = $sort;
        }
        $mobile = empty($params['mobile']) ? '':$params['mobile'];
        if(empty($params['numid'])){
            if(empty($params['type'])){
                if(empty($params['mobile'])){
                    $sql.= ' ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql);
            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE a.plan_type=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE a.plan_type=? AND b.checkplan LIKE "%'.$mobile.'%" ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['type']]);

            }
        }else{
            if(empty($params['type'])){
                if(empty($params['mobile'])){

                    $sql= 'SELECT t1.*,a.plan_name,a.run_terminal,a.plan_type from (SELECT a.day,a.pid,a.adz_id,a.adv_id,SUM(a.views) as views,
                        SUM(a.click_num) as click_num,SUM(a.adv_deduction) as adv_deduction,SUM(a.adv_num) as adv_num,
                        SUM(a.sumadvpay) as sumadvpay,SUM(a.sumprofit) as sumprofit,SUM(a.download) as download,SUM(a.sumpay
                    ) as sumpay,MAX(a.heavy_click_num) AS  heavy_click_num FROM lz_stats_new AS a WHERE '.$id.'=?
                        GROUP BY day,pid ) as t1
                        LEFT JOIN lz_plan as a ON t1.pid=a.pid';
//                    $sql.= ' WHERE '.$id.' =? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND b.checkplan LIKE "%'.$mobile.'%"  ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid']]);

            }else{
                if(empty($params['mobile'])){
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=? ORDER BY '.$sort.' DESC ';
                }else{
                    $sql.= ' WHERE '.$id.' =? AND a.plan_type=?  AND b.checkplan LIKE "%'.$mobile.'%"  ORDER BY '.$sort.' DESC ';
                }
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        return $res;
    }

    /**
     *  二次点击列表
     */
    public function twoClick($params)
    {
        $sql = 'SELECT adz_id,event_type,click_num,ui_num,day FROM lz_two_click WHERE day>=? AND day<=? ';
        $res = Db::query($sql,[$params['day'],$params['day1']]);
        return $res;
    }

    /**
     * 得到广告位分类报表数据
     */
    public function adzClassList($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'b.'.$params['id'];
        }else{
            $id = $params['id'];
        }
        $stats = 'lz_stats_new';
        $today = date('Y-m-d').date('Y-m-d');
        if($params['time'] == $today){
            $params['day'] = date('Y-m-d');
            $stats = 'lz_stats_log';
        }
        $sql = 'SELECT t1.*,a.class_id,b.class_name FROM (SELECT SUM(views) AS views,SUM(web_num) AS web_num,SUM(click_num) AS click_num,SUM(web_deduction) AS web_deduction,SUM(sumprofit) AS sumprofit,SUM(sumpay) AS sumpay,SUM(sumadvpay) AS sumadvpay,pid,uid,ad_id,adz_id,adv_id,site_id,day,plan_type
        FROM '.$stats.' ';
        $res = $this->_getResForAdzClass($params,$sql,$id);
        return $res;
    }

    /**
     * 广告位分类报表搜索条件
     */
    private function _getResForAdzClass($params,$sql,$id)
    {
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id';
                $res = Db::query($sql,[$params['day'],$params['day1']]);
            }else{
                $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE t1.plan_type=?';
                $res = Db::query($sql,[$params['day'],$params['day1'],$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                if($params['id'] == 'class_name'){
                    $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE '.$id.'=?';
                }else{
                    $sql.= ' WHERE day>=? AND day<=? AND '.$id.'=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id';
                }
                $res = Db::query($sql,[$params['day'],$params['day1'],$params['numid']]);
            }else{
                if($params['id'] == 'class_name'){
                    $sql.= ' WHERE day>=? AND day<=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE '.$id.'=? AND t1.plan_type=?';
                }else{
                    $sql.= ' WHERE day>=? AND day<=? AND '.$id.'=? GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE t1.plan_type=?';
                }
                $res = Db::query($sql,[$params['day'],$params['day1'],$params['numid'],$params['type']]);
            }
        }
        return $res;
    }

    /**
     * 广告位分类报表搜索所有时间段情况
     */
    public function adzClasAllTimeTotal($params)
    {
        if($params['id'] == 'class_name'){
            $id = 'b.'.$params['id'];
        }else{
            $id = $params['id'];
        }
        $sql = 'SELECT t1.*,a.class_id,b.class_name FROM (SELECT SUM(views) AS views,SUM(web_num) AS web_num,SUM(click_num) AS click_num,SUM(web_deduction) AS web_deduction,SUM(sumprofit) AS sumprofit,SUM(sumpay) AS sumpay,SUM(sumadvpay) AS sumadvpay,pid,uid,ad_id,adz_id,adv_id,site_id,day,plan_type
        FROM lz_stats_new';
        $res = $this->_getAdzClassAll($params,$sql,$id);
        return $res;
    }

    /**
     * 广告位分类报表搜索所有时间段条件
     */
    private function _getAdzClassAll($params,$sql,$id)
    {
        if(empty($params['numid'])){
            if(empty($params['type'])){
                $sql.= ' GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id';
                $res = Db::query($sql);
            }else{
                $sql.= ' GROUP BY day,adz_id) AS t1
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE t1.plan_type=?';
                $res = Db::query($sql,[$params['type']]);
            }
        }else{
            if(empty($params['type'])){
                if($params['id'] == 'class_name'){
                    $sql.= ' GROUP BY day,adz_id) AS t1 
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE '.$id.'=?';
                }else{
                    $sql.= ' WHERE '.$id.'=? GROUP BY day,adz_id) AS t1 
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id';
                }
                $res = Db::query($sql,[$params['numid']]);
            }else{
                if($params['id'] == 'class_name'){
                    $sql.= ' GROUP BY day,adz_id) AS t1 
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE '.$id.'=? AND t1.plan_type=?';
                }else{
                    $sql.= ' WHERE '.$id.'=? GROUP BY day,adz_id) AS t1 
                        LEFT JOIN lz_adzone AS a ON t1.adz_id=a.adz_id
                        LEFT JOIN lz_classes AS b ON a.class_id=b.class_id WHERE t1.plan_type=?';
                }
                $res = Db::query($sql,[$params['numid'],$params['type']]);
            }
        }
        return $res;
    }

    /**
     * 获取今天的大点击情况
     */
    public function getTodayBigClick($params)
    {
        $sql = 'SELECT pid,SUM(bigclick) as bigclick,day FROM lz_bigclick_log WHERE day=? GROUP BY pid,day';
        $res = Db::query($sql,[$params['day1']]);
        return $res;
    }

    /**
     * 获取今天之前的大点击情况
     */
    public function getBigClick($params)
    {
        $sql = 'SELECT pid,SUM(bigclick) as bigclick,day FROM lz_bigclick WHERE day>=? AND day<=? GROUP BY pid,day';
        $res = Db::query($sql,[$params['day'],$params['day1']]);
        return $res;
    }

}
