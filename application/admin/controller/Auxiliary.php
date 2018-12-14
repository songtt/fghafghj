<?php
/* 基本设置
 * date   2016-6-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Loader;
use think\Hook;
use think\Db;
use app\user\api\DelApi as DelApi;

class Auxiliary extends Admin
{
    /**
     * 手机机型列表
     */
    public function modleList()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        $params['status'] = empty($params['status']) ? 'all' : $params['status'];
        $params['search'] = empty($params['search']) ? "" : $params['search'];
        //获取手机机型列表
        $total = Loader::model('Auxiliary')->getListCount($params);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show(Request::instance()->action(),$params);
        $res = Loader::model('Auxiliary')->getList($params,$Page->firstRow,$Page->listRows);

        $this->assign('param',$params);
        $this->assign('page',$show);
        $this->assign('one',$res);
        return $this->fetch('modle-list');
    }

    /**
     * 手机机型编辑
     */
    public function edit()
    {
        $request = Request::instance();
        //获取参数
        $params = $request->param();
        if($request->isPost()){
            $params['type_pay'] = empty($params['type_pay']) ? 0 : $params['type_pay'];
            //编辑机型
            $res = Loader::model('Auxiliary')->edit($params);
            if($res>0){
                $this->_success("",'机型编辑成功');
            }else{
                $this->_error('机型编辑失败');
            }
        }else{
            //判断该机型是否已经添加
            $res = Loader::model('Auxiliary')->getOne($params['id']);
            $this->assign('id',$params['id']);
            $this->assign('one',$res[0]);
            return $this->fetch('modle-edit');
        }
    }

    /**
     * 手机机型添加
     */
    public function add()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            //获取参数
            $params = $request->post();
            //判断该机型是否已经添加
            $res = Loader::model('Auxiliary')->getName($params['name']);
            //如果机型已存在则不添加
            if(!empty($res)){
                $this->_error('该机型已存在');
            }else{
                //添加机型
                $res = Loader::model('Auxiliary')->add($params);
                if($res>0){
                    $this->_success("",'机型添加成功');
                }else{
                    $this->_error('机型添加失败');
                }
            }
        }else{
            return $this->fetch('modle-add');
        }
    }

    /**
     * 删除 ajax
     */
    public function del()
    {
        $request = Request::instance();
//        Hook::listen('auth',$this->_uid); //权限
        $id = $request->post('id');
//        $UserApi = new DelApi();
//        $UserApi->del($pid,'ad');
        $res = Loader::model('Auxiliary')->delOne($id);
        if($res>0){
//            //写操作日志
//            $this->logWrite('0027',$pid);
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 首页
     */
    public function list()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        $total = Loader::model('Auxiliary')->mobileCount($params);
        $Page = new \org\PageUtil($total,$params,100);
        $show = $Page->show(Request::instance()->action(),$params);
        $res = Loader::model('Auxiliary')->mobileLst($Page->firstRow,$Page->listRows);
        //获取总机型数量
        $num = db::query('SELECT sum(num) AS num FROM lz_mobile_modle');
        //计算机型百分百比
        foreach ($res as $key => $value) {
            $res[$key]['percent'] = floor($value['num'] / $num[0]['num'] *100* 100)/100;
            $res[$key]['ctime'] = date('Y-m-d', strtotime('-1 day'))  ;
        }
        $this->assign('moblie',$res);
        $this->assign('page',$show);
        $this->assign('num',$num[0]);
        return $this->fetch('moblie-list');
    }

}