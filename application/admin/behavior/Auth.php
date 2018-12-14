<?php
// 权限判断类
namespace app\admin\behavior;
use think\Request;

class Auth
{
    public function run(&$params)
    {
        echo 'run';
    }

    /**
     * 权限
     */
    public function auth(&$params,&$func)
    {
        $Auth = new \org\Auth();
        $uid = $params;
        if(empty($func)){
            $request = Request::instance();
            $func = $request->controller().'-'.$request->action();
        }
        $func = ucfirst($func);
        $res = $Auth->check($func,$uid);
        if(!$res){
            echo '<h1>当前登陆角色没有权限,请联系管理员</h1>';
            exit;
        }
    }

}