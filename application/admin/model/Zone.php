<?php
/**
 * 广告位
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2
 *------------------------------------------------------
 */
namespace app\admin\model;
use think\Model;

class Zone extends Model
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
