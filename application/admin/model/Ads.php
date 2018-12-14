<?php
/**
 * 广告表
 * date   2016-6-2
 */
namespace app\admin\model;
use think\Model;
use think\Db;

class Ads extends Model
{    

    /**
     * 所有广告列表
     */
    public function adLst($offset,$count,$where = '',$params)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,a.web_deduction,a.adv_deduction,b.plan_name,
        a.pid,b.plan_type,b.status AS plan_status,a.width,a.height,a.imageurl,a.url,a.files,a.adinfo,c.tplname,c.customspecs,
        e.price,e.gradation,e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv
        FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id LEFT JOIN
        lz_users as d ON b.uid=d.uid LEFT JOIN lz_plan_price as e ON a.tc_id=e.id';

        if(!empty($where)) { 
            if(!empty($params['search'])){
                $sele = trim($params['search']);
                if($params['ads'] == 'username'){
                    $sort = 'd.'.$params['ads'];
                }elseif ($params['ads'] == 'tplname') {
                    $sort = 'c.'.$params['ads'];
                }else{
                    $sort = 'a.'.$params['ads'];
                }
                $sql.= ' WHERE b.plan_type=?  AND '.$sort.' like "%'.$sele.'%" ORDER BY a.ad_id DESC LIMIT ?,? ';
                $res = Db::query($sql,[$where,$offset,$count]); 
            }else{
                $sql.= ' WHERE b.plan_type=? ORDER BY a.ad_id DESC LIMIT ?,? ';
                $res = Db::query($sql,[$where,$offset,$count]); 
            }
        }else{
            if(!empty($params['search'])){
                $sele = trim($params['search']);
                if($params['ads'] == 'username'){
                    $sort = 'd.'.$params['ads'];
                }elseif ($params['ads'] == 'tplname') {
                    $sort = 'c.'.$params['ads'];
                }else{
                    $sort = 'a.'.$params['ads'];
                }
                    $sql.= ' WHERE '.$sort.' like "%'.$sele.'%" ORDER BY a.ad_id DESC  LIMIT ?,? ';
                    $res = Db::query($sql,[$offset,$count]); 
            }else{
                $sql.=' ORDER BY a.ad_id DESC  LIMIT ?,? ';
                $res = Db::query($sql,[$offset,$count]); 
            }
        }
        return $res;
    }

     /**
      * 锁定的广告列表
      */
    public function adLstOne($offset,$count,$params)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,b.plan_name,
        a.pid,b.plan_type,b.status AS plan_status,a.width,a.height,a.imageurl,a.files,a.adinfo,c.tplname,c.customspecs,
        e.price,e.gradation,e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv
        FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id LEFT JOIN
        lz_users as d ON b.uid=d.uid LEFT JOIN lz_plan_price as e ON a.tc_id=e.id ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['ads'] == 'username'){
                $sort = 'd.'.$params['ads'];
            }elseif ($params['ads'] == 'tplname') {
                $sort = 'c.'.$params['ads'];
            }else{
                $sort = 'a.'.$params['ads'];
            }
            $sql.= ' WHERE a.status=? AND '.$sort.' like "%'.$sele.'%" ORDER BY a.ad_id DESC LIMIT ?,? ';
            $res = Db::query($sql,[0,$offset,$count]);
        }else{
            $sql.= ' WHERE a.status=? ORDER BY a.ad_id DESC LIMIT ?,?';
            $res = Db::query($sql,[0,$offset,$count]);
        }
        return $res;
    }

     /**
      * 活动的广告列表
      */
    public function adLstTwo($offset,$count,$params)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,b.plan_name,
        a.pid,b.plan_type,b.status AS plan_status,a.width,a.height,a.imageurl,a.files,a.adinfo,c.tplname,c.customspecs,
        e.price,e.gradation,e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv
        FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id LEFT JOIN
        lz_users as d ON b.uid=d.uid LEFT JOIN lz_plan_price as e ON a.tc_id=e.id ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['ads'] == 'username'){
                $sort = 'd.'.$params['ads'];
            }elseif ($params['ads'] == 'tplname') {
                $sort = 'c.'.$params['ads'];
            }else{
                $sort = 'a.'.$params['ads'];
            }
            $sql.= ' WHERE a.status=? AND '.$sort.' like "%'.$sele.'%" ORDER BY a.ad_id DESC LIMIT ?,? ';
            $res = Db::query($sql,[1,$offset,$count]);
        }else{
            $sql.= ' WHERE a.status=? ORDER BY a.ad_id DESC LIMIT ?,?';
            $res = Db::query($sql,[1,$offset,$count]);
        }
        return $res;
    }

      /**
      * 待审的广告列表
      */
    public function adLstThree($offset,$count,$params)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,b.plan_name,
        a.pid,b.plan_type,b.status AS plan_status,a.width,a.height,a.imageurl,a.files,a.adinfo,c.tplname,c.customspecs,
        e.price,e.gradation,e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv
        FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id LEFT JOIN
        lz_users as d ON b.uid=d.uid LEFT JOIN lz_plan_price as e ON a.tc_id=e.id  ';
        if(!empty($params['search'])){
            $sele = trim($params['search']);
            if($params['ads'] == 'username'){
                $sort = 'd.'.$params['ads'];
            }elseif ($params['ads'] == 'tplname') {
                $sort = 'c.'.$params['ads'];
            }else{
                $sort = 'a.'.$params['ads'];
            }
            $sql.= ' WHERE a.status=? AND '.$sort.' like "%'.$sele.'%" ORDER BY a.ad_id DESC LIMIT ?,? ';
            $res = Db::query($sql,[2,$offset,$count]);
        }else{
            $sql.= ' WHERE a.status=? ORDER BY a.ad_id DESC LIMIT ?,?';
            $res = Db::query($sql,[2,$offset,$count]);
        }
        return $res;
    }

    /**
     * 获取上传图片的服务器地址
     */
    public function getImgService()
    {
        $sql = 'SELECT img_server FROM lz_setting';
        $res = Db::query($sql);
        $res = empty($res) ? '' : $res['0'];
        return $res;
    }

    /**
     * 所有广告页码统计
     */
    public function adLstCount($where = '',$params)
    {
        $sql = 'SELECT count(a.ad_id)as count,a.adname,a.tpl_id,d.username,a.uid,c.tplname  FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid 
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id LEFT JOIN lz_users as d ON a.uid=d.uid ';
        if(!empty($where)) { 
            if(!empty($params['search'])){
                if($params['ads'] == 'username'){
                    $sort = 'd.'.$params['ads'];
                }elseif ($params['ads'] == 'tplname') {
                    $sort = 'c.'.$params['ads'];
                }else{
                    $sort = 'a.'.$params['ads'];
                }
                $sql.= ' WHERE b.plan_type=?  AND '.$sort.'=? ';
                $res = Db::query($sql,[$where,$params['search']]); 
            }else{
                $sql.= ' WHERE b.plan_type=?';
                $res = Db::query($sql,[$where,$params['search']]); 
            }
                  
        }else{
            if(!empty($params['search'])){
                if($params['ads'] == 'username'){
                    $sort = 'd.'.$params['ads'];
                }elseif ($params['ads'] == 'tplname') {
                    $sort = 'c.'.$params['ads'];
                }else{
                    $sort = 'a.'.$params['ads'];
                }
                    $sql.= ' WHERE '.$sort.'=? ';
                    $res = Db::query($sql,[$params['search']]); 
            }else{
                $res = Db::query($sql); 
            }
        }
        return $res[0]['count'];
    }

     /**
      * 锁定广告页码统计
      */
    public function adsLock($params)
    {
        $sql = 'SELECT count(a.ad_id)as count,a.adname,a.tpl_id,b.username,a.uid,c.tplname  FROM lz_ads as a LEFT JOIN lz_users as b ON a.uid=b.uid 
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id ';
        if(!empty($params['search'])){
            if($params['ads'] == 'username'){
                $sort = 'b.'.$params['ads'];
            }elseif ($params['ads'] == 'tplname') {
                $sort = 'c.'.$params['ads'];
            }else{
                $sort = 'a.'.$params['ads'];
            }
            $sql.= ' WHERE a.status=? AND '.$sort.'=? ';
            $res = Db::query($sql,[0,$params['search']]);
        }else{
            $sql.= ' WHERE a.status=? ';
            $res = Db::query($sql,[0]);
        }
        return $res[0]['count'];
    }

     /**
      * 活动广告页码统计
      */
    public function adsAtv($params)
    {
        $sql = 'SELECT count(a.ad_id)as count,a.adname,a.tpl_id,b.username,a.uid,c.tplname  FROM lz_ads as a LEFT JOIN lz_users as b ON a.uid=b.uid 
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id ';
        if(!empty($params['search'])){
            if($params['ads'] == 'username'){
                $sort = 'b.'.$params['ads'];
            }elseif ($params['ads'] == 'tplname') {
                $sort = 'c.'.$params['ads'];
            }else{
                $sort = 'a.'.$params['ads'];
            }
            $sql.= ' WHERE a.status=? AND '.$sort.'=? ';
            $res = Db::query($sql,[1,$params['search']]);
        }else{
            $sql.= ' WHERE a.status=? ';
            $res = Db::query($sql,[1]);
        }
        return $res[0]['count'];
    }

     /**
      * 待审广告页码统计
      */
    public function adsPend($params)
    {
        $sql = 'SELECT count(a.ad_id)as count,a.adname,a.tpl_id,b.username,a.uid,c.tplname  FROM lz_ads as a LEFT JOIN lz_users as b ON a.uid=b.uid 
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id ';
        if(!empty($params['search'])){
            if($params['ads'] == 'username'){
                $sort = 'b.'.$params['ads'];
            }elseif ($params['ads'] == 'tplname') {
                $sort = 'c.'.$params['ads'];
            }else{
                $sort = 'a.'.$params['ads'];
            }
            $sql.= ' WHERE a.status=? AND '.$sort.'=? ';
            $res = Db::query($sql,[2,$params['search']]);
        }else{
            $sql.= ' WHERE a.status=? ';
            $res = Db::query($sql,[2]);
        }
        return $res[0]['count'];
    }

    /**
     * 计划管理下查看广告
     */
    public function adPlanLst($pid)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,d.username,d.uid,a.priority,a.status,b.plan_name,
        a.pid,a.width,a.height,a.imageurl,a.url,a.files,a.adinfo,b.plan_type,c.tplname,c.customspecs,e.price,e.gradation,
        e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv FROM lz_ads as a LEFT JOIN lz_plan as b
        ON a.pid=b.pid LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id
        LEFT JOIN lz_users as d ON b.uid=d.uid LEFT JOIN lz_plan_price as e ON a.tc_id=e.id WHERE a.pid=?';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 添加广告
     */
    public function addOne($data)
    {
        $this->pid = $data['ads_pid'];
        $this->uid = $data['uid'];
        $this->adname = $data['ad_name'];
        $this->tpl_id = $data['adtpl_id'];
        $this->tc_id = $data['tc_id'];
        $this->files = empty($data['file'])?'':$data['file'];
        $this->imageurl = empty($data['imageurl'])?'':$data['imageurl'];
        $this->url = empty($data['url'])?'':$data['url'];
        $this->priority = $data['priority'];
        $this->adinfo = $data['adinfo'];
        $this->width = empty($data['width'])?'':$data['width'];
        $this->height = empty($data['height'])?'':$data['height'];
        $this->web_deduction = empty($data['web_deduction'])?0:$data['web_deduction'];
        $this->adv_deduction = empty($data['adv_deduction'])?0:$data['adv_deduction'];
        $this->click_url = empty($data['click_url'])?null:$data['click_url'];
        $this->status=1;
        $this->ctime = time();
        $this->textcheck = $data['textcheck']; //信息流广告
        $res = $this->save();
        return $res;
    }

    /**
     *  新建广告下得到计划列表
     * param data
     */
    public function getAll()
    {
        $sql = 'SELECT pid,plan_name,plan_type,uid FROM lz_plan  ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 添加广告   多广告添加
     */
    public function addOneMore($data)
    {
        $res = $this->saveAll($data);
        return $res;
    }

    /**
     * 添加文字广告
     */
    public function addtextOne($data)
    {
        $this->pid = $data['ads_pid'];
        $this->uid = $data['uid'];
        $this->adname = $data['ad_name'];
        $this->text_chain = $data['text_chain'];
        $this->tpl_id = $data['adtpl_id'];
        $this->tc_id = $data['tc_id'];
        $this->files = empty($data['file'])? '0':$data['file'];
        $this->imageurl = empty($data['imageurl'])? '0':$data['imageurl'];
        $this->url = empty($data['url'])? '0':$data['url'];
        $this->priority = $data['priority'];
        $this->adinfo = $data['adinfo'];
        $this->width = empty($data['width'])? '0':$data['width'];
        $this->height = empty($data['height'])? '0':$data['height'];
        $this->web_deduction = empty($data['web_deduction'])? '0':$data['web_deduction'];
        $this->adv_deduction = empty($data['adv_deduction'])? '0':$data['adv_deduction'];
        $this->click_url = empty($data['click_url'])? null:$data['click_url'];
        $this->status=1;
        $this->ctime = time();
        $res = $this->save();
        return $res;
    }

    /**
     * 批量添加广告
     */
    public function add($data)
    {
        $param['pid'] = $data['ads_pid'];
        $param['uid'] = $data['uid'];
        $param['adname'] = $data['name'];
        $param['tpl_id'] = $data['adtpl_id'];
        $param['tc_id'] = $data['tc_id'];
        $param['files'] = empty($data['files'])?'':$data['files'];
        $param['imageurl'] = empty($data['imageurl'])?'':$data['imageurl'];
        $param['url'] = empty($data['adsUrl'])?'':$data['adsUrl'];
        $param['priority'] = $data['priority'];
        $param['adinfo'] = $data['adinfo'];
        $param['width'] = empty($data['width'])?'':$data['width'];
        $param['height'] = empty($data['height'])?'':$data['height'];
        $param['web_deduction'] = empty($data['web_deduction'])?0:$data['web_deduction'];
        $param['adv_deduction'] = empty($data['adv_deduction'])?0:$data['adv_deduction'];
        $param['status']=1;
        $param['ctime'] = time();
        $res = Db::name('ads')->insert($param);
        return $res;
    }

    /**
     * 编辑广告
     */
    public function editOne($data,$aid)
    {
        $map = array(
            'ad_id'=>$aid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 更新状态
     * param aid 计划id status 状态
     */
    public function updateStatus($aid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'ad_id'=>$aid,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 更新权重
     */
    public function updatePriority($ad_id,$data)
    {
        $map = array(
            'ad_id'=>$ad_id,
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 广告列表下更新扣量
     */
    public function deduction($data)
    {
        $map = array(
            'ad_id'=>$data['ad_id'],
        );
        $res = $this::where($map)->update($data);
        return $res;
    }

    /**
     * 删除计划
     * param pid 计划id
     */
    public function delOne($pid)
    {
        $map = array(
            'ad_id'=>$pid,
        );
        $res = $this::where($map)->delete();
        return $res;
    }

    /**
     * 编辑
     * param aid 广告id
     */
    public function getOne($aid)
    {
        $sql = 'SELECT a.adname,a.ad_id,a.tpl_id,a.uid,a.priority,a.status,a.web_deduction,a.adv_deduction,a.adinfo,b.plan_name,
        a.files,a.imageurl,a.url,a.width,a.height,a.text_chain,a.click_url,a.textcheck,b.plan_type,c.tplname,c.customspecs 
        FROM lz_ads as a LEFT JOIN lz_plan as b ON a.pid=b.pid 
        LEFT JOIN lz_admode as c ON a.tpl_id=c.tpl_id WHERE a.ad_id=? ';
        $res = Db::query($sql,[$aid]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     *  广告类型选择  得到数据
     */
    public function getAdHtml($id){
        $sql = 'SELECT a.tpl_id,a.adtype_id,a.tplname,a.htmlcontrol,b.specs,b.stylename 
        FROM lz_admode as a  LEFT JOIN lz_adstyle as b ON a.tpl_id=b.tpl_id WHERE a.tpl_id=?';
        $res = Db::query($sql,[$id]);
        if(empty($res)){
            return '';
        }else{
            return $res[0];
        }
    }

    /**
     * 广告列表批量删除
     */
    public function delLst($ids)
    {
        Db::startTrans();
        try {
            $Ads = new Ads;
            $res = $Ads::destroy($ids);

            Db::commit();
            if($res>0){
                return 1;
            }else{
                return 0;
            }
        } catch (\PDOException $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    /**
     * 广告筛选器--得到所有广告
     */
    public function getAllAds($ads_sel_click = '')
    {
        $sql = 'SELECT a.ad_id,a.priority from lz_ads as a LEFT JOIN lz_stats_new as b 
        ON a.ad_id=b.ad_id WHERE a.`status`=1 AND b.click_num>=?';
        $res = Db::query($sql,[$ads_sel_click]);
        return $res;
    }

    /**
     * 广告筛选器--广告权重增加
     */
    public function saveAllAds($data = '')
    {
        $Ads = new Ads;
        foreach ($data as $key => $value) {
            $data[$key]['priority'] = $data[$key]['priority'] + 1;
        }
        $res = $Ads->saveAll($data);
        if(!empty($res)){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 查询计划单价
     */
    public function getPrice($pid)
    {
        $sql = 'SELECT a.id,a.tpl_id,a.price_name,a.size,b.type FROM lz_plan_price as a LEFT JOIN lz_admode as b ON a.tpl_id = b.tpl_id WHERE a.pid=? AND b.type = 2 ';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 查询计划单价   图文计划尺寸展示
     */
    public function getPriceMore($pid)
    {
        $sql = 'SELECT a.id,a.tpl_id,a.price_name,a.size,b.type FROM lz_plan_price as a LEFT JOIN lz_admode as b ON a.tpl_id = b.tpl_id WHERE a.pid=? AND b.type = 1';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 查询计划单价   文字计划尺寸展示
     */
    public function getPriceText($pid)
    {
        $sql = 'SELECT a.id,a.tpl_id,a.price_name,a.size,b.type FROM lz_plan_price as a LEFT JOIN lz_admode as b ON a.tpl_id = b.tpl_id WHERE a.pid=? AND b.type = 2';
        $res = Db::query($sql,[$pid]);
        return $res;
    }

    /**
     * 查询广告位
     */
    public function getAdzone($params)
    {
        $sql = 'SELECT adz_id,show_adid FROM lz_adzone WHERE adtpl_id=? AND width=? AND height=? AND viewtype=?';
        $res = Db::query($sql,[$params['adtpl_id'],$params['width'],$params['height'],1]);
        return $res;
    }

    /**
     * 查询广告ID
     */
    public function getAdid($params)
    {
        $sql = 'SELECT ad_id FROM lz_ads WHERE adname=? AND imageurl=? ';
        $res = Db::query($sql,[$params['ad_name'],$params['imageurl']]);
        return $res;
    }

    /**
     * 查询广告位
     */
    public function updateAdzone($show_adid,$value)
    {
        $sql = 'UPDATE lz_adzone SET show_adid=? WHERE adz_id=? ';
        $res = Db::execute($sql,[$show_adid,$value['adz_id']]);
        return $res;
    }

    /**
     * 查询广告
     */
    public function getAds($aid)
    {
        $sql = 'SELECT ad_id,tpl_id as adtpl_id,width,height FROM lz_ads WHERE ad_id=?';
        $res = Db::query($sql,[$aid]);
        return $res;
    }

    /**
     * 查询计划名称
     */
    public function getPlanName($pid)
    {
        $sql = 'SELECT plan_name FROM lz_plan WHERE pid=?';
        $res = Db::query($sql,[$pid]);
        $res = empty($res[0]) ? '' : $res[0];
        return $res;
    }

    /**
     *  批量修改计划所属的广告链接
     */
    public function updateAdsUrl($param)
    {
        $sql = 'UPDATE lz_ads SET url=? WHERE pid=?';
        $res = Db::query($sql,[$param['url'],$param['pid']]);
        return $res;
    }

    /**
     *  批量修改图片链接地址
     */
    public function updateImgUrl($param)
    {
        //查出广告图片名称为_1 _2 ..../查出ID 和链接 进行批量修改
        $sql = "select ad_id,pid,adname,imageurl,width,height from lz_ads where pid = ?";
        $res = Db::query($sql,[$param['pid']]);
        return $res;
    }

    /**
     * 修改广告链接
     */
    public function saveImg($userInfo)
    {
        if (!empty($userInfo)){
            foreach ($userInfo as $key => $value) {
                $data['ad_id'] = $value['ad_id'] ;
                $de['imageurl'] = $value['save'] ;
                $sql = "UPDATE lz_ads SET imageurl = ? WHERE ad_id = ?";
                $res = Db::query($sql,[$de['imageurl'],$data['ad_id']]);
                $res = true;
            }
        }else{
            $res = false;
        }
        return $res;
    }


    /**
     *  批量修改计划所属的广告名称
     * @param $param
     * @return mixed
     */
    public function updateAdsName($param)
    {
        $sql = <<<ENF
UPDATE lz_ads SET adname=REPLACE(adname,?,?) WHERE pid=?
ENF;
        return Db::execute($sql, [$param['oldname'], $param['adname'], $param['pid']]);
    }




}