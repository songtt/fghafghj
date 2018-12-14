<?php
/**
 * 二次点击表
 * date   2016-12-12
 */
namespace app\admin\model;
use think\Model;
use think\Db;

class Clickagain extends Model
{    
    /**
     * 添加广告
     */
    public function insertdata($data)
    {
        $data['ctime'] = time();
        $res = Db::name('clickagain')->INSERT($data);
        return $res;
    }
}