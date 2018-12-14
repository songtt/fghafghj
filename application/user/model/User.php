<?php
namespace app\user\model;
use think\Db;

class User 
{
    /**
     * 得到列表
     * param data
     */
    public function getUser($uname,$pwd)
    {
        // 查询数据
        $sql = 'SELECT id,username,password,login_time,login_ip,role_id,ctime FROM lz_administrator WHERE username=? AND password=?';
        $one = Db::query($sql,[$uname,$pwd]);

        return $one;
    }

    /**
     * 得到列表
     * param data
     */
    public function getWebmaster($uname,$pwd)
    {
        // 查询数据
        $sql = 'SELECT id,username,password,customer FROM lz_webmanager WHERE username=? AND password=?';
        $one = Db::query($sql,[$uname,$pwd]);

        return $one;
    }
}