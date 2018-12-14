<?php
namespace app\user\api;
use app\user\model\User as User;
use app\user\common\Encrypt as Encrypt;

class UserApi 
{       
    public function __construct(){
        $this->model = new User;
        $this->common = new Encrypt;
    }

    /**
     * check user and pwd 
     */
    public function checkUser($uname = '',$pwd = ''){

        $pwd = $this->common->fb_ucenter_encrypt($pwd);

        $user = $this->model->getUser($uname,$pwd);

        return $user;
    }

    /**
     * check user and pwd
     */
    public function checkWebmaster($uname = '',$pwd = ''){

        $pwd = $this->common->fb_ucenter_encrypt($pwd);

        $user = $this->model->getWebmaster($uname,$pwd);

        return $user;
    }
   
}