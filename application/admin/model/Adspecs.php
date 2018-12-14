<?php
/**
 * 广告尺寸表
 *------------------------------------------------------
 * @date   2016-6-2 
 *------------------------------------------------------
 */
namespace app\admin\model;
use think\Model;

class Adspecs extends Model
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
}
