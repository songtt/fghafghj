<?php
namespace app\home\controller;
use think\Controller;
use think\Session;
use think\Request;
use think\Loader;

class Client extends Controller
{
    protected  $_uid      = '';   //用户id

    protected  $_nickname = '';   //用户昵称

    protected  $_status   = '';   //身份

    public function _initialize()
    {
     // $this->getCuid();//判断session是否过期
        $this->getCuid('user_login_uname');   
     // $this->assign('meta_title','后台管理系统');
        $this->_uid = empty(Session::get('user_login_id')) ? Session::get('login_id') : Session::get('user_login_id');
        $this->getService();//获取js服务器地址
        $this->getName();//获取登录的用户名
        $this->getRole();//相应的角色只能查看自己的页面，防止站长等通过输入url的方式看到客服等的页面结构
//        $this->updateUsersMoney();//将log表中的数据更新到站长余额中，同时删除已更新的数据
        // $this->_nickname = $_SESSION['nickname'];
        // $this->assign('uid',$this->_uid);
        // define('NICKNAME', $_SESSION['nickname']);
        // $this->assign('username',$this->_nickname);
        // $this->assign('HTTP_HOST',$_SERVER['HTTP_HOST']);
//         $uid = Session::get('user_login_id');
//        $this->_uid = '1001';
        if(!$this->_uid){
//             $this->redirect('index/index/login');
             $this->redirect('/');
         }
    }

    public function getRole()
    {
        if(empty(Session::get('user_login_id'))){
            $controller=request()->controller();
            if($controller == 'Webmaster'){
                if(empty(Session::get('webmasterUid'))){
                    $this->redirect('/');
                }
            }elseif($controller == 'Advertiser'){
                if(empty(Session::get('advertiserUid'))){
                    $this->redirect('/');
                }
            }elseif($controller == 'Customer'){
                if(empty(Session::get('customerUid'))){
                    $this->redirect('/');
                }
            }elseif($controller == 'Business'){
                if(empty(Session::get('businessUid'))){
                    $this->redirect('/');
                }
            }
        }
    }

    /**
     * 空函数
     */
    public function _empty()
    {
        return $this->fetch('index@public/404');
    }

    /**
     * 获取js服务器地址
     */
    protected function getService()
    {
        $service = Loader::model('Users')->getJsService();
        $this->assign('service',$service);
    }

    /**
     * 获取登录的用户名
     */
    protected function getName()
    {
        $request = Request::instance();
        $type= $request->controller();
        if($type == 'Webmaster'){
            $uid = empty(Session::get('webmasterUid')) ? Session::get('login_id') : Session::get('webmasterUid');
            $webmasterName = Loader::model('Users')->getUname($uid);
            $this->assign('webmaster_login_name',$webmasterName);
            if(empty($webmasterName)){
                $this->redirect('/');
            }
        }elseif($type == 'Advertiser'){
            $uid = empty(Session::get('advertiserUid')) ? Session::get('login_id') : Session::get('advertiserUid');
            $advertiserName = Loader::model('Users')->getUname($uid);
            if(empty($advertiserName)){
                $this->redirect('/');
            }
            $this->assign('advertiser_login_name',$advertiserName);
        }elseif($type == 'Customer'){
            $uid = empty(Session::get('customerUid')) ? Session::get('login_id') : Session::get('customerUid');
            $customerName = Loader::model('Users')->getUname($uid);
            if(empty($customerName)){
                $this->redirect('/');
            }
            $this->assign('customer_login_name',$customerName);
        }elseif($type == 'Game'){
            $uid = empty(Session::get('gameUid')) ? Session::get('login_id') : Session::get('gameUid');
            $gameName = Loader::model('Users')->getUname($uid);
            if(empty($gameName)){
                $this->redirect('/');
            }
            $this->assign('game_login_name',$gameName);
        }else{
            $uid = empty(Session::get('businessUid')) ? Session::get('login_id') : Session::get('businessUid');
            $businessName = Loader::model('Users')->getUname($uid);
            if(empty($businessName)){
                $this->redirect('/');
            }
            $this->assign('business_login_name',$businessName);
        }
    }

    /**
     * 将log表中的数据更新到站长余额中，同时删除已更新的数据
     */
    protected function updateUsersMoney()
    {
        $res = Loader::model('Users')->getLogMoney();
        $money = array();
        //将从log表中查询出来的数据组装
        foreach($res as $key=>$value){
            $id[] = $value['id'];
            $idlast = $value['id'];
            $money[$value['uid']]['money'] = 0;
        }
        foreach($res as $key=>$value){
            $money[$value['uid']]['money'] += $value['money'];
            $money[$value['uid']]['uid'] = $value['uid'];
        }
        //修改所有需要修改的金额
        foreach($money as $key=>$value){
            $money = Loader::model('Users')->getMoney($value);
            $map['money'] = empty($money) ? 0 : $money[0]['money'] + $value['money'];

            Loader::model('Users')->editorMoney($value,$map);
        }
        //删除已经更新过的金额
        if(!empty($id)){
            Loader::model('Users')->deleteLog($id[0],$idlast);
        }
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

    /**
     * 获取cuid
     */
    protected function getCuid($name)
    {
        // $Redis = new \org\Redis;
       $uname = Session::get($name);
       //  $uname = $Redis::get($name);
       // $uname = Session::get('webmasterUid');
        if(empty($uname)){
            $this->redirect('/');
        }
        // $Redis::set('user_login_uname',$uname,7200);
        Session::set('user_login_uname',$uname);
        return $uname;
    }
}
