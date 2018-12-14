<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use think\Request;
use think\Loader;

class Base extends Controller
{
    protected  $_uid      = '';   //用户id

    protected  $_nickname = '';   //用户昵称

    protected  $_status   = '';   //身份

    public function _initialize()
    {

    }

    /**
     * 空函数
     */
    public function _empty($name)
    {
        return $this->fetch('public/404');
    }

    /**
     * 用户登录uid是否存在
     * return boolean
     */
    protected function is_login(){
        $user_login_id = $_SESSION['user_login_id'];
        if(empty($user_login_id)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * ajax成功返回
     * param $data array  数据数组
     * param $info string 成功返回的字符串
     * return json
     */
    protected function _success($datas = array(),$info='success'){
        $data['status']  = 1;
        $data['data'] = $datas;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * ajax成功返回
     * param $info string 错误返回的字符串
     * return json
     */
    protected function _error($info='error'){
        $data['status']  = 0;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * 选择显示状态
     */
    protected function _doFlag($flag = '')
    {
        $status = 0;
        switch ($flag) {
            case 'add':
                $status = 1;
                break;
            case 'save':
                $status = 2;
                break;
            case 'edit':
                $status = 3;
                break;
            case 'delete':
                $status = 4;
                break;
            default:
                $status = 0;
                break;
        }
        $this->assign('cmd_status',$status);
    }

}
