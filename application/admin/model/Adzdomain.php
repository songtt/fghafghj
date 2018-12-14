<?php
/**
 * 广告位
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2
 *------------------------------------------------------
 */
namespace app\admin\model;
use think\Db;

class Adzdomain extends \think\Model
{
    /**
     * 查询列表数据个数
     */
    public function getListCount($pageParam)
    {
        $pageParam['searchname'] = ($pageParam['searchname'] == 'zonename') ? 'b.'.$pageParam['searchname'] : 'a.'.$pageParam['searchname'];
        $sql ='SELECT count(a.adz_id) AS count FROM lz_adz_domain AS a LEFT JOIN lz_adzone AS b
               ON a.adz_id=b.adz_id';
        if(empty($pageParam['search'])){
            $res = Db::query($sql);
        }else{
            $sql .= ' WHERE '.$pageParam['searchname'].'=?';
            $res = Db::query($sql,[$pageParam['search']]);
        }
        return $res[0]['count'];
    }

    /**
     * 查询列表数据
     */
    public function getList($offset,$count,$pageParam)
    {
        $pageParam['searchname'] = $pageParam['searchname'] == 'zonename' ? 'b.'.$pageParam['searchname'] : 'a.'.$pageParam['searchname'];
        $sql ='SELECT a.adz_id,a.domain_name,b.zonename FROM lz_adz_domain AS a LEFT JOIN lz_adzone AS b
               ON a.adz_id=b.adz_id';
        if(empty($pageParam['search'])){
            $sql .= ' LIMIT ?,?';
            $res = Db::query($sql,[$offset,$count]);
        }else{
            $sql .= ' WHERE '.$pageParam['searchname'].'=? LIMIT ?,?';
            $res = Db::query($sql,[$pageParam['search'],$offset,$count]);
        }

        return $res;
    }

    /**
     * 查看该广告位域名是否已经添加
     */
    public function getName($adzId)
    {
        $sql = 'SELECT adz_id FROM lz_adz_domain WHERE adz_id=?';
        $res = Db::query($sql,[$adzId]);
        return $res;
    }

    /**
     * add data
     * param data
     */
    public function add($data)
    {
        $res = Db::name('adz_domain')->insert($data);
        return $res;
    }

    /**
     * 查看该广告位域名是否已经添加
     */
    public function adzcheck($adzId)
    {
        $sql = 'SELECT adz_id FROM lz_adzone WHERE adz_id=?';
        $res = Db::query($sql,[$adzId]);
        return $res;
    }

    /**
     * 获取该广告位的域名
     */
    public function getadzdomain($adzId)
    {
        $sql = 'SELECT adz_id,domain_name FROM lz_adz_domain WHERE adz_id=?';
        $res = Db::query($sql,[$adzId]);
        return $res;
    }

    /**
     * edit
     */
    public function edit($params)
    {
        $sql = 'UPDATE lz_adz_domain SET domain_name=? WHERE adz_id=?';
        $res = Db::execute($sql,[$params['domain_name'],$params['adz_id']]);
        return $res;
    }

    /**
     * 删除
     */
    public function delOne($adz_id)
    {
        $map = array(
            'adz_id'=>$adz_id,
        );
        $res = Db::name('adz_domain')->where($map)->delete();
        return $res;
    }
}
