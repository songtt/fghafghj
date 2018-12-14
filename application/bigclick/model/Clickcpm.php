<?php
/**
 * 网站
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2
 *------------------------------------------------------
 */
namespace app\bigclick\model;
use think\Db;
use think\Model;


class Clickcpm extends Model
{

    /**
     * 查询产品
     */
    public function proListCount($param)
    {
        $sql = 'SELECT a.pro_id,a.pro_name,a.adv_name,a.billing_model,a.launch_model,a.post_type,a.status,a.ctime,a.plan_name,a.pro_type
                FROM lz_bigclick_cpm_product AS a';
        if(empty($param['search'])){
            if($param['status'] == 'all'){
                $sql.=' ORDER BY a.pro_id DESC ';
                $res = Db::connect('db_182_config')->query($sql);
            }else{
                $sql.=' WHERE a.status=? ORDER BY a.pro_id DESC';
                $res = Db::connect('db_182_config')->query($sql,[$param['status']]);
            }
        }else{
            $sele = ltrim($param['search']);
            if($param['status'] == 'all'){
                if($param['index'] == 'pro_name'){
                    $sql.=' WHERE a.pro_name like "%'.$sele.'%" ORDER BY a.pro_id DESC';
                }elseif($param['index'] == 'adv_name'){
                    $sql.=' WHERE a.adv_name like "%'.$sele.'%" ORDER BY a.pro_id DESC';
                }elseif($param['index'] == 'pro_type'){
                    $sql.=' WHERE a.pro_type="'.$sele.'" ORDER BY a.pro_id DESC';
                }else{
                    $sql.=' WHERE a.plan_name like "%'.$sele.'%" ORDER BY a.pro_id DESC';
                }
                $res = Db::connect('db_182_config')->query($sql);
            }else{
                if($param['index'] == 'pro_name'){
                    $sql.=' WHERE a.status=? AND a.pro_name like "%'.$sele.'%" ORDER BY a.pro_id DESC';
                }elseif($param['index'] == 'adv_name'){
                    $sql.=' WHERE a.status=? AND a.adv_name like "%'.$sele.'%" ORDER BY a.pro_id DESC';
                }elseif($param['index'] == 'pro_type'){
                    $sql.=' WHERE a.status=? AND a.pro_type="'.$sele.'" ORDER BY a.pro_id DESC';
                }else{
                    $sql.=' WHERE a.status=? AND a.plan_name like "%'.$sele.'%" ORDER BY a.pro_id DESC';
                }
                $res = Db::connect('db_182_config')->query($sql,[$param['status']]);
            }
        }
        return count($res);
    }

    /**
     * 查询产品
     */
    public function proList($offset,$count,$param)
    {
        $sql = 'SELECT a.pro_id,a.pro_name,a.adv_name,a.billing_model,a.launch_model,a.post_type,a.status,a.num,a.ctime,a.pro_type,
                a.plan_name,c.url,c.percent,c.checkplan,c.delivery_mode FROM lz_bigclick_cpm_product AS a
                LEFT JOIN lz_bigclick_cpm_url_copy AS c ON a.pro_id=c.pro_id  ';
        if(empty($param['search'])){
            if($param['status'] == 'all'){
                $sql.='GROUP BY a.pro_id ORDER BY a.pro_id DESC  Limit ?,? ';
                $res = Db::connect('db_182_config')->query($sql,[$offset,$count]);
            }else{
                $sql.=' WHERE a.status=? GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                $res = Db::connect('db_182_config')->query($sql,[$param['status'],$offset,$count]);
            }
        }else{
            $sele = ltrim($param['search']);
            if($param['status'] == 'all'){
                if($param['index'] == 'pro_name'){
                    $sql.=' WHERE a.pro_name like "%'.$sele.'%" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }elseif($param['index'] == 'adv_name'){
                    $sql.=' WHERE a.adv_name like "%'.$sele.'%" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }elseif($param['index'] == 'pro_type'){
                    $sql.=' WHERE a.pro_type="'.$sele.'" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }else{
                    $sql.=' WHERE a.plan_name like "%'.$sele.'%" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }
                $res =Db::connect('db_182_config')->query($sql,[$offset,$count]);
            }else{
                if($param['index'] == 'pro_name'){
                    $sql.=' WHERE a.status=? AND a.pro_name like "%'.$sele.'%" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }elseif($param['index'] == 'adv_name'){
                    $sql.=' WHERE a.status=? AND a.adv_name like "%'.$sele.'%" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }elseif($param['index'] == 'pro_type'){
                    $sql.=' WHERE a.status=? AND a.pro_type like "'.$sele.'" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }else{
                    $sql.=' WHERE a.status=? AND a.plan_name like "%'.$sele.'%" GROUP BY a.pro_id ORDER BY a.pro_id DESC Limit ?,? ';
                }
                $res = Db::connect('db_182_config')->query($sql,[$param['status'],$offset,$count]);
            }
        }

        return $res;
    }

    /**
     * 产品状态编辑
     */
    public function updateStatus($proId,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'pro_id'=>$proId,
        );
        $res = Db::connect('db_182_config')->name('bigclick_cpm_product')->where($map)->update($data);
        Db::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($map)->update($data);

        return $res;
    }

    /**
     * 产品新增
     */
    public  function proAdd($data)
    {
        $insert = Db::connect('db_182_config')->name('bigclick_cpm_product')->insert($data);
        return $insert;
    }

    /**
     * 查询产品消息
     */
    public function getPro($id)
    {
        $sql = 'SELECT pro_id,pro_name,adv_name,num,billing_model,launch_model,post_type,status,plan_name,pro_type
                FROM lz_bigclick_cpm_product WHERE pro_id=?';
        $res = Db::connect('db_182_config')->query($sql,array($id));
        return $res[0];
    }

    /**
     * 编辑
     * param data array 修改数据
     */
    public function proEdit($id,$data)
    {
        $map = array(
            'pro_id'=>$id,
        );
        $res = Db::connect('db_182_config')->name('bigclick_cpm_product')->where($map)->update($data);
        return $res;
    }

    /**
     * 删除产品
     */
    public function proDel($id)
    {
        $map = array(
            'pro_id'=>$id,
        );
        $res = Db::connect('db_182_config')->name('bigclick_cpm_product')->where($map)->delete();
        Db::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($map)->delete();
        if($res>0){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 查询产品消息
     */
    public function getProName()
    {
        $sql = 'SELECT pro_name,pro_id FROM lz_bigclick_cpm_product';
        $res = Db::connect('db_182_config')->query($sql);
        return $res;
    }

    /**
     * 查询链接信息
     */
    public function urlListCount($param)
    {
        $sql = 'SELECT a.url_name,a.url_id,a.pro_id,a.url,a.delivery_mode,a.status,a.checkplan,a.percent,a.ctime,b.pro_name
                FROM lz_bigclick_cpm_url_copy AS a LEFT JOIN lz_bigclick_cpm_product AS  b ON a.pro_id=b.pro_id';
        if(empty($param['search'])){
            if($param['status'] == 'all'){
                $sql.=' ORDER BY a.url_id DESC ';
                $res = Db::connect('db_182_config')->query($sql);
            }else{
                $sql.=' WHERE a.status=? ORDER BY a.url_id DESC';
                $res = Db::connect('db_182_config')->query($sql,[$param['status']]);
            }
        }else{
            $sele = $param['search'];
            if($param['status'] == 'all'){
                if($param['index'] == 'url'){
                    $sql.=' WHERE a.url like "%'.$sele.'%" ORDER BY a.url_id DESC';
                }else{
                    $sql.=' WHERE b.pro_name like "%'.$sele.'%" ORDER BY a.url_id DESC';
                }
                $res = Db::connect('db_182_config')->query($sql);
            }else{
                if($param['index'] == 'url'){
                    $sql.=' WHERE a.status=? AND a.url like "%'.$sele.'%" ORDER BY a.url_id DESC';
                }else{
                    $sql.=' WHERE a.status=? AND b.pro_name like "%'.$sele.'%" ORDER BY a.url_id DESC';
                }
                $res = Db::connect('db_182_config')->query($sql,[$param['status']]);
            }
        }
        return count($res);
    }

    /**
     * 查询链接
     */
    public function urlList($offset,$count,$param)
    {
        $sql = 'SELECT a.url_name,a.url_id,a.pro_id,a.url,a.delivery_mode,a.status,a.checkplan,a.percent,a.ctime,b.pro_name
                FROM lz_bigclick_cpm_url_copy AS a LEFT JOIN lz_bigclick_cpm_product AS  b ON a.pro_id=b.pro_id ';
        if(empty($param['search'])){
            if($param['status'] == 'all'){
                $sql.='ORDER BY a.url_id DESC  Limit ?,? ';
                $res = Db::connect('db_182_config')->query($sql,[$offset,$count]);
            }else{
                $sql.=' WHERE a.status=? ORDER BY a.url_id DESC Limit ?,? ';
                $res = Db::connect('db_182_config')->query($sql,[$param['status'],$offset,$count]);
            }
        }else{
            $sele = $param['search'];
            if($param['status'] == 'all'){
                if($param['index'] == 'url'){
                    $sql.=' WHERE a.url like "%'.$sele.'%" ORDER BY a.url_id DESC Limit ?,? ';
                }else{
                    $sql.=' WHERE b.pro_name like "%'.$sele.'%" ORDER BY a.url_id DESC Limit ?,? ';
                }
                $res = Db::connect('db_182_config')->query($sql,[$offset,$count]);
            }else{
                if($param['index'] == 'url'){
                    $sql.=' WHERE a.status=? AND a.url like "%'.$sele.'%" ORDER BY a.url_id DESC Limit ?,? ';
                }else{
                    $sql.=' WHERE a.status=? AND b.pro_name like "%'.$sele.'%" ORDER BY a.url_id DESC Limit ?,? ';
                }
                $res = Db::connect('db_182_config')->query($sql,[$param['status'],$offset,$count]);
            }
        }

        return $res;
    }

    /**
     * 链接新增
     */
    public  function urlAdd($data)
    {
        $insert = Db::connect('db_182_config')->name('bigclick_cpm_url_copy')->insert($data);
        return $insert;
    }

    /**
     * 编辑
     * param data array 修改数据
     */
    public function urlEdit($id,$data)
    {
        $map = array(
            'url_id'=>$id,
        );
        $res = Db::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($map)->update($data);
        return $res;
    }

    /**
     * 链接状态编辑
     */
    public function updateurlStatus($urlid,$status)
    {
        $data = array(
            'status' => $status
        );
        $map = array(
            'url_id'=>$urlid,
        );
        $res = Db::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($map)->update($data);
        return $res;
    }

    /**
     * 删除链接
     */
    public function urlDel($id)
    {
        $map = array(
            'url_id'=>$id,
        );
        $res = Db::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($map)->delete();
        if($res>0){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 查询链接消息
     */
    public function geturl($id)
    {
        $sql = 'SELECT a.url_name,a.url_id,a.pro_id,a.url,a.delivery_mode,a.status,a.checkplan,a.percent,a.ctime,b.pro_name
                FROM lz_bigclick_cpm_url_copy AS a LEFT JOIN lz_bigclick_cpm_product AS  b ON a.pro_id=b.pro_id WHERE a.url_id=?';
        $res = Db::connect('db_182_config')->query($sql,array($id));
        return $res[0];
    }

    /**
     *  验证添加安卓产品是否成功   
     */
    public function proaddcheck($data){

        $da['pro_name'] = $data['pro_name'] ;
        $da['adv_name'] = $data['adv_name'] ;
        $da['plan_name'] = $data['plan_name'] ;
        $da['billing_model'] = $data['billing_model'] ;
        $da['launch_model'] = $data['launch_model'] ;
        $da['post_type'] = $data['post_type'] ;
        $da['ctime'] = $data['ctime'] ;

        $re = Db::connect('db_182_config')->name('bigclick_cpm_product')->where($da)->select();
        return $re;
    }

    /**
     *  查出安卓产品名称
     */
    public function proname($id){

        $da['pro_id'] = $id;

        $re = DB::connect('db_182_config')->name('bigclick_cpm_product')->where($da)->select();
        return $re;
    }

    /**
     *  查出新增安卓产品ID
     */
    public function urlidcheck($data){

        $da['url_name'] = $data['url_name'] ;
        $da['pro_id'] = $data['pro_id'] ;
        $da['url'] = $data['url'] ;
        $da['delivery_mode'] = $data['delivery_mode'] ;
        $da['percent'] = $data['percent'] ;
        $da['checkplan'] = $data['checkplan'] ;
        $da['ctime'] = $data['ctime'] ;
        
        $re = DB::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($da)->select();
        return $re;
    }

    /**
     *  修改链接-- 操作日志
     */
    public function urleditcheck($id){

        $da['url_id'] = $id;

        $re = DB::connect('db_182_config')->name('bigclick_cpm_url_copy')->where($da)->select();
        return $re;
    }

     /*
      * 自动计算
      */
    public function saveurlsum()
    {
        $sql = 'SELECT pro_id,status,num FROM lz_bigclick_cpm_product';
        $res = Db::connect('db_182_config')->query($sql);
        return $res;
    }

    /**
     *  自动计算
     */
    public function saveurlsun()
    {
        $sql = 'SELECT url_id,pro_id,status FROM lz_bigclick_cpm_url_copy';
        $res = DB::connect('db_182_config')->query($sql);
        return $res;
    }

    /*
     *  自动计算
     */
    public function updatepronum($re)
    {   
        foreach ($re as $key => $value) {
            $data['pro_id'] = $value['pro_id'] ;
            $da['num'] = $value['save'] ;
            $res = Db::connect('db_182_config')->name('bigclick_cpm_product')->where($data)->update($da);
        } 
        // return $res;
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
}
