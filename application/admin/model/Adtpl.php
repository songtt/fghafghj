<?php
/**
 * 广告模板表
 * @date   2016-6-2 
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class Adtpl extends Model
{    
    /**
     * 广告类型list
     */
    public function getLst()
    {
        $sql = 'SELECT adtype_id,type_name,stats_type,sort,terminal
        FROM lz_adtype WHERE status!=4 ORDER BY adtype_id';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 广告类型添加
     */
    public function addOne($params)
    {
        $Adtype = new Adtype;
        $Adtype->type_name = $params['name'];
        $Adtype->stats_type = $params['statstype'];
        $Adtype->sort  =$params['sort'];
        $Adtype->terminal = $params['terminal'];
        $Adtype->ctime = time();
        $res = $Adtype->save();
        return $res;
    }

    /**
     * 编辑get one
     * param pid 计划id 
     */
    public function getTypeOne($id)
    {
        $Adtype = new Adtype;
        $One = $Adtype->get($id);
        $res = $One->data;
        return $res;
    }

    /**
     * 编辑
     * param data array 修改数据 
     */
    public function editTypeOne($data)
    {
        $Adtype = new Adtype;
        $One = $Adtype->get($data['id']);
        $One->type_name=$data['name'];
        $One->stats_type=$data['statstype'];
        $One->sort=$data['sort'];
        $One->terminal=$data['terminal'];
        $res = $One->save();
        return $res;
    }

    /**
     * 删除
     * param data array 删除数据
     */
    public function delOne($id)
    {
        $map = array(
            'adtype_id'=>$id,
        );
        $data = array(
            'status'=>4,
        );
        $res = Db::name('adtype')->where($map)->update($data);
        return $res;
    }

    /**
     * 广告模式
     */
    public function adtplList(){
        $sql = 'SELECT a.tpl_id,a.adtype_id,a.tplname,a.sort,a.status,b.adtype_id AS typeid,b.type_name
        FROM lz_admode AS a LEFT JOIN lz_adtype AS b ON a.adtype_id =b.adtype_id  WHERE a.status!=4 ORDER BY a.sort ASC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 广告模式  添加
     */
    public function addTpl($data){
        $res = Db::name('admode')->insert($data);
        return $res;
    }

    /**
     * 广告模式   修改
     */
    public function adtplEdit($id,$data){
        $map = array(
            'tpl_id'=>$id,
        );
        $res = Db::name('admode')->where($map)->update($data);
        return $res;
    }

    /**
     * 广告模式   默认显示
     */
    public function adtplListOne($id){
        $sql = 'SELECT tpl_id,adtype_id,tplname,sort,htmlcontrol,type FROM lz_admode WHERE tpl_id='.$id;
        $res = Db::query($sql);
        if(empty($res)){
            return 0;
        }else{
        return $res[0];
        }
    }

    /**
     * 广告模式   delect
     */
    public function adtplDelect($id){
        $map = array(
            'tpl_id'=>$id,
        );
        $data = array(
            'status'=>4,
        );
        $res = Db::name('admode')->where($map)->update($data);
        return $res;
    }

    /**
     * 广告模式   修改 状态
     */
    public function adtplStatEdit($id,$status){
        $map = array(
            'tpl_id'=>$id,
        );
        $data = array(
            'status'=>$status,
        );
        $res = Db::name('admode')->where($map)->update($data);
        return $res;
    }

    /**
     * 广告样式   add 添加
     */
    public function adstyleAdd($data){
        $res = Db::name('adstyle')->insert($data);
        return $res;
    }

    /**
     * 广告样式   列表
     */
    public function adstyleList(){
        $sql = 'SELECT a.style_id,a.stylename,a.tpl_id,a.sort,a.ctime,b.tpl_id AS tid,b.tplname
        FROM lz_adstyle AS a LEFT JOIN lz_admode AS b ON a.tpl_id =b.tpl_id WHERE a.status!=4 ORDER BY a.style_id DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 广告样式   已删除列表
     */
    public function adstyleDelist(){
        $sql = 'SELECT a.style_id,a.stylename,a.tpl_id,a.sort,b.tpl_id AS tid,b.tplname
        FROM lz_adstyle AS a LEFT JOIN lz_admode AS b ON a.tpl_id =b.tpl_id WHERE a.status=4 ORDER BY a.style_id DESC';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 广告样式   修改时显示数据
     */
    public function adstyleFind($id){
        $sql = 'SELECT a.*,b.tpl_id AS tid,b.tplname FROM lz_adstyle AS a LEFT JOIN lz_admode AS b ON a.tpl_id =b.tpl_id WHERE a.style_id ='.$id;
        $res = Db::query($sql);
        if(empty($res)){
            return 0;
        }else{
            return $res[0];
        }
    }

    /**
     * 广告样式   修改时显示数据
     */
    public function adstyleUpdete($id,$data){
        $map = array(
            'style_id'=>$id,
        );
        $res = Db::name('adstyle')->where($map)->update($data);
         return $res;
    }

    /**
     * 广告样式  delect
     */
    public function adstyleDelect($id){
        $map = array(
            'style_id'=>$id,
        );
        $data = array(
            'status'=>4,
        );
        $res = Db::name('adstyle')->where($map)->update($data);
        return $res;
    }

    /**
     * 广告尺寸 add
     */
    public function adspecsAdd($data){
        $res = Db::name('adspecs')->insert($data);
        return $res;
    }

    /**
     * 广告尺寸 list
     */
    public function adspecsList(){
        $res = Db::query('SELECT * FROM lz_adspecs WHERE status!=4 ORDER BY width DESC');
        return $res;
    }

    /**
     * 广告尺寸 显示数据
     */
    public function adspecsFind($id){
        $map = array(
            'specs_id'=>$id,
        );
        $res = Db::name('adspecs')->where($map)->find();
        return $res;
    }

    /**
     * 广告尺寸 edit
     */
    public function adspecsEdit($id,$data){
        $map = array(
            'specs_id'=>$id,
        );
        $res = Db::name('adspecs')->where($map)->update($data);
        return $res;
    }
    
    /**
     * 广告样式  delect
     */
    public function adspecsDelect($id){
        $map = array(
            'specs_id'=>$id,
        );
        $data = array(
            'status'=>4,
        );
        $res = Db::name('adspecs')->where($map)->update($data);
        return $res;
    }
}