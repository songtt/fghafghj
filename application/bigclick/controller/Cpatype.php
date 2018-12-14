<?php

/**
 * 产品分类  
 * date 2018年9月10日 
 */
namespace app\bigclick\controller;
use think\Controller;
use think\Loader;
use think\Request;
use think\Hook;
use think\Cache;

class Cpatype extends Admin
{
    //后退不报错
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    // 产品分类列表
    public function type()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $res = Loader::model('Cpatype')->typeList();
        $this->assign('list',$res);
        return $this->fetch('type');
    }


    //新增产品了分类
    public function typeadd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
		$params = $request->post();
        if ($request->isPost()){
            $params['ctime'] = time();
        	$res = Loader::model('Cpatype')->addType($params);
        	if($res>0){
        		$this->redirect('type');
        	}else{
        		$this->_error();
        	}
        }else{
        	return $this->fetch('type-add');
        }
    }

    //编辑产品分类
    public function edit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        if ($request->isPost()){
            $where['id'] = $params['id'];
            $data['type_name'] = $params['type_name'];
            $data['type_info'] = $params['type_info'];
            $res = Loader::model('Cpatype')->typeUpdate($where,$data);
            $this->redirect('type');
        }else{
            $res = Loader::model('Cpatype')->typeEdit($params);
            $this->assign('list',$res);
            return $this->fetch('type-edit');
        }
        
    }



}