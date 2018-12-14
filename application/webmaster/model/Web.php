<?php
/**
 * 站长管理表
 * date   2016-10-28
 */
namespace app\webmaster\model;
use think\Model;
use think\Db;

class Web extends Model
{
    /**
     * 得到所有网站列表   超级管理员可以看到所有的负责人的网站   各负责人仅仅可看到自己的
     */
    public function getList($params,$name)
    {
        if($name == 'admin'){
            $sql = 'SELECT id,web_name,web_type,web_url,about_ip,qq,weights,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web ';
            if(empty($params['search'])){
                $sql.= ' ORDER BY shows,time DESC,ctime';
            }else{
                $sele = trim($params['search']);
                //大致流量的查询
                if($params['searchName'] == 'about_ip'){
                    $sql.= ' WHERE '.$params['searchName'].' >= '.$sele.' ORDER BY shows,time DESC,ctime';
                }else{
                    $sql.= ' WHERE '.$params['searchName'].' like "%'.$sele.'%" ORDER BY shows,time DESC,ctime';
                }
            }
            $res = Db::query($sql);
        }else{
            $sql = 'SELECT id,web_name,web_type,web_url,about_ip,qq,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web ';
            if(empty($params['search'])){
                $sql.= ' WHERE customer=? ORDER BY shows,time DESC,ctime';
            }else{
                $sele = trim($params['search']);
                //大致流量的查询
                if($params['searchName'] == 'about_ip'){
                    $sql.= ' WHERE customer=? AND '.$params['searchName'].' >= '.$sele.' ORDER BY shows,time DESC,ctime';
                }else{
                    $sql.= ' WHERE customer=? AND '.$params['searchName'].' like "%'.$sele.'%" ORDER BY shows,time DESC,ctime';
                }
            }
            $res = Db::query($sql,[$name]);
        }
        return $res;
    }

    /**
     * 得到跟进中的 超级管理员可以看到所有的负责人的网站   各负责人仅仅可看到自己的
     */
    public function getFollow($params,$name)
    {
        if($name == 'admin'){
            $sql = 'SELECT id,web_name,web_type,web_url,about_ip,qq,tel,email,customer,ip,pv,area,weights,uid,position,status,shows,time,start_time,ctime FROM lz_web ';
            if(empty($params['search'])){
                $sql.= ' WHERE status=2 ORDER BY shows,time DESC,ctime';
            }else{
                $sele = trim($params['search']);
                //大致流量的查询
                if($params['searchName'] == 'ip' || $params['searchName'] == 'weights'){
                    $sql.= ' WHERE '.$params['searchName'].' >= '.$sele.' AND status=2 ORDER BY shows,time DESC,ctime';
                }else{
                    $sql.= ' WHERE '.$params['searchName'].' like "%'.$sele.'%" AND status=2 ORDER BY shows,time DESC,ctime';
                }
            }
            $res = Db::query($sql);
        }else{
            $sql = 'SELECT id,web_name,web_type,web_url,about_ip,qq,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web ';
            if(empty($params['search'])){
                $sql.= ' WHERE customer=? AND status=2 ORDER BY shows,time DESC,ctime';
            }else{
                $sele = trim($params['search']);
                //大致流量的查询
                if($params['searchName'] == 'ip' || $params['searchName'] == 'weights'){
                    $sql.= ' WHERE customer=? AND '.$params['searchName'].' >= '.$sele.' AND status=2 ORDER BY shows,time DESC,ctime';
                }else{
                    $sql.= ' WHERE customer=? AND '.$params['searchName'].' like "%'.$sele.'%" AND status=2 ORDER BY shows,time DESC,ctime';
                }
            }
            $res = Db::query($sql,[$name]);
        }
        return $res;
    }

    /**
     * 得到已合作的 超级管理员可以看到所有的负责人的网站   各负责人仅仅可看到自己的
     */
    public function getCooper($params,$name)
    {
        if(!empty($params['search'])){
            if($params['searchName'] == 'username'){
                $param = 'b.'.$params['searchName'];
            }else{
                $param = 'a.'.$params['searchName'];
            }
        }
        $sql = 'SELECT a.id,a.web_name,a.web_type,a.web_url,a.about_ip,a.qq,a.tel,a.email,a.customer,a.ip,a.pv,a.area,a.weights,a.price,a.method,a.uid,a.position,a.status,a.shows,a.time,a.ctime,b.username,b.contact,b.account_name,b.bank_name,b.bank_branch,b.bank_card,c.*  FROM lz_web AS a LEFT JOIN lz_users AS b ON a.uid=b.uid LEFT JOIN (SELECT web_id,comment,user,ptime FROM lz_webcomment ORDER BY ptime DESC LIMIT 0,1) AS c ON a.id=c.web_id';
        if($name == 'admin'){
            if(empty($params['search'])){
                $sql.= ' WHERE a.status=1 ORDER BY a.time DESC,a.ctime';
            }else{
                $sele = trim($params['search']);
                if($params['searchName'] == 'ip' || $params['searchName'] == 'weights'){
                    $sql.= ' WHERE a.status=1 AND '.$param.' >= '.$sele.' ORDER BY a.time DESC,a.ctime';
                }else{
                    $sql.= ' WHERE a.status=1 AND '.$param.' like "%'.$sele.'%" ORDER BY a.time DESC,a.ctime';
                }
            }
            $res = Db::query($sql);
        }else{
            if(empty($params['search'])){
                $sql.= ' WHERE a.customer=? AND a.status=1 ORDER BY a.time DESC,a.ctime';
            }else{
                $sele = trim($params['search']);
                if($params['searchName'] == 'ip' || $params['searchName'] == 'weights'||$params['searchName'] == 'pv'||$params['searchName'] == 'pv'){
                    $sql.= ' WHERE a.customer=? AND a.status=1 AND '.$param.' >= '.$sele.' ORDER BY a.time DESC,a.ctime';
                }else{
                    $sql.= ' WHERE a.customer=? AND a.status=1 AND '.$param.' like "%'.$sele.'%" ORDER BY a.time DESC,a.ctime';
                }
            }
            $res = Db::query($sql,[$name]);
        }
        return $res;
    }


    /**
     * 得到未合作和黑名单网站列表   超级管理员可以看到所有的负责人的网站   各负责人仅仅可看到自己的
     */
    public function getStatusList($params,$name,$status)
    {
        $sql = 'SELECT id,web_name,web_type,web_url,about_ip,qq,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web ';
        if($name == 'admin'){
            if(empty($params['search'])){
                $sql.= ' WHERE status=? ORDER BY shows,time DESC,ctime';
            }else{
                $sele = trim($params['search']);
                //大致流量的查询
                if($params['searchName'] == 'about_ip'){
                    $sql.= ' WHERE status=? AND '.$params['searchName'].' >= '.$sele.' ORDER BY shows,time DESC,ctime';
                }else{
                    $sql.= ' WHERE status=? AND '.$params['searchName'].' like "%'.$sele.'%" ORDER BY shows,time DESC,ctime';
                }
            }
            $res = Db::query($sql,[$status]);
        }else{
            if(empty($params['search'])){
                $sql.= ' WHERE status=? AND customer=? ORDER BY shows,time DESC,ctime';
            }else{
                $sele = trim($params['search']);
                //大致流量的查询
                if($params['searchName'] == 'about_ip'){
                    $sql.= ' WHERE status=? AND customer=? AND '.$params['searchName'].' >= '.$sele.' ORDER BY shows,time DESC,ctime';
                }else{
                    $sql.= ' WHERE status=? AND customer=? AND '.$params['searchName'].' like "%'.$sele.'%" ORDER BY shows,time DESC,ctime';
                }
            }
            $res = Db::query($sql,[$status,$name]);
        }
        return $res;
    }
    /** 
     * 查询网站分类
     */

    public function getClass()
    {
        $sql = 'SELECT * FROM lz_classes WHERE type=? ';
        $res = Db::query($sql,[1]);
        return $res;
    }

    /**
     * 得到网站的今日批注个数
     */
    public function getCommentNum($name,$param)
    {
        if($name == 'admin'){
            $sql = 'SELECT count(id) as count FROM lz_webcomment WHERE status=0 AND type=2 AND web_id=?';
        }else{
            $sql = 'SELECT count(id) as count FROM lz_webcomment WHERE status=0 AND type=1 AND web_id=?';
        }
        $res = Db::query($sql,[$param['id']]);
        return $res[0]['count'];
    }

    /**
     * 得到网站的今日批注个数
     */
    public function getComment($param)
    {
        $sql = 'SELECT a.id,a.web_id,a.user,a.status,a.comment,a.type,a.ptime,b.web_url FROM lz_webcomment AS a LEFT JOIN lz_web AS b ON a.web_id=b.id  WHERE web_id=? ORDER BY a.ptime DESC';
        $res = Db::query($sql,[$param['id']]);
        return $res;
    }

    /** 
     * 一键读取信息 
     */
    public function updateRead($where,$data)
    {
        $res = Db::name('webcomment')->WHERE($where)->update($data);
        return $res;
    }

    /** 
     * 查询信息是否完整
     */
    public function getWeb($param)
    {
        $map['id'] = $param['id'];
        $res = Db::name('web')->WHERE($map)->find();
        return $res;
    }


    /**
     * 查询超级管理员所建立的网站
     */
    public function getManagerList()
    {
        $sql = 'SELECT id,web_name,web_url,qq,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web
        WHERE type=? ORDER BY shows,time DESC,start_time';
        $res = Db::query($sql,[1]);
        return $res;
    }

    /**
     * 得到分类情况下的站长分页
     */
    public function getCount($params)
    {
        if(empty($params['searchName']) || empty($params['searchNum'])){
            $sql = 'SELECT count(id) as count FROM lz_web WHERE status=? AND type!=?';
            $res = Db::query($sql,[$params['status'],1]);
        }else{
            $sql = 'SELECT count(id) as count FROM lz_web WHERE status=? AND '.$params['searchName'].'=? AND type!=?';
            $res = Db::query($sql,[$params['status'],$params['searchNum'],1]);
        }
        return $res[0]['count'];
    }

    /**
     * 得到分类情况下的站长列表
     */
    public function getClassifyList($params)
    {
        if(empty($params['searchName']) || empty($params['searchNum'])){
            $sql = 'SELECT id,web_name,web_url,qq,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web
            WHERE status=? AND type!=? ORDER BY shows,time DESC,ctime ';
            $res = Db::query($sql,[$params['status'],1]);
        }else{
            $sql = 'SELECT id,web_name,web_url,qq,tel,email,customer,ip,status,shows,time,start_time,ctime FROM lz_web
            WHERE status=? AND '.$params['searchName'].'=? AND type!=? ORDER BY shows,time DESC,ctime ';
            $res = Db::query($sql,[$params['status'],$params['searchNum'],1]);
        }
        return $res;
    }

    /**
     * 得到需要编辑的站长信息
     */
    public function getEditList($params)
    {
        $sql = 'SELECT id,web_url,web_type,qq,tel,email,customer,about_ip,pv,ip,area,weights,uid,info,position,price,method,status FROM lz_web WHERE id=?';
        $res = Db::query($sql,[$params['id']]);
        return $res;
    }

    /**
     * 新建用户
     * param data
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $data['start_time'] = time();
        $data['time'] = time();
        $res = Db::name('web')->insert($data);
        return $res;
    }

    /**
     * 判断是否有此用户
     */
    public function decide($data)
    {
        $where = array(
            'web_url' =>$data,
        );
        $res = Db::name('web')->where($where)->find();
        return $res;
    }

    /**
     * 判断是否有此用户(罗总)
     */
    public function lzDecide($data)
    {
        $sql = 'SELECT id FROM lz_web WHERE web_url=? AND type=?';
        $res = Db::query($sql,[$data,1]);
        return $res;
    }

    /**
     * 得到此用户的时间
     */
    public function getTime($data)
    {
        $sql = 'SELECT start_time FROM lz_web WHERE id=?';
        $res = Db::query($sql,[$data]);
        return $res;
    }

    /**
     * 编辑用户
     * param data
     */
    public function edit($id,$data)
    {
        $map = array(
            'id'=>$id,
        );
        $res = Db::name('web')->where($map)->update($data);
        return $res;
    }

    /**
     * 编辑用户显示
     * param data
     */
    public function editShows($id,$shows,$time)
    {
        $map = array(
            'id'=>$id,
        );
        $data = array(
            'shows'=>$shows,
            'start_time'=>$time,
            'time'=>time(),

        );
        $res = Db::name('web')->where($map)->update($data);
        return $res;
    }

    /**
     * 删除站长
     * param id
     */
    public function delOne($id)
    {
        $map = array(
            'id'=>$id,
        );
        $res = Db::name('web')->where($map)->delete();
        return $res;
    }

    /**
     * 删除批注
     * param id
     */
    public function delCom($id)
    {
        $map = array(
            'id'=>$id,
        );
        $res = Db::name('webcomment')->where($map)->delete();
        return $res;
    }

    /**
     * 更新状态
     * param aid 计划id status 状态
     */
    public function updateStatus($id,$status)
    {
        $data = array(
            'status' => $status,
            'time' => time()
        );
        $map = array(
            'id'=>$id,
        );
        $res = Db::name('web')->where($map)->update($data);
        return $res;
    }

    /**
     * 站长列表批量删除
     */
    public function delLst($ids)
    {
        Db::startTrans();
        try {
            $Ads = new Web;
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
}