<?php
/**
 * 注册账号
 * @date   2016-6-16
 */
namespace app\home\model;
use think\Model;
use think\Db;
class Register extends Model
{



    /**
     * 新建用户
     * param data
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $res = Db::name('users')->insert($data);
        //数据同步users_log
        Db::name('users_log')->insert($data);
        return $res;
    }

}