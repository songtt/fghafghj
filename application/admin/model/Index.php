<?php
/**
 * 新建账号
 * date   2016-6-16
 */
namespace app\admin\model;
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
        $res = Db::name('administrator')->INSERT($data);
        return $res;
    }
    /**
     * 验证注册用户名是否唯一
     * param data
     */
    public function getId($data)
    {
        $sql = 'SELECT id FROM lz_administrator WHERE username=?';
        $res = Db::query($sql,[$data['username']]);
        return $res;
    }

    /**
     * 新建用户权限
     * param data
     */
    public function insertAccess($access)
    {
        $sql = 'INSERT INTO lz_auth_group_access VALUES (?,?)';
        $res = Db::query($sql,[$access['uid'],$access['group_id']]);
        return $res;
    }

    /**
     * 删除lz_administrator的数据
     * param data
     */
    public function deleteOne($id)
    {
        $sql = 'DELETE FROM lz_administrator WHERE id=?';
        $res = Db::execute($sql,[$id]);
        return $res;
    }

    /**
     * 获得所有管理员的权限名称
     * param data
     */
    public function getGroup()
    {
        $sql = 'SELECT id,title FROM lz_auth_group ';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 获得登录管理员的权限名称
     * param data
     */
    public function getTitle($id)
    {
        $sql = 'SELECT b.status FROM lz_auth_group_access as a LEFT JOIN lz_auth_group as b ON a.group_id=b.id WHERE a.uid=?';
        $res = Db::query($sql,[$id]);
        return $res;
    }

     /**
     * 获取登录用户名称
     */
    public function getUname($id)
    {
        $sql = 'SELECT title FROM lz_auth_group WHERE status=?';
        $res = Db::query($sql,[$id['status']]);
        $res = empty($res) ? '' : $res['0'];
        return $res;
    }

    /**
     * 获取广告商总额
     */
    public function getMoney()
    {
        $sql = 'SELECT username,money,adv_money FROM lz_users WHERE type=? AND status=?';
        $res = Db::query($sql,[2,1]);
        return $res;
    }

    /**
     * 获取广告商基本设置
     */
    public function getSetAdv()
    {
        $sql = 'SELECT adv_money FROM lz_setting';
        $res = Db::query($sql);
        if(empty($res)){
            return '';
        }else{
            return $res['0'];
        }
    }

    /**
     * 获取用户密码
     */
    public function getUpass($username)
    {
        $sql = 'SELECT password FROM lz_administrator WHERE username=?';
        $res = Db::query($sql,[$username]);
        $res = empty($res) ? '' : $res['0']['password'];
        return $res;
    }

    /**
     * 获取用户密码
     */
    public function passEdit($uname,$data)
    {
        $map=array(
            'username' =>$uname,
        );
        $res = Db::name('administrator')->where($map)->update($data);
        return $res;
    }
}