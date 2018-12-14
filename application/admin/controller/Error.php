<?php
namespace app\admin\controller;
use think\Controller;

class Error extends Controller {

    /**
     * 空控制器显示
     */
    public function index()
    {
        return $this->fetch('index@public/404');
    }

    public function _empty()
    {
        return $this->fetch('index@public/404');
    }
}