<?php
namespace app\webmaster\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Loader;
use app\user\api\UserApi as UserApi;
use org\Verify as Verify;
use app\user\common\Encrypt as Encrypt;
use think\Hook;
use app\admin\api\AdsApi as AdsApi;

class Index extends controller
{

    /**
     * 用户登录界面
     */
    public function login()
    {
        if(Request::instance()->isPost()){
            //获取用户所填的账号和密码
            $params = Request::instance()->post();
            $uname = $params['uname'];
            $pwd = $params['pwd'];

            $UserApi = new UserApi();
            $res = $UserApi->checkWebmaster($uname,$pwd);

            //登录成功进入下一页面
            if(!empty($res)){
                //存储session
                $this->_setSession($uname,$pwd,$res);

                $this->_success(array(),$info='登录成功！');
            } else{
                //登录失败
                $this->_error($info='用户名或密码错误！');
            }
        } else{
            return $this->fetch('login');
        }
    }

    /**
     * 用户退出登录界面
     */
    public function logout()
    {
        //获取当前Session
        $session = Session::get();
        if($this->is_login()){
            if (!empty($session)) {
                $_SESSION = [];
            }
            session_unset();
            session_destroy();
            return $this->redirect('index/login');
        } else {
            return $this->redirect('index/login');
        }
    }

    /**
     * 注册新用户
     */
    public function reg()
    {
        // echo 404;exit;
        $request = Request::instance();
        if($request->isPost()) {
            $params = $request->post();
            //检测用户输入信息是否正确
            $this->_vilodate($params);
            //用户密码加密
            $Encrypt = new Encrypt();
            $password = $Encrypt->fb_ucenter_encrypt($params['pwd']);
            //组装数据
            $data = $this->_getData($params,$password);

            //验证新会员注册时用户名必须唯一
            $uname = Loader::model('Index')->getId($data);
            if(!empty($uname)){
                $this->_error($info='该用户已被注册！');
            }
            //将新建的用户信息插入到数据库中
            $res = Loader::model('Index')->add($data);
            if($res>0){
                //保存成功
                $this->_success(array(),$info='注册成功！');
            }else{
                //注册失败
                $this->_error($info='注册失败！');
            }
        } else {
            return $this->fetch('reg');
        }
    }
    
    /**
     * 生成验证码
     */
    public function verify()
    {
        $Check = new \org\geetest\web\StartCaptchaServlet;
        $response = $Check->geetest();
        echo $response;

    }

    /**
     * 二次验证
     */
    public function verifyAgain()
    {
        $Check = new \org\geetest\web\VerifyLoginServlet;
        $response = $Check->geetest();
        echo $response;

    }

    /**
     * 用户登录uid是否存在
     * return boolean
     */
    private function is_login()
    {
        if(empty($_SESSION)){
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
     * error函数
     */
    public function _error($info)
    {
        $data['status']  = 0;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * 空函数
     */
    public function _empty()
    {
        return $this->fetch('index@public/404');
    }

    /**
     * 处理注册数据
     */
    private function _getData($params,$password)
    {
        //组装数据
        $data = array(
            'username' => $params['uname'],
            'password' => $password,
            'customer' => $params['customer'],
        );
        return $data;
    }

    /**
     * 检测用户输入信息是否正确
     */
    private function _vilodate($params)
    {
        //验证用户注册时的账号和密码不能为空
        if($params['uname'] == '' || $params['pwd'] == ''){
            $this->_error($info='用户名和密码不能为空！');
        }
    }

    /**
     * 存储session
     */
    private function _setSession($uname,$pwd,$res)
    {
//        $Redis = new \org\Redis;
//        $Redis::set('uname',$uname,1800);
        
        Session::set('webmaster_login_id',$res[0]["customer"]);
    }

}