<?php
namespace app\webmaster\controller;
use think\Controller;
use think\Session;
use think\Loader;

class Admin extends Controller{

    protected $_uname      = '';   //用户id

    protected $_status   = '';   //身份

    public function _initialize()
    {
        $this->_uname = Session::get('user_login_uname');
        if(!$this->_uname){
            $this->redirect('Admin/Index/login');
        }
        if(Session::get('user_login_id')){
            Session::set('webmaster_login_id','admin');
        }else{
            $name = Session::get('user_login_uname');
            Session::set('webmaster_login_id',$name);
        }
        $this->assign('meta_title','后台管理系统');

    }

    /**
     * 空函数
     */
    public function _empty()
    {
        return $this->fetch('index@public/404');
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

    /**
     * 获取cuid
     */
    protected function getCuid($name)
    {
        $Redis = new \org\Redis;
        
        $uname = $Redis::get($name);
        if(empty($uname)){
            $this->redirect('Index/login');
        }
        $Redis::set('uname',$uname,1800);
        return $uname;
    }

}