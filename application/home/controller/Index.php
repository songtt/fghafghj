<?php
namespace app\home\controller;
use think\Controller;
use think\Loader;
use think\Request;
use app\user\common\Encrypt;
use think\Db;
use think\response\Redirect;
use think\Session;
use org\Verify as Verify;

class Index extends Controller
{
    public function demo()
    {
        echo 'asdsd';
       echo 'index / index';
    }

    /**
    * 用户登录
     **/
//    public function login(){
//
//        return $this->fetch('login');
//    }

    /**
    * 注册
    */
//    public function register(){
//        return $this->fetch('register');
//    }


    /**
     * 生成验证码
     */
    public function verify()
    {
        //生成验证码
        import("extend.org");
        $verify = new verify();
        $verify->entry(1);
    }

    /**
     * 用户退出登录界面
     */
    public function logout()
    {
//        $session = Session::delete('login_id');
//        if(Session::get('type') ==1){
//            //获取当前Session
//            $session = Session::delete('webmasterUid');
//        }elseif(Session::get('type') ==2){
//            $session=Session::delete('advertiserUid');
//        }elseif(Session::get('type') ==3){
//            $session=Session::delete('customerUid');
//        }else{
//            $session=Session::delete('businessUid');
//        }
        session_start();
        session_destroy();
        return $this->redirect('/');

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
     * error函数
     */
    public function _error($info)
    {
        $data['status']  = 0;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

}
