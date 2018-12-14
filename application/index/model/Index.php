<?php
namespace app\index\model;
use think\Model;
use think\Db;

class Index extends Model
{
    /**
     *  显示基本信息
     */
    public function add($date)
    {
        $date['ctime'] = time();
        $res = Db::name('users')->insert($date);

        //数据同步users_log
        Db::name('users_log')->insert($date);
        return $res;
    }

    /**
     * 获取js的服务器地址
     */
    public function getJsService()
    {
        $sql = 'SELECT js_server FROM lz_setting';
        $res = Db::query($sql);
        $res['0'] = empty($res) ? '' : $res['0'];
        return $res['0'];
    }

    /**
     * 用户登录
     * param data
     */
    public function userList($username,$password)
    {
        $sql = 'SELECT uid,username,nickname,password,status,type,mail_status FROM lz_users WHERE username=? AND password=?';
        $res = Db::query($sql,[$username,$password]);
        return $res;

    }

    /**
     * 用户账号验证是否通过
     */
    public function mailStatus($email)
    {
        $where = array(
            'email'=>$email,
        );
        $data = array(
            'mail_status'=>1,
        );
        $res = Db::name('users')->where($where)->update($data);
        //数据同步users_log
        Db::name('users_log')->where($where)->update($data);
        return $res;
    }

    /**
     * 用户登录
     * param data
     */
    public function emailOne($email)
    {
        $sql = 'SELECT uid,nickname FROM lz_users WHERE email=? AND mail_status=1';
        $res = Db::query($sql,[$email]);
        return $res;
    }

    /**
     * 用户名是否存在
     * param data
     */
    public function nameOne($name)
    {
        $sql = 'SELECT uid,nickname FROM lz_users WHERE username=?';
        $res = Db::query($sql,[$name]);
        return $res;
    }

    /**
     * 客服列表
     * param data
     */
    public function typeList($data)
    {
        $sql = 'SELECT uid,nickname,qq,type FROM lz_users WHERE status=? AND nickname != ? AND type=? ';
        $res = Db::query($sql,[$data['status'],'',$data['type']]);
        return $res;
    }

    /**
     * 获取客服和商务列表
     */
    public function getusers($type)
    {
        $sql = 'SELECT uid,nickname FROM lz_users WHERE status=? AND nickname != ? AND type=? ';
        $res = Db::query($sql,[1,'',$type]);
        return $res;
    }


    /**
     * 删除lz_webmanager的数据
     * param data
     */
//    public function deleteOne($id)
//    {
//        $sql = 'DELETE FROM lz_webmanager WHERE id=?';
//        $res = Db::execute($sql,[$id]);
//        return $res;
//    }













}
