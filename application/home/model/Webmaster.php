<?php
namespace app\home\model;
use think\Model;
use think\Db;


class Webmaster extends Model
{

    /**
     * 获取当天报告
     */
    public function reportNow($param)
    {
        $sql = 'SELECT a.day,a.adz_id,a.views,a.num,a.sumpay,a.plan_type,a.web_deduction,b.money,f.gradation,d.star,e.cpd,e.cpd_day,f.price,f.price_1,
        f.price_2,f.price_3,f.price_4,f.price_5 FROM lz_stats_new AS a LEFT JOIN lz_users AS b
        ON a.uid=b.uid LEFT JOIN lz_plan AS c ON a.pid=c.pid LEFT JOIN  lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN
        lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id  LEFT JOIN lz_plan_price AS f ON a.tc_id = f.id WHERE a.uid=? AND a.day=? ORDER BY plan_type';
        $res = Db::query($sql,[$param['uid'],$param['day']]);
        return $res;
    }

    /**
     * 获取当天收入
     */
    public function reportNownew($param)
    {
        $sql = 'SELECT a.day,a.adz_id,SUM(a.sumpay) as sumpay,e.cpd,e.cpd_day,d.cpd_status FROM lz_stats_log AS a LEFT JOIN lz_users AS b
        ON a.uid=b.uid LEFT JOIN  lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN
        lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id  WHERE a.uid=? AND a.day=? GROUP BY adz_id';
        $res = Db::query($sql,[$param['uid'],$param['day']]);
        return $res;
    }


    /**
     * 获取昨日支付
     */
    public function getYesMoney($param)
    {
        $sql = 'SELECT a.day,a.adz_id,SUM(a.sumpay) as sumpay,d.cpd,d.cpd_day,c.cpd_status FROM lz_stats_log AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id LEFT JOIN lz_adzone_copy as d ON a.day=d.cpd_day AND a.adz_id=d.adz_id LEFT JOIN lz_plan_price AS f ON a.tc_id = f.id WHERE a.uid=? AND a.day=? GROUP BY adz_id';
        $res = Db::query($sql,[$param['uid'],$param['yesterday']]);
        return $res;
    }

    /**
     * 获取当月支付
     */
    public function getMonthMoney($param)
    {
        $sql = 'SELECT a.day,a.adz_id,SUM(a.sumpay) as sumpay,d.cpd,d.cpd_day,c.cpd_status FROM lz_stats_new AS a LEFT JOIN lz_plan AS b ON a.pid=b.pid LEFT JOIN lz_adzone as c ON a.adz_id=c.adz_id LEFT JOIN lz_adzone_copy as d ON a.day=d.cpd_day AND a.adz_id=d.adz_id  WHERE a.uid=? AND a.day>=? AND a.day<=? GROUP BY a.adz_id,a.day ';
        $res = Db::query($sql,[$param['uid'],$param['firstday'],$param['yesterday']]);
        return $res;
    }

    /**
     * 获取站长余额
     */
    public function webMoney($uid)
    {
        $sql = 'SELECT  money FROM lz_users WHERE uid=?';
        $res = Db::query($sql,[$uid]);
        if(empty($res)){
            return 0;
        }else{
            return $res['0'];
        }
    }

    /**
     * 获取站长 stats当天的数据
     */
    public function webMoneyPay($uid)
    {
        $date =date("Y-m-d");
        $sql = 'SELECT SUM(a.sumpay) as money,a.adz_id,b.cpd,b.cpd_day FROM lz_stats_new AS a LEFT JOIN lz_adzone_copy AS b 
            ON a.adz_id =b.adz_id WHERE a.uid=? AND a.day=?';
        $res = Db::query($sql,[$uid,$date]);
        if(empty($res)){
            return 0;
        }else{
            return $res['0'];
        }
    }

    /**
     *  显示基本信息
     */
    public function account($data)
    {

        $res = Db::name('users')->select($data);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     *  修改信息
     */
    public  function edit($uid,$data)
    {
        $map = array(
            'uid'=>$uid,
        );
        $res = Db::name('users')->where($map)->update($data);
        //数据同步users_log
//        Db::name('users_log')->where($map)->update($data);
        return $res;
    }

    /**
     *  查询当前密码
     */
    public function getOne($uid)
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
        Db::name('users_log')->where($map)->update($data);
        return $res;
    }

    /**
     *  查输入网址是否重复
     */
    public function siteRepeatUrl($url)
    {
        $map = array(
            'siteurl'=>$url,
        );
        $res = Db::name('site')->where($map)->find();
        return $res;
    }

    /**
     *   网站列表
     */
    public function siteList($uid)
    {
       $res = Db::table('lz_site')->alias('a')->join('lz_classes b','a.class_id = b.class_id')->
       field('a.site_id,a.sitename,a.siteurl,a.status,a.https,b.class_id as claid,b.class_name')->where('uid="'.$uid.'"')->order('a.site_id','desc')->select();

        return $res;
    }

    /**
     *   网站添加
     */
    public function siteAdd($data)
    {
       $res = Db::name('site')->insert($data);
       return $res;
    }

    /**
     *  网站修改
     */
    public function siteEdit($uid,$data)
    {
        $map = array(
            'site_id'=>$uid,
        );
        $res = Db::name('site')->where($map)->update($data);
        return $res;
    }

    /**
     *  网站删除
     */
    public function siteDele($siteid)
    {
        $map = array(
            'site_id'=>$siteid,
        );
        $res = Db::name('site')->where($map)->delete();
        return $res;
    }

    /**
     *  修改时默认显示查询
     */
    public function siteOne($uid)
    {
        $map = array(
            'site_id'=>$uid,
        );
        $res = Db::name('site')->where($map)->find();
        return $res;
    }
    
    /**
     *   查询网站分类
     */
    public function siteClass()
    {
        $res = Db::name('classes')->where('type=1')->select();
        return $res;
    }

    /**
     *  查询广告表id
     */
    public function plan($textType)
    {
        $sql = 'SELECT a.ad_id,a.pid,a.tpl_id,a.status,b.tpl_id as admtplid,b.status as admstatus,c.pid as planid,c.plan_type,c.status as planstatus,
            c.restrictions,c.resuid,c.checkplan FROM lz_ads AS a LEFT JOIN lz_admode AS b ON a.tpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.pid = c.pid 
            WHERE a.status =1 AND c.status = 1 AND b.status = 1 AND c.plan_type IN ('.$textType.')';
        $res = Db::query($sql);
        return $res;
    }


    /**
     *  全局展示设置
     */
    public function sett()
    {
        $sql = 'SELECT cpc,cpm,cpv,cps,cpa FROM lz_setting';
        $res = Db::query($sql);
        return $res;
    }


    /**
     *  查询广告表 (不限制站长)
     */
    public function adsSql($pid)
    {
        $sql = 'SELECT a.*,b.tpl_id as admtplid,b.adtype_id,b.tplname,b.htmlcontrol,b.status as admstatus,c.pid as planid,c.plan_type,c.status as planstatus,c.restrictions,c.resuid FROM lz_ads AS a LEFT JOIN lz_admode AS b ON a.tpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.pid = c.pid WHERE a.status =1 AND c.status =1 AND b.status = 1 AND c.pid IN ('.$pid.') ';
        $res = Db::query($sql);
        return $res;
    }

//    /**
//     *  查询广告表 (允许以下站长)
//     */
//    public function adsSqlLimit($pid,$resuid)
//    {
//        $sql = 'SELECT a.*,b.tpl_id as admtplid,b.adtype_id,b.tplname,b.htmlcontrol,b.status as admstatus,c.pid as planid,c.plan_type,c.status as planstatus,c.restrictions,c.resuid,d.uid AS userid FROM lz_ads AS a LEFT JOIN lz_admode AS b ON a.tpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.pid = c.pid LEFT JOIN lz_users AS d ON d.uid =d.uid WHERE a.status =1 AND c.status =1 AND c.pid IN ('.$pid.') AND d.uid IN ('.$resuid['resuidLimit'].')';
//        dump($sql);
//        $res = Db::query($sql);
//        return $res;
//    }
//
//    /**
//     *  查询广告表 (不允许以下站长)
//     */
//    public function adsSqlNoLimit($pid,$resuid)
//    {
//        $sql = 'SELECT a.*,b.tpl_id as admtplid,b.adtype_id,b.tplname,b.htmlcontrol,b.status as admstatus,c.pid as planid,c.plan_type,c.status as planstatus,c.restrictions,c.resuid,d.uid AS userid FROM lz_ads AS a LEFT JOIN lz_admode AS b ON a.tpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.pid = c.pid LEFT JOIN lz_users AS d ON d.uid =d.uid WHERE a.status =1 AND c.status =1 AND c.pid IN ('.$pid.') AND d.uid NOT IN ('.$resuid['resuidNoLimit'].')';
//        dump($sql);
//        $res = Db::query($sql);
//        return $res;
//    }

    /**
     *  查询计费方式
     */
    public function typeSql($impid)
    {
        $sql = 'SELECT pid,plan_type FROM lz_plan  WHERE pid IN ('.$impid.') AND status = 1';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查询广告类型
     */
    public function adtype($zitplid,$keytplid)
    {
        $sql = 'SELECT a.ad_id,a.tpl_id,a.status,b.status,b.tpl_id AS tyid,b.adtype_id,b.tplname,b.type,c.adtype_id AS adtyid,c.type_name
        FROM lz_ads AS a LEFT JOIN lz_admode AS b ON a.tpl_id = b.tpl_id LEFT JOIN lz_adtype AS c ON b.adtype_id = c. adtype_id WHERE a.status =1 AND b.status = 1  AND a.tpl_id IN ('.$zitplid.') AND a.ad_id IN ('.$keytplid.')';
        $res = Db::query($sql);

        return $res;
    }
    /**
     *  查询广告类型取重
     */
    public function chongType($getptname)
    {
        $sql = 'SELECT a.tpl_id,a.ad_id,a.pid AS adpid,b.pid from lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid WHERE b.plan_type = "'.$getptname.'" AND b.status = 1 AND a.status = 1';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  cpc下查看固定位置广告
     */
    public function chongType_CPC($getptname)
    {
        $sql = 'SELECT a.tpl_id,a.ad_id,a.pid AS adpid,b.pid from lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid
                WHERE b.plan_type = "'.$getptname.'" AND b.status = 1 AND a.status = 1 AND a.tpl_id=5030';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  cpm下查看固定位置以外的广告
     */
    public function chongType_CPM($getptname)
    {
        $sql = 'SELECT a.tpl_id,a.ad_id,a.pid AS adpid,b.pid from lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid
                WHERE b.plan_type = "'.$getptname.'" AND b.status = 1 AND a.status = 1 AND a.tpl_id!=5030';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查询广告尺寸
     */
    public function zoneSize($chongtyid,$zonelei,$getptname)
    {
        $sql = 'SELECT a.ad_id,a.tpl_id,a.width,a.height,a.pid AS adpid,b.pid FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid ';
        if(empty($zonelei)){
        $sql.='WHERE tpl_id ='.$chongtyid.' AND b.plan_type="'.$getptname.'" AND a.status=1 ORDER BY width DESC ';
        }else{
        $sql.='WHERE tpl_id ='.$zonelei.' AND b.plan_type="'.$getptname.'" AND a.status=1 ORDER BY width DESC ';
        }

        $res = Db::query($sql);
        return $res;
    }

    /**
     *  处理广告尺寸结果
     */
    public function guangSize($chongtyid,$zonelei,$keysize)
    {
        $sql = 'SELECT ad_id,tpl_id,width,height from lz_ads ';
        if(empty($zonelei)){
            $sql.='WHERE tpl_id ='.$chongtyid.' AND ad_id IN ('.$keysize.') ORDER BY width DESC,height DESC ';
        }else{
            $sql.='WHERE tpl_id ='.$zonelei.' AND ad_id IN ('.$keysize.')  ORDER BY width DESC,height DESC ';
        }

        $res = Db::query($sql);
        return $res;
    }

    /**
     *  显示效果
     */
    public function styleShow($chongtyid,$zonelei)
    {
        $sql = 'SELECT a.tpl_id,b.style_id,b.tpl_id AS stytplid,b.stylename,b.htmlcontrol,b.style_id,b.specs FROM lz_admode AS a LEFT JOIN lz_adstyle AS b ON a.tpl_id = b.tpl_id ';
        if(empty($zonelei)){
            $sql.='WHERE b.tpl_id ='.$chongtyid;
        }else{
            $sql.='WHERE b.tpl_id ='.$zonelei;
        }
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  广告过滤
     * */
    public function zoneFilter($admid,$width,$height,$pidChu)
    {
        $sql = 'SELECT a.ad_id,a.tpl_id,a.width,a.height,a.pid,a.files,a.imageurl,b.pid AS planid,b.plan_name,c.gradation,c.price,c.price_1,c.price_2,c.price_3,c.price_4,c.price_5 FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_plan_price AS c ON a.tc_id = c.id WHERE a.tpl_id ='.$admid.' AND a.width='.$width.' AND a.height='.$height.' AND a.pid IN ('.$pidChu.') AND a.status = 1 AND b.status = 1 ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  同计划下广告
     * */
    public function pname($getptname)
    {
        $sql = 'SELECT pid FROM  lz_plan  WHERE  plan_type="'.$getptname.'" AND status = 1 ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  广告过滤
     * */
    public function zoneAdd($data)
    {

        $res = Db::name('adzone')->insert($data);
        $adz_id = Db::name('adzone')->getLastInsID();
        return $adz_id;
    }

    /**
     *  广告列表
     */
    public function actList($data,$param)
    {
		if(!empty($param['mobile'])){
                $mobile = $param['mobile'];
                $mobile_sql = ' AND a.system_type ='.$mobile.'';
            }else{
                $mobile_sql = '';
            }

        $sql = 'SELECT a.adz_id,a.uid,a.zonename,a.plantype,a.adtpl_id,a.width,a.height,a.status,a.add_time,a.system_type,b.tpl_id,b.tplname,c.pid,c.plan_type,c.restrictions,c.resuid,a.type
        FROM lz_adzone AS a LEFT JOIN lz_admode AS b ON a.adtpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.plantype = c.plan_type WHERE a.uid ='.$data.' '.$mobile_sql.'  GROUP BY a.adz_id  ORDER BY a.adz_id DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  广告列表
     */
    public function actListType($uid,$type,$param)
    {
		if(!empty($param['mobile'])){
                $mobile = $param['mobile'];
                $mobile_sql = ' AND a.system_type ='.$mobile.'';
            }else{
                $mobile_sql = '';
            }

        $sql = 'SELECT a.adz_id,a.uid,a.zonename,a.plantype,a.adtpl_id,a.width,a.height,a.status,a.add_time,a.system_type,b.tpl_id,b.tplname,c.pid,c.plan_type,c.restrictions,c.resuid,a.type
        FROM lz_adzone AS a LEFT JOIN lz_admode AS b ON a.adtpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.plantype = c.plan_type WHERE a.uid ='.$uid.' AND c.plan_type ="'.$type.'" '.$mobile_sql.'  GROUP BY a.adz_id ORDER BY adz_id DESC ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  广告修改
     */
    public function activitEdit($zoneid)
    {
        $sql = 'SELECT a.adz_id,a.uid,a.zonename,a.star,a.plantype,a.adtpl_id,a.class_id,a.width,a.height,a.viewtype,a.adstyle_id,a.htmlcontrol,a.show_adid,a.minutes,b.tpl_id,b.tplname,c.pid,c.plan_type,d.style_id,d.htmlcontrol AS adhtml,a.type,a.adz_type
        FROM lz_adzone AS a LEFT JOIN lz_admode AS b ON a.adtpl_id = b.tpl_id LEFT JOIN lz_plan AS c ON a.plantype = c.plan_type LEFT JOIN lz_adstyle AS d ON a.adstyle_id = d.style_id
         WHERE a.adz_id ='.$zoneid.' ';
        $res = Db::query($sql);
        return $res;
    }

   /**
     * 获取广告位分类
     */
    public function getClass()
    {
        $sql = 'SELECT class_id,class_name FROM lz_classes';
        $res = Db::query($sql);
        return $res;
    }


    /**
     *  查询到同类型下的所有广告显示效果
     */
    public function actShow($adtplid)
    {
        $sql = 'SELECT b.style_id,b.tpl_id,b.stylename,b.specs FROM  lz_adstyle AS b  WHERE b.tpl_id = '.$adtplid.' ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查询同尺寸下的显示效果
     */
    public function effect($zigid){
        $sql = 'SELECT style_id,stylename FROM lz_adstyle WHERE style_id IN ('.$zigid.')';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查询同尺寸下的显示效果
     */
    public function zShow($keytplid,$zoneid){
        $sql = 'SELECT a.ad_id,a.files,a.imageurl,a.pid,a.width AS adwidth,a.height AS adheight,b.pid AS planid,b.plan_name,c.adz_id,c.uid,c.star,c.plantype,c.adtpl_id,c.width,c.height,d.gradation,d.price,d.price_1,d.price_2,d.price_3,d.price_4,d.price_5 FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid LEFT JOIN lz_adzone AS c ON a.width = c.width LEFT JOIN lz_plan_price AS d ON a.tc_id = d.id WHERE a.width = c.width AND a.height = c.height AND a.pid IN ('.$keytplid.') AND c.adz_id ='.$zoneid.' AND a.status=1 AND b.status=1 ORDER BY a.ad_id DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *   修改广告位
     * */
    public function zoneEdit($data,$gid)
    {
        $res = Db::name('adzone')->where('adz_id='.$gid)->update($data);
        return $res;
    }

    /**
     *   删除广告位
     */
    public function zdel($zdel)
    {
        $res = Db::name('adzone')->where('adz_id='.$zdel)->delete();
        return $res;
    }

    /**
     *   活动广告
     */
    public function planList()
    {
        $sql = 'SELECT a.pid,a.plan_name,a.plan_type,a.class_id,a.status,b.class_id AS claid,b.class_name,b.type FROM lz_plan AS a LEFT JOIN lz_classes AS b ON a.class_id = b.class_id WHERE a.status !=2 ORDER BY a.pid DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  分类活动广告列表
     */
    public function ptype($gettype)
    {
        $sql = 'SELECT a.pid,a.plan_name,a.plan_type,a.class_id,a.status,b.class_id AS claid,b.class_name,b.type FROM lz_plan AS a LEFT JOIN lz_classes AS b ON a.class_id = b.class_id WHERE a.status !=2 AND a.plan_type = "'.$gettype.'" ORDER BY a.pid DESC ';
        $res = Db::query($sql);
        return $res;
    }


    /**
     *   活动广告详情
     */
    public function planinfo($pid)
    {
        $sql = 'SELECT a.pid,a.plan_name,a.plan_type,a.class_id,a.checkplan,a.status,b.class_id AS claid,b.class_name,b.type FROM lz_plan AS a LEFT JOIN lz_classes AS b
        ON a.class_id = b.class_id WHERE a.pid='.$pid.' ORDER BY a.pid DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查看广告活动分类
     */
    public function plantype($pid,$tid)
    {
        if(!empty($tid)){
            $where = ' AND b.ad_id IN('.$tid.')';
        }else{
            $where ='';
        }

        $sql = 'SELECT a.pid,b.tpl_id,b.ad_id,b.pid AS adpid,b.status,c.tpl_id AS admtpid,c.tplname,c.status AS admstats
        FROM lz_plan AS a LEFT JOIN lz_ads AS b ON a.pid = b.pid LEFT JOIN lz_admode AS c ON b.tpl_id = c.tpl_id
         WHERE a.pid='.$pid.' '.$where.'  ORDER BY b.ad_id DESC';

        $res = Db::query($sql);
        return $res;
    }

    /**
     *  广告活动分类id
     */
    public function plans($pid)
    {
        $sql = 'SELECT a.pid,b.tpl_id,b.ad_id,b.pid AS adpid,b.status,c.tpl_id AS admtpid,c.status AS admstats
        FROM lz_plan AS a LEFT JOIN lz_ads AS b ON a.pid = b.pid LEFT JOIN lz_admode AS c ON b.tpl_id = c.tpl_id
         WHERE  a.pid='.$pid.' ORDER BY b.ad_id DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  查看广告
     */
    public function planshow($pid,$tplid)
    {
        if(!empty($tplid)){
            $tplid = ' AND b.tpl_id = '.$tplid.'';
        }
        $sql = 'SELECT a.pid,b.tpl_id,b.ad_id,b.pid AS adpid,b.files,b.imageurl,b.width,b.height,b.status,c.tpl_id AS admtpid,c.tplname,c.status AS admstats
        FROM lz_plan AS a LEFT JOIN lz_ads AS b ON a.pid = b.pid LEFT JOIN lz_admode AS c ON b.tpl_id = c.tpl_id
         WHERE  a.pid='.$pid.' '.$tplid.'   ORDER BY b.ad_id DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     *  汇总报告
     */
    public function statlist($param)
    {
        $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }

		if(!isset($param['type'])){
            $param['type'] = '';
        }

        if($param['type'] == 'site'){
            $type = ',a.adz_id,a.site_id ';
        }elseif($param['type'] == 'adz'){
            $type = ',a.adz_id ';
        }else{
            $type =',a.adz_id ';
        }

        $sql = "SELECT SUM(a.sumpay) AS sumpay,a.uid,a.adz_id,a.day,a.site_id,b.cpd,c.zonename,c.cpd_status,d.sitename FROM ".$stats." AS a 
            LEFT JOIN lz_adzone_copy AS b ON a.day=b.cpd_day AND a.adz_id=b.adz_id 
            LEFT JOIN lz_adzone AS c ON a.adz_id=c.adz_id 
            LEFT JOIN lz_site AS d ON a.site_id=d.site_id WHERE a.uid=? AND a.day>=? AND a.day<=? GROUP BY a.day ".$type." 
            ORDER BY a.day DESC";
        $res = Db::query($sql,[$param['uid'],$param['startday'],$param['endday']]);
        return $res;
    }

    /**
     *  汇总报告 今日数据
     */
    public function statlistToday($param)
    {
		if(!isset($param['type'])){
            $param['type'] = '';
        }

        if($param['type'] == 'site'){
            $type = ',a.adz_id,a.site_id ';
        }elseif($param['type'] == 'adz'){
            $type = ',a.adz_id ';
        }else{
            $type =',a.adz_id ';
        }

        $sql = "SELECT SUM(a.sumpay) AS sumpay,a.uid,a.adz_id,a.site_id,a.day,b.cpd,c.zonename,c.cpd_status,d.sitename FROM lz_stats_log AS a 
            LEFT JOIN lz_adzone_copy AS b ON a.day=b.cpd_day AND a.adz_id=b.adz_id 
            LEFT JOIN lz_adzone AS c ON a.adz_id=c.adz_id 
            LEFT JOIN lz_site AS d ON a.site_id=d.site_id WHERE a.uid=? AND a.day=? GROUP BY a.day ".$type." 
            ORDER BY a.day DESC";
        $res = Db::query($sql,[$param['uid'],$param['endday']]);
        return $res;
    }

    /**
     *  汇总报告 今日数据
     */
    public function statAdzlistToday($param)
    {
		if(!isset($param['type'])){
            $param['type'] = '';
        }

        if($param['type'] == 'site'){
            $type = ',a.adz_id,a.site_id ';
        }elseif($param['type'] == 'adz'){
            $type = ',a.adz_id ';
        }else{
            $type =',a.adz_id ';
        }

        $sql = "SELECT SUM(a.sumpay) AS sumpay,a.uid,a.adz_id,a.site_id,a.day,b.cpd,c.zonename,c.cpd_status,d.sitename FROM lz_stats_log AS a 
            LEFT JOIN lz_adzone_copy AS b ON a.day=b.cpd_day AND a.adz_id=b.adz_id 
            LEFT JOIN lz_adzone AS c ON a.adz_id=c.adz_id 
            LEFT JOIN lz_site AS d ON a.site_id=d.site_id WHERE a.uid=? AND a.adz_id=? AND a.day=? GROUP BY a.day ".$type." 
            ORDER BY a.day DESC";
        $res = Db::query($sql,[$param['uid'],$param['adz_id'],$param['endday']]);
        return $res;
    }

    /**
     *  汇总报告 今日数据
     */
    public function statSitelistToday($param)
    {
		if(!isset($param['type'])){
            $param['type'] = '';
        }

        if($param['type'] == 'site'){
            $type = ',a.adz_id,a.site_id ';
        }elseif($param['type'] == 'adz'){
            $type = ',a.adz_id ';
        }else{
            $type =',a.adz_id ';
        }

        $sql = "SELECT SUM(a.sumpay) AS sumpay,a.uid,a.adz_id,a.site_id,a.day,b.cpd,c.zonename,c.cpd_status,d.sitename FROM lz_stats_log AS a 
            LEFT JOIN lz_adzone_copy AS b ON a.day=b.cpd_day AND a.adz_id=b.adz_id 
            LEFT JOIN lz_adzone AS c ON a.adz_id=c.adz_id 
            LEFT JOIN lz_site AS d ON a.site_id=d.site_id WHERE a.uid=? AND a.site_id=? AND a.day=? GROUP BY a.day ".$type." 
            ORDER BY a.day DESC";
        $res = Db::query($sql,[$param['uid'],$param['site_id'],$param['endday']]);
        return $res;
    }


    /**
     * 获取计划报告检索下的当天报告
     */
    public function getPlanPerformance($param)
    {
        $sql = 'SELECT a.views,a.num,a.sumpay,a.plan_type,a.day,a.web_deduction,b.bigpname,a.pid,a.ad_id,a.adz_id,b.plan_name,
            d.star,d.zonename,e.cpd,e.cpd_day,f.gradation,f.price,f.price_1,f.price_2,f.price_3,f.price_4,f.price_5 FROM
            lz_stats_new AS a LEFT JOIN lz_plan as b ON a.pid=b.pid LEFT JOIN
            lz_adzone as d ON a.adz_id=d.adz_id LEFT JOIN lz_adzone_copy as e ON a.day=e.cpd_day AND a.adz_id=e.adz_id LEFT JOIN
            lz_plan_price AS f ON a.tc_id = f.id  WHERE a.uid=? AND a.pid=? AND a.day>=? AND a.day<=? ORDER BY a.day DESC ';
        $res = Db::query($sql,[$param['uid'],$param['pid'],$param['startday'],$param['endday']]);
        return $res;
    }

    /**
     * 获取广告位报告检索下的当天报告
     */
    public function getadzPerformance($param)
    {
        $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
		if(!isset($param['type'])){
            $param['type'] = '';
        }

        if($param['type'] == 'site'){
            $type = ',a.adz_id,a.site_id ';
        }elseif($param['type'] == 'adz'){
            $type = ',a.adz_id ';
        }else{
            $type =',a.adz_id ';
        }

        $sql = "SELECT SUM(a.sumpay) AS sumpay,a.uid,a.adz_id,a.day,b.cpd,c.zonename,c.cpd_status FROM ".$stats." AS a 
            LEFT JOIN lz_adzone_copy AS b ON a.day=b.cpd_day AND a.adz_id=b.adz_id 
            LEFT JOIN lz_adzone AS c ON a.adz_id=c.adz_id WHERE a.uid=? AND a.adz_id=? AND a.day>=? AND a.day<=? GROUP BY a.day ".$type." 
            ORDER BY a.day DESC";
        $res = Db::query($sql,[$param['uid'],$param['adz_id'],$param['startday'],$param['endday']]);
        return $res;
    }

    /**
     * 获取网站报告检索下的当天报告
     */
    public function getsitePerformance($param)
    {
        $day = date('Y-m-d');
        if($param['startday']==$day&&$param['endday']==$day){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
		if(!isset($param['type'])){
            $param['type'] = '';
        }

        if($param['type'] == 'site'){
            $type = ',a.adz_id,a.site_id ';
        }elseif($param['type'] == 'adz'){
            $type = ',a.adz_id ';
        }else{
            $type =',a.adz_id ';
        }

        $sql = "SELECT SUM(a.sumpay) AS sumpay,a.uid,a.adz_id,a.day,a.site_id,b.cpd,c.zonename,c.cpd_status,d.sitename FROM ".$stats." AS a 
            LEFT JOIN lz_adzone_copy AS b ON a.day=b.cpd_day AND a.adz_id=b.adz_id 
            LEFT JOIN lz_adzone AS c ON a.adz_id=c.adz_id
            LEFT JOIN lz_site AS d ON a.site_id=d.site_id WHERE a.uid=? AND d.site_id=? AND a.day>=? AND a.day<=? GROUP BY a.day ".$type." 
            ORDER BY a.day DESC";
        $res = Db::query($sql,[$param['uid'],$param['site_id'],$param['startday'],$param['endday']]);
        return $res;
    }

    /**
     * 获取上传图片的服务器地址
     */
    public function getImgService()
    {
        $sql = 'SELECT img_server FROM lz_setting';
        $res = Db::query($sql);
        return empty($res) ? '' : $res['0'];
    }

    /**
     * 得到该天下此广告位一共有几条数据
     */
    public function getAdzCount($adz_id,$day)
    {
        $sql = 'SELECT count(adz_id) as count FROM lz_stats_new WHERE adz_id=? AND day=?';
        $res = Db::query($sql,[$adz_id,$day]);
        return $res[0];
    }

    /**
     *  查询站长付款记录
     */
    public function recordLog($uid,$params)
    {
        $sql = 'SELECT uid,sum(xmoney) as money,day FROM lz_paylog  WHERE  uid=? AND status=? and xmoney!="" ORDER BY id DESC';
        $res = Db::query($sql,[$uid,3]);
        return $res;
    }

    /**
     *  查询站长财务所有结算记录
     */
    public function recordLogall($uid,$params)
    {
        $sql = 'SELECT uid,money,day,payinfo FROM lz_paylog  WHERE  uid=?  AND status=? ORDER BY id DESC';
        $res = Db::query($sql,[$uid,1]);
        return $res;
    }

    /**
     *  得到站长未结算记录
     */
    public function recordnoPay($uid,$params)
    {
        $sql = 'SELECT SUM(sumpay) as sumpay,uid,day FROM lz_stats_new WHERE  uid=? AND day>=? AND day<=?';
        $res = Db::query($sql,[$uid,$params['mon'],$params['sun']]);
        return $res;
    }

}
