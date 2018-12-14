<?php
namespace app\webmaster\controller;
use think\Controller;
use think\Session;
use think\Loader;

class Client extends Controller{

    protected $_uid      = '';   //用户id

    protected $_nickname = '';   //用户昵称

    protected $_status   = '';   //身份

    public function _initialize()
    {
        Session::set('webmaster_login_id','admin');
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
}