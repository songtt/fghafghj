<?php
namespace app\admin\controller;

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

            // if($uname!='yanfabu'){
            //     $this->_error($info='系统维护中');
            //     exit;
            // }

            //检测验证码输入是否正确
            import("extend.org");
            $code = new verify();
            if(!$code->check($params['verify'],1)){
                $this->_error($info='验证码错误！');
            }

            $UserApi = new UserApi();
            $res = $UserApi->checkUser($uname,$pwd);

            //登录成功进入下一页面
            if(!empty($res)){
                $status = Loader::model('Index')->getTitle($res[0]["id"]);
                //存储session
                $this->_setSession($uname,$pwd,$res,$status);
                $this->_success(array(),$info='登录成功！');
            } else{
                //登录失败
                $this->_error($info='用户名或密码错误！');
            }
        } else{
            $service = Loader::model('Setting')->getJsService();
            $this->assign('service',$service);
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
        exit;
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
            $res = $this->_insert($params,$data);
            if($res>0){
                //保存成功
                $this->_success(array(),$info='注册成功！');
            }else{
                //注册失败
                $this->_error($info='注册失败！');
            }
        } else {
            $res = Loader::model('Index')->getGroup();
            $service = Loader::model('Setting')->getJsService();
            $this->assign('res',$res);
            $this->assign('service',$service);
            return $this->fetch('reg');
        }
    }




    /**
     *   修改账号密码
     */
    public function passrevise()
    {
        $request = Request::instance();
        if($request->isPost()) {
            $params = $request->post();

            if(!in_array('',$params)){
                // 查询原始密码
                $passOldSele = Loader::model('index')->getUpass($params['uname']);
                //用户输入的密码加密
                $Encrypt = new Encrypt();
                $passOld = $Encrypt->fb_ucenter_encrypt($params['pwd_old']);
                //判断 用户输入的原始密码是否正确
                if(!empty($passOldSele)){
                    if($passOld == $passOldSele){
                        if($params['pwd_new_1'] != $params['pwd_old']){
                            //判断 用户输入的2次新密码是否一致
                            if($params['pwd_new_1'] ==$params['pwd_new_2']){
                                $new_password = $Encrypt->fb_ucenter_encrypt($params['pwd_new_1']);
                                $data = array(
                                    'password'=>$new_password,
                                );
                                $update = Loader::model('index')->passEdit($params['uname'],$data);
                                if($update >=0){
                                    $this->_success(array(),'修改成功');
                                }else{
                                    $this->_error('修改失败');
                                }
                            }else{
                                $this->_error('2次新密码不一致');
                            }
                        }else{
                            $this->_error('新密码不能和旧密码相同');
                        }
                    }else{
                        $this->_error('旧密码输入错误');
                    }
                }else{
                    $this->_error('无此用户');
                }
            }else{
                $this->_error('用户名或密码不能为空');
            }
        } else {
            return $this->fetch('passrevise');
        }



    }




    /**
     * 生成验证码
     */
    public function verifyCom()
    {
        //生成验证码
        import("extend.org");
        $verify = new verify();
        $verify->entry(1);
    }

    /**
     * 生成极验验证码
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

    public function demo()
    {
        $Redis = new \org\Redis;
        
        $uid = $Redis::get('c_uid');
        if(empty($uid)){
            $Redis::set('c_uid',11,60);
        }else{
            $uid = $Redis::get('c_uid');
            echo $uid;
        }
    }

    public function demo2()
    {
        return $this->fetch('demo2');
    }

    public function demo3()
    {
        $uid = 1;
        Hook::listen('auth',$uid); //权限
        echo 'demo3-index';
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
     * 将新建的用户信息插入到数据库中
     */
    private function _insert($params,$data)
    {
        $res = Loader::model('Index')->add($data);
        if($res>=0){
            $arr = Loader::model('Index')->getId($data);
            $access = array(
                'group_id' => $params['group_id'],
                'uid' => $arr[0]['id'],
            );
            $res = Loader::model('Index')->insertAccess($access);
            if($res<0){
                //如果lz_auth_group_access插入数据失败,则删除lz_administrator的数据
                Loader::model('Index')->deleteOne($arr[0]['id']);
            }
        }
        return $res;
    }

    /**
     * 存储session
     */
    private function _setSession($uname,$pwd,$res,$status)
    {
        if($uname == 'yfb001'){
            $uname = 'admin';
        }
//        $Redis = new \org\Redis;
//        $Redis::set('user_login_uname',$uname,7200);
        Session::set('user_login_uname',$uname);
        Session::set('uname',$uname);
//        Session::set('pwd',$pwd);
        Session::set('user_login_id',$res[0]["id"]);
        Session::set('status',$status[0]['status']);
    }



    public function adinfo()
    { 
        
        $a = array(
            'adinfo' => '1' 
        );
        $a = json_encode($a);
        // echo "console.log('".json_encode($a)."');";
        return $a;
        // $request = Request::instance();
        // $params = $request->post();
        // dump($params);exit;
        // $ad_id = $params['ad_id'];
        // $pid = $params['pid'];
        // $adzid = $params['adz_id'];
        // $uid = $params['uid'];

        // $AdsApi = new AdsApi();
        // $res = $AdsApi->adinfo($params);
        // return $res;
    }

}
