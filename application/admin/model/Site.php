<?php
/**
 * 网站
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2
 *------------------------------------------------------
 */
namespace app\admin\model;
use think\Db;
use think\Model;


class Site extends Model
{

    /**
     * 查询网站表
     */
    public function siteList($offset,$count,$param)
    {
        $sql = 'SELECT a.site_id,a.uid,a.sitename,a.siteurl,a.https,a.class_id,a.star,a.status,a.web_deduction,a.adv_deduction,b.uid AS userid,b.username,c.class_id,c.class_name
            FROM lz_site AS a LEFT JOIN lz_users AS b ON a.uid=b.uid LEFT JOIN lz_classes AS c ON a.class_id = c.class_id ';
        if(empty($param['search'])){
            if($param['status'] == 'site_all'){
                $sql.='WHERE a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                $res = Db::query($sql,[$offset,$count]);
            }else{
                $sql.=' WHERE a.status=? AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                $res = Db::query($sql,[$param['status'],$offset,$count]);
            }
        }else{
            $sele = $param['search'];
            if($param['status'] == 'site_all'){
                if($param['index'] == 'sitename'){
                    $sql.=' WHERE a.sitename like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }elseif ($param['index'] == 'username') {
                    $sql.=' WHERE b.username like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }elseif ($param['index'] == 'siteurl') {
                    $sql.=' WHERE a.siteurl like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }else{
                    $sql.=' WHERE c.class_name like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }
                $res = Db::query($sql,[$offset,$count]);
            }else{
                if($param['index'] == 'sitename'){
                    $sql.=' WHERE a.status=? AND a.sitename like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }elseif ($param['index'] == 'username') {
                    $sql.=' WHERE a.status=? AND b.username like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }elseif ($param['index'] == 'siteurl') {
                    $sql.=' WHERE a.status=? AND a.siteurl like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }else{
                    $sql.=' WHERE a.status=? AND c.class_name like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC  Limit ?,? ';
                }
                $res = Db::query($sql,[$param['status'],$offset,$count]);
            }
        }
        return $res;
    }

	/**
     * 查询网站表个数
     */
    public function siteListCount($param)
    {
        $sql = 'SELECT a.site_id,a.uid,a.sitename,a.siteurl,a.https,a.class_id,a.star,a.status,a.web_deduction,a.adv_deduction,b.uid AS userid,b.username,c.class_id,c.class_name
            FROM lz_site AS a LEFT JOIN lz_users AS b ON a.uid=b.uid LEFT JOIN lz_classes AS c ON a.class_id = c.class_id ';
        if(empty($param['search'])){
            if($param['status'] == 'site_all'){
                $sql.='WHERE a.uid = b.uid ORDER BY a.site_id DESC ';
                $res = Db::query($sql);
            }else{
                $sql.=' WHERE a.status=? AND a.uid = b.uid ORDER BY a.site_id DESC';
                $res = Db::query($sql,[$param['status']]);
            }
        }else{
            $sele = $param['search'];
            if($param['status'] == 'site_all'){
                if($param['index'] == 'sitename'){
                    $sql.=' WHERE a.sitename like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }elseif ($param['index'] == 'username') {
                    $sql.=' WHERE b.username like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }elseif ($param['index'] == 'siteurl') {
                    $sql.=' WHERE a.siteurl like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }else{
                    $sql.=' WHERE c.class_name like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }
                $res = Db::query($sql);
            }else{
                if($param['index'] == 'sitename'){
                    $sql.=' WHERE a.status=? AND a.sitename like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }elseif ($param['index'] == 'username') {
                    $sql.=' WHERE a.status=? AND b.username like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }elseif ($param['index'] == 'siteurl') {
                    $sql.=' WHERE a.status=? AND a.siteurl like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }else{
                    $sql.=' WHERE a.status=? AND c.class_name like "%'.$sele.'%" AND a.uid = b.uid ORDER BY a.site_id DESC';
                }
                $res = Db::query($sql,[$param['status']]);
            }
        }
        return count($res);
    }

    /**
     * 查询网站表
     */
    public function siteViews($day)
    {
        if($day == date('Y-m-d')){
            $stats = 'lz_stats_log';
        }else{
            $stats = 'lz_stats_new';
        }
        $sql = 'SELECT site_id,SUM(views) as views FROM  '.$stats.' WHERE day=? GROUP BY site_id';
        $res = Db::query($sql,[$day]);
        return $res;
    }

    /**
     * 网站列表个数
     */
    public function siteCount($param)
    {
        // 查询网站表
        $sql = 'SELECT a.uid,a.class_id,b.username,a.status,a.sitename,a.siteurl,c.class_name FROM lz_site AS a LEFT JOIN lz_users AS b ON a.uid = b.uid  
            LEFT JOIN lz_classes AS c ON a.class_id = c.class_id ';
        if(empty($param['search'])){
            if($param['status'] == 'site_all'){
                $sql.= ' WHERE a.uid = b.uid';
                $res = db::query($sql);
            }else{
                $sql.= 'WHERE a.uid = b.uid AND a.status=?';
                $res = db::query($sql,[$param['status']]);
            }
        }else{
            if($param['status'] == 'site_all'){
                if($param['index'] == 'sitename'){
                    $sql.=' WHERE a.sitename=?';
                }elseif ($param['index'] == 'username') {
                    $sql.=' WHERE b.username=? ';
                }elseif ($param['index'] == 'siteurl') {
                    $sql.=' WHERE a.siteurl=?  ';
                }else{
                    $sql.=' WHERE c.class_name=? ';
                }
                $res = Db::query($sql,[$param['search']]);
            }else{
                if($param['index'] == 'sitename'){
                    $sql.=' WHERE a.sitename=? AND a.status=?';
                }elseif ($param['index'] == 'username') {
                    $sql.=' WHERE b.username=? AND a.status=?';
                }elseif ($param['index'] == 'siteurl') {
                    $sql.=' WHERE a.siteurl=?  AND a.status=?';
                }else{
                    $sql.=' WHERE c.class_name=?  AND a.status=?';
                }
                $res = Db::query($sql,[$param['search'],$param['status']]);
            }
        }
        $res = count($res);
        return $res;
    }

    /**
     *  查询 会员表
     */
    public function userOne($username){
        $data = array(
            'username'=>$username,
            'type'     =>1,
        );
        $res = Db::name('users')->where($data)->find();
        return $res;
    }

    /**
     * 查询 分类表
     */
    public function classList(){
        $res = Db::query('SELECT * FROM lz_classes WHERE type=1');
        return $res;
    }
    /**
     * 添加网站 add  网站表
     */
    public function siteAdd($data){
        $res = Db::name('site')->insert($data);
        return $res;
    }
    /**
     * 网站列表 修改 状态
     */
    public function siteEditStatus($id,$status){
        $map = array(
            'site_id'=>$id,
        );
        $data = array(
            'status'=>$status,
        );
        $res = Db::name('site')->where($map)->update($data);
        return $res;
    }

    /**
     * 网站列表下更新扣量
     */
    public function deduction($data)
    {
        $map = array(
            'site_id'=>$data['site_id'],
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     *  查询网站表 find
     */
    public function siteOne($id){
        $sql = 'SELECT a.site_id,a.uid,a.sitename,a.siteurl,a.https,a.class_id,a.web_deduction,a.adv_deduction,a.add_time,a.beian,a.star,a.site_cnzz_id,b.uid AS userid,b.username,c.class_id,c.class_name
        FROM lz_site AS a LEFT JOIN lz_users AS b ON  a.uid=b.uid LEFT JOIN lz_classes AS c ON a.class_id = c.class_id WHERE a.uid =b.uid AND a.site_id = '.$id;
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     *  网站列表   编辑
     */
    public function siteEdit($id,$data){
        $map = array(
            'site_id'=>$id,
        );
        $res = Db::name('site')->where($map)->update($data);
        return $res;
    }

    /**
     * 网站列表 删除
     *
     */
    public function delOne($id)
    {
        $map = array(
            'site_id'=>$id,
        );
        $res = Db::name('site')->WHERE($map)->DELETE();
        return $res;
    }

    /**
     * 更新状态
     * param aid 计划id status 状态
     */
    public function updateStatus($adzId,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'adz_id'=>$adzId,
        );
        $res = Db::name('adzone')->where($map)->update($data);
        return $res;
    }

    /**
     *  广告位管理  列表
     */
    public function adzone($offset,$count,$param)
    {
        //拼接投放设备,投放尺寸,投放模式sql
        $system_type = $param['system_type'] == '-1' ? ' AND a.system_type != ?' : ' AND a.system_type = ?';
        $adzsize = $param['adzsize'] == '-1' ? ' AND concat(a.width,"*",a.height) != ?' : ' AND concat(a.width,"*",a.height) = ?';
        $adtpl_id = $param['adtpl_id'] == '-1' ? ' AND a.adtpl_id != ?' : ' AND a.adtpl_id = ?';
        //判断是否是从广告样式跳转过来的
        if(!empty($param['style_id'])){
            $addition = ' AND a.adstyle_id = "'.$param['style_id'].'" ';
        }else{
            $addition = '';
        }
        $param['adzone'] = empty($param['adzone'])?'uid':$param['adzone'];
        //拼接的sql
        $sqlQuery = $addition.$system_type.$adzsize.$adtpl_id;

        $sql = 'SELECT a.adz_id,a.uid,a.status,a.zonename,a.adtpl_id,a.adstyle_id,a.plantype,a.width,a.height,a.cpd_status,a.cpd,
            a.cpd_startday,a.cpd_endday,a.system_type,b.uid AS userid,b.username,c.tpl_id,c.tplname,d.style_id,f.cpd AS cp FROM lz_adzone AS a
            LEFT JOIN lz_users AS b ON a.uid =b.uid LEFT JOIN lz_admode AS c ON a.adtpl_id = c.tpl_id LEFT JOIN
            lz_adstyle AS d ON a.adstyle_id =d.style_id LEFT JOIN lz_adzone_copy AS f ON a.adz_id=f.adz_id  ';
        
        if(!empty($param['search'])){
            if($param['status'] == 'adzone_all'){
                if($param['adzone'] == 'uid'){
                    $sql.=' WHERE a.uid =b.uid  AND b.uid=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['search'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif($param['adzone'] == 'username'){
                    $sele = $param['search'];
                    $sql.=' WHERE a.uid =b.uid AND b.username like "%'.$sele.'%" '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif ($param['adzone'] == 'adz_id') {
                    $sql.=' WHERE a.uid =b.uid AND a.adz_id=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['search'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif($param['adzone'] == 'zonename'){
                    $sele = $param['search'];
                    $sql.=' WHERE a.uid =b.uid AND a.zonename  like "%'.$sele.'%" '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif($param['adzone'] == 'size'){
                    $sql.=' WHERE a.uid =b.uid AND concat(a.width,"*",a.height)=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['search'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }
            }else{
                if($param['adzone'] == 'uid'){
                    $sql.=' WHERE a.uid =b.uid AND b.uid=? AND a.status=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['search'],$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif($param['adzone'] == 'username'){
                    $sele = $param['search'];
                    $sql.=' WHERE a.uid =b.uid AND b.username like "%'.$sele.'%" AND a.status=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif ($param['adzone'] == 'adz_id') {
                    $sql.=' WHERE a.uid =b.uid AND a.adz_id=? AND a.status=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['search'],$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif($param['adzone'] == 'zonename'){
                    $sele = $param['search'];
                    $sql.=' WHERE a.uid =b.uid AND a.zonename like "%'.$sele.'%" AND a.status=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }elseif($param['adzone'] == 'size'){
                    $sql.=' WHERE a.uid =b.uid AND concat(a.width,"*",a.height)=? AND a.status=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                    $res = Db::query($sql,[$param['search'],$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
                }

            }
        }else{
            if($param['status'] == 'adzone_all'){
                $sql.=' WHERE a.uid =b.uid AND a.adtpl_id = c.tpl_id '.$addition.' '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                $res = Db::query($sql,[$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
            }else{
                $sql.=' WHERE a.uid =b.uid AND a.adtpl_id = c.tpl_id AND a.status=? '.$sqlQuery.' GROUP BY a.adz_id ORDER BY a.adz_id DESC  Limit ?,? ';
                $res = Db::query($sql,[$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id'],$offset,$count]);
            }
        }
        return $res;
    }

    /**
     * 广告位包天查询
     */
    public function adzCopyList($adz_id)
    {
        $sql = 'SELECT cpd FROM lz_adzone_copy WHERE adz_id=?';
        $res = Db::query($sql,[$adz_id]);
        return $res;
    }
    /**
     * 广告列表  个数
     */
    public function adzoneCount($param)
    {
        //拼接投放设备,投放尺寸,投放模式sql
        $system_type = $param['system_type'] == '-1' ? ' AND a.system_type != ?' : ' AND a.system_type = ?';
        $adzsize = $param['adzsize'] == '-1' ? ' AND concat(a.width,"*",a.height) != ?' : ' AND concat(a.width,"*",a.height) = ?';
        $adtpl_id = $param['adtpl_id'] == '-1' ? ' AND a.adtpl_id != ?' : ' AND a.adtpl_id = ?';
        //判断是否是从广告样式跳转过来的
        if(!empty($param['style_id'])){
            $addition = ' AND a.adstyle_id = "'.$param['style_id'].'" ';
        }else{
            $addition = '';
        }
        //拼接的sql
        $sqlQuery = $addition.$system_type.$adzsize.$adtpl_id;

        $sql = 'SELECT a.adz_id,a.status,a.zonename,a.width,a.height,b.uid AS userid,b.username,concat(a.width,"*",a.height) AS size 
            FROM lz_adzone AS a LEFT JOIN lz_users AS b ON a.uid = b.uid ';
        $param['adzone'] = empty($param['adzone'])?'uid':$param['adzone'];
        if(!empty($param['search'])){
            if($param['status'] == 'adzone_all'){
                if ($param['adzone'] == 'uid') {
                    $sql.= 'WHERE b.uid=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'username') {
                    $sql.= 'WHERE b.username=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'adz_id') {
                    $sql.= 'WHERE a.adz_id=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'zonename') {
                    $sql.= 'WHERE a.zonename=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'size') {
                    $sql.= 'WHERE concat(a.width,"*",a.height)=? AND username !=""'.$sqlQuery;
                }
                $res = Db::query($sql,[$param['search'],$param['system_type'],$param['adzsize'],$param['adtpl_id']]);
            }else{
                if ($param['adzone'] == 'uid') {
                    $sql.= 'WHERE b.uid=? AND a.status=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'username') {
                    $sql.= 'WHERE b.username=? AND a.status=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'adz_id') {
                    $sql.= 'WHERE a.adz_id=? AND a.status=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'zonename') {
                    $sql.= 'WHERE a.zonename=? AND a.status=? AND username !=""'.$sqlQuery;
                }elseif ($param['adzone'] == 'size') {
                    $sql.= 'WHERE concat(a.width,"*",a.height)=? AND a.status=? AND username !=""'.$sqlQuery;
                }
                $res = Db::query($sql,[$param['search'],$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id']]);
            }
        }else{
            if($param['status'] !== 'adzone_all'){
                $sql.= 'WHERE a.status=? AND username !=""'.$sqlQuery;
                $res = Db::query($sql,[$param['status'],$param['system_type'],$param['adzsize'],$param['adtpl_id']]);
            }else{
                $sql.= 'WHERE username !="" '.$sqlQuery;
                $res = Db::query($sql,[$param['system_type'],$param['adzsize'],$param['adtpl_id']]);

            }
        }
        $res = count($res);
        return $res;
    }



    /**
     * 广告位副表  个数
     */
    public function adzoneCopyCount($id)
    {
        $sql = 'SELECT count(adz_id)as count FROM lz_adzone_copy WHERE adz_id=?';
        $res = Db::query($sql,[$id]);
        $res = empty($res[0]['count']) ? '' : $res[0]['count'];
        return $res;
    }

    /**
     * 查询广告位副表
     */
    public function adzoneCopy($id,$offset,$count)
    {
        $sql = 'SELECT id,adz_id,cpd,cpd_day,zonename FROM lz_adzone_copy WHERE adz_id=? ORDER BY cpd_day DESC LIMIT ?,? ';
        $res = Db::query($sql,[$id,$offset,$count]);
        return $res;
    }

    /**
     * 查询广告位副表
     */
    public function getCpdMoney($id)
    {
        $sql = 'SELECT a.id,a.adz_id,a.cpd,a.cpd_day,b.zonename FROM lz_adzone_copy AS a LEFT JOIN lz_adzone as b ON a.adz_id=b.adz_id WHERE id=? ';
        $res = Db::query($sql,[$id]);
        $res[0] = empty($res[0]) ? '' : $res[0];
        return $res[0];
    }

    /**
     * 查询广告位副表
     */
    public function updateCpdMoney($params)
    {
        $sql = 'UPDATE lz_adzone_copy SET cpd=? WHERE id=? ';
        $res = Db::execute($sql,[$params['cpd'],$params['id']]);
        return $res;
    }

    /**
     * 网站列表 删除
     *
     */
    public function adzoneDel($id)
    {
        $map = array(
            'adz_id'=>$id,
        );
        $res = Db::name('adzone')->WHERE($map)->DELETE();
        return $res;
    }

    /**
     *  默认 修改显示
     */
    public function adzoneOne($id)
    {
        $sql = 'SELECT a.uid,a.adz_id,a.zonename,a.checkadz,a.clickhight,a.star,a.plantype,a.adstyle_id,a.adtpl_id,a.class_id,a.width,a.height,a.cpd_status,a.false_close,a.point_close,a.type,a.adz_type,a.adz_ua,
        a.cpd,a.cpd_startday,a.cpd_endday,a.web_deduction,a.adv_deduction,a.plan_class_allow,a.system_type,a.plan_limit,a.plan_check,b.plan_type FROM lz_adzone AS a LEFT JOIN lz_plan AS b ON a.plantype = b.plan_type
        WHERE adz_id='.$id;
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     *  默认 修改显示
     */
    public function adzoneDay($id)
    {
        $sql = 'SELECT MIN(cpd_day) AS cpd_day1,MAX(cpd_day) AS cpd_day2 from lz_adzone_copy WHERE adz_id='.$id;
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     * 广告模型列表
     */
    public function admodeOne($id)
    {
        $sql = 'SELECT tpl_id,tplname FROM lz_admode WHERE tpl_id='.$id;
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     * 获取广告样式
     */
    public function getStyle($tpl_id)
    {
        $sql = 'SELECT style_id,tpl_id,stylename FROM lz_adstyle WHERE tpl_id=? AND status=1';
        $res = Db::query($sql,[$tpl_id]);
        return $res;
    }

    /**
     * 获取广告位分类
     */
    public function getClass($tpl_id)
    {
        $sql = 'SELECT class_id,class_name FROM lz_classes';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 广告计划分类查询
     */
    public function adzplanclass()
    {
        $one =Db::query('SELECT * FROM lz_classes WHERE type=2 ORDER BY class_id DESC');
        return $one;
    }

    /**
     * 广告位  修改
     */
    public function adzoneEdit($id,$data){
        $map = array(
            'adz_id'=>$id,
        );
        $res = Db::name('adzone')->where($map)->update($data);
        return $res;
    }

    /**
     * 广告位  修改
     */
    public function delCpd($id,$data)
    {
        $sql = 'DELETE FROM lz_adzone_copy WHERE adz_id=? AND cpd_day>? ';
        $res = Db::execute($sql,[$id,$data]);
        return $res;
    }

    /**
     * 广告位副表  修改
     */
    public function adzoneCopyInsert($arr){
        $res = Db::name('adzone_copy')->insert($arr);
        return $res;
    }

    /**
     * 查询站长余额
     */
    public function selectWebMoney($id)
    {
        $sql = 'SELECT a.uid,b.money FROM lz_adzone AS a LEFT JOIN lz_users AS b ON a.uid=b.uid WHERE a.adz_id=?';
        $res = Db::query($sql,[$id]);
        if (empty($res)) {
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     * 查询站长余额
     */
    public function selectlastday($id)
    {
        $sql = 'SELECT cpd_day FROM lz_adzone_copy WHERE adz_id=?';
        $res = Db::query($sql,[$id]);
        return $res;
    }

    /**
     * 查询站长余额
     */
    public function getLastMoney($id,$value)
    {
        $sql = 'SELECT SUM(sumpay) as sumpay FROM lz_stats_new WHERE adz_id=? AND day=?';
        $res = Db::query($sql,[$id,$value]);
        return $res;
    }

    /**
     * 更新站长余额
     */
    public function updateWebMoney($id,$money)
    {
        $map = array(
            'uid'=>$id,
        );
        $res = Db::name('users')->where($map)->update($money);
        return $res;
    }

    /**
     * 批量删除广告位副表
     */
    public function del($id)
    {
        $map = array(
            'adz_id'=>$id[0],
            'cpd_day'=>$id[1],
        );
        $res = Db::name('adzone_copy')->where($map)->DELETE();
        return $res;
    }

    /**
     * 批量删除广告位副表
     */
    public function delCpdDay($id)
    {
        $map = array(
            'id'=>$id,
        );
        $res = Db::name('adzone_copy')->where($map)->DELETE();
        return $res;
    }

    /**
     * 获取所有的广告模式
     */
    public function getAdtype()
    {
        $sql = 'SELECT tpl_id,tplname FROM lz_admode WHERE status=1';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 查询广告位信息
     */
    public function getone($id)
    {
        $sql = 'SELECT adz_id,uid,zonename,system_type FROM lz_adzone WHERE adz_id=?';
        $res = Db::query($sql,[$id]);
        $res = empty($res) ? array() : $res[0];
        return $res;
    }

    /**
     * remindingAdd $pid $params
     * param data
     */
    public function remindingAdd($remindData)
    {
        $data = array();
        $data['adz_id'] = empty($remindData['adz_id']) ? '' : $remindData['adz_id'];
        $data['uid'] = empty($remindData['uid']) ? '' : $remindData['uid'];
        $data['adz_name'] = empty($remindData['zonename']) ? '' : $remindData['zonename'];
        $data['terminal'] = empty($remindData['terminal']) ? '' : $remindData['terminal'];
        $data['type'] = '3';
        $data['ctime'] = time();

        $res = Db::name('reminding')->insert($data);
        return $res;
    }

    /**
     *  查询产品分类
     */
    public function getProType()
    {
        $sql = 'SELECT id,type_name FROM lz_pd_type';
        $res = DB::query($sql);
        return $res;
    }
    /**
     *  城市池
     */
    public function getCity()
    {
        $sql = 'SELECT id,city,city_name FROM lz_city_pool';
        return DB::query($sql);
    }
    /**
     * 查询点弹池中的激活的连接
     */
    public function getUrl($params){
        $sql = 'SELECT id,url_name,url FROM lz_url_pool WHERE  type=? AND status=? AND url_name LIKE "%'.$params['url_name'].'%"';
        return DB::query($sql,[$params['type'],1]);
    }

    /**
     * 查询广告位附加定向功能的规则名称
     */
    public function getAdzSite($id)
    {
        $sql = 'select id,adz_id,rule_name from lz_adzone_rule where adz_id=?';
        return DB::query($sql,[$id]);
    }
    /**
     * 查询广告位附加定向设置
     */
    public function getAdzRule($id)
    {
        $res = Db::name('adzone_rule')->WHERE($id)->FIND();
        return $res;
    }

    /**
     * 插入附加定向功能的数据
     */
    public function adzSiteInsert($data)
    {
        $res = Db::name('adzone_rule')->insert($data);
        return $res;
    }


    /**
     * 更新附加定向设置功能数据
     */
    public function adzSiteUpdate($where,$data)
    {
        $res = Db::name('adzone_rule')->where($where)->update($data);
        return $res;
    }

    /**
     *  编辑查询链接池
     */
    public function getAuleUrl($param,$type)
    {
        $sql = "SELECT id,url_name,url FROM lz_url_pool WHERE id IN(".$param.") AND type=?";
        $res = DB::query($sql,[$type]);
        return $res;
    }

    /**
     *  查询需要复制的广告位数据
     */
    public function getCopy($param)
    {
        $sql = 'SELECT rule_name,wake_on,wake_pro,wake_num,jp_on,jp_type,jp_ip,point_site,point_time,point_num,point_url,jump_time,jump_url,js_on,js_check,map_rule,hour_rule FROM lz_adzone_rule WHERE adz_id=?';
        return DB::query($sql,[$param['copy_id']]);
    }

    /**
     *  查询出该站长所有的手机型号
     */
    public function phoneModel($param)
    {
        $sql = 'SELECT model FROM lz_area_ip_stat WHERE uid=? and type = ?';
        return DB::query($sql,[$param['uid'],3]);
    }

    /***
     *  查询广告位规则列表
     */
    public function ruleList($param)
    {
        if (!empty($param['search']) && $param['index'] == 'jump_url' || $param['index'] == 'point_url') {
            $param['search'] = $this->_getUrlId($param);
            if (!$param['search']){return array();}
        }
        $sql = 'SELECT id,adz_id,rule_name,jp_port,jp_on,jp_type,jp_ip,map_rule,hour_rule,status FROM lz_adzone_rule';
        if (empty($param['search'])){
            if ($param['status'] != 2 ){
                $sql.= ' WHERE status=?';
                return DB::query($sql,[$param['status']]);
            }else{
                return DB::query($sql);
            }
        }else{
            $sele = trim($param['search']);
            if ($param['index'] == 'adz_id'){
                $sql.= ' WHERE adz_id="'.$sele.'"';
            }elseif($param['index'] == 'jump_url'){
                $sql.= ' WHERE jp_type=1 AND jump_url REGEXP ' . $sele . '';
            }elseif($param['index'] == 'point_url'){
                $sql.= ' WHERE jp_type=0 AND point_url REGEXP ' . $sele . '';
            }
            if ($param['status'] != 2){
                $sql.= ' AND status='.$param['status'].'';
            }
            return DB::query($sql);
        }
    }

    /**
     *  链接查询
     */
    private function _getUrlId($param)
    {
        $sele = trim($param['search']);
        $type = 1;
        if ($param['index'] == 'jump_url'){
            $type = 2;
        }
        $map = array(
            'type'=>$type,
            'status'=>1,
        );
        $where['url'] = array('like','%'.$sele.'%');
        $res = Db::name('url_pool')->WHERE($map)->where($where)->value('id');
        return $res;
    }


    /**
     *  删除规则
     */
    public function ruleDel($id)
    {
        $map = array(
            'id'=>$id,
        );
        $res = Db::name('adzone_rule')->WHERE($map)->DELETE();
        return $res;
    }

    /**
     * 更新规则状态
     */
    public function ruleStatus($params)
    {
        $data = array(
            'status' => $params['status']
        );
        $map = array(
            'id'=>$params['id'],
        );
        $res = Db::name('adzone_rule')->where($map)->update($data);
        return $res;
    }
}
