<?php
namespace app\index\model;
use think\Model;
use think\Db;

class Show extends Model
{
    /*
     *  显示基本信息
     * */
    public function show($id)
    {
        $map=array(
            'style_id'=>$id,
        );
        $res = Db::name('adstyle')->where($map)->find();

        return $res;
    }



















}
