<?php
/**
 * 分类
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2 
 *------------------------------------------------------
 */
namespace app\admin\model;
use think\Model;
use think\Db;
class Classes extends Model
{
    /**
     * 查询数据
     */
    public function classList()
    {
        $sql = 'SELECT a.class_id,a.class_name,a.type,b.class_name AS name,b.relation,b.class_id as relation_class_id FROM lz_classes AS a LEFT JOIN
         lz_classes AS b ON a.class_id=b.relation WHERE a.type=? AND a.relation=?  ORDER BY a.class_id DESC';
        $one =Db::query($sql,[1,0]);
        return $one;
    }

    /**
     * 查询是否添加重复classname
     */
    public function classname($value)
    {
        $sql = 'SELECT class_name FROM lz_classes WHERE class_name=?';
        $one =Db::query($sql,[$value]);
        return $one;
    }

    /**
     * 查询数据
     */
    public function addClass()
    {
        $sql = 'SELECT * FROM lz_classes WHERE type=? AND relation=? ORDER BY class_id DESC';
        $one =Db::query($sql,[1,0]);
        return $one;
    }
    /**
     * 添加分类
     */
    public  function add($data)
    {
        $inst = Db::name('classes')->insert($data);
        return $inst;
    }

    /**
     *  修改二级网站分类
     */
    public  function minClass($id)
    {
        $sql = 'SELECT * FROM lz_classes WHERE type=? AND class_id=?';
        $res = Db::query($sql,[1,$id]);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }



    /**
     * 修改子分类
     */
    public function editClass($params)
    {
        if(empty($params['relation_class_id'])){
            $params['relation_class_id'] = $params['classid'];
        }
        $map = array(
            'class_id' => $params['relation_class_id']
            );
        $data = array(
            'class_name' => $params['class_name']
            );
        $res = Db::name('classes')->where($map)->update($data);
        return $res;
    }

    /**
     * 删除分类
     * 有子分类删除子分类(3级)，没有删除2级分类
     */
    public function delClass($params)
    {
        $res = Db::name('classes')->where($params)->delete();
        return $res;
    }

    /**
     * 获取分类列表
     */
    public function getLstByType($type = '')
    {
        if(empty($type)){
            $sql = 'SELECT * FROM lz_classes WHERE type=? ';
            $res = Db::query($sql,['1']);
        }
        // else{
        //     $sql = 'SELECT * FROM lz_classes WHERE type=? ';
        //     $res = Db::query($sql,[$type]);
        // }
        return $res;
    }
}
