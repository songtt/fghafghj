<?php
/**
 * 新建账号
 * date   2016-6-16
 */
namespace app\webmaster\model;
use think\Db;

class Index extends \think\Model
{
    /**
     * 新建用户
     * param data
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $res = Db::name('webmanager')->INSERT($data);
        return $res;
    }
    /**
     * 验证注册用户名是否唯一
     * param data
     */
    public function getId($data)
    {
        $sql = 'SELECT id FROM lz_webmanager WHERE username=?';
        $res = Db::query($sql,[$data['username']]);
        return $res;
    }

    /**
     * 删除lz_webmanager的数据
     * param data
     */
    public function deleteOne($id)
    {
        $sql = 'DELETE FROM lz_webmanager WHERE id=?';
        $res = Db::execute($sql,[$id]);
        return $res;
    }

}