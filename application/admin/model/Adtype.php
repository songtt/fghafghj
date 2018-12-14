<?php
/**
 * 广告类型表
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2 
 *------------------------------------------------------
 */
namespace app\admin\model;
use think\Model;
use think\Db;

class Adtype extends Model
{
    /**
     * 查询数据
     */
    public function getone()
    {
        $one =Db::table('person')->select();
        $one = Person::all();
        return $one;
    }

    /**
     * 新建广告下广告类型 list
     * param data array 修改数据 
     */
    public function adsTypeLst()
    {
        $sql = 'SELECT a.*,b.type_name,b.stats_type FROM lz_admode as a 
        LEFT JOIN lz_adtype AS b ON a.adtype_id=b.adtype_id';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 编辑广告 下广告类型 list
     * param data array 修改数据 
     */
    public function adsTypeOne($tpl_id)
    {
        $sql = 'SELECT a.*,b.type_name,b.stats_type FROM lz_admode AS a
        LEFT JOIN lz_adtype AS b ON a.adtype_id=b.adtype_id WHERE a.tpl_id=?';
        $res = Db::query($sql,[$tpl_id]);
        if(empty($res)){
            return 0;
        }else {
            return $res[0];
        }
    }

}
