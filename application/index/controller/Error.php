<?php
namespace app\index\controller;
use think\Controller;

class Error extends Controller {

    /**
     * 空控制器显示
     */
    public function index()
    {
        return $this->fetch('index@public/404');
    }

    /**
     * 空操作器显示
     */
    public function _empty()
    {
        return $this->fetch('index@public/404');
    }

}