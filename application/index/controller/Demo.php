<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Loader;
use think\Hook;
use PDO;
use app\user\api\UserApi as UserApi;
use app\user\common\Encrypt as Encrypt;

class Demo extends Controller
{
    public function _empty($name)
    {
        echo '1';exit;
        $this->redirect('Index/index');
    }

    public function demo()
    {
        echo 'not exist html ';
    }

    // hook 行为
    public function test1()
    {
        $uid = 2;
        Hook::listen('auth',$uid); //权限
        echo '2';
    }

    public function sftest()
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','bdroot','Pd36EmQf');
        $asdasdasdsadads = $pdo->prepare("insert into test (test) values(?)");
        $asdasdasdsadads->execute(array('sftest'));
    }
}
