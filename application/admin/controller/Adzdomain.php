<?php
/* 
 * 网站与广告位管理
 * date   2016-6-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Loader;
use think\Request;
use think\Hook;

class Adzdomain extends Admin
{

    /**
     * 广告位域名列表
     */
    public function list()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        $pageParam['searchname'] = empty($pageParam['searchname']) ? 'adz_id' : $pageParam['searchname'];
        $total = Loader::model('Adzdomain')->getListCount($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = Loader::model('Adzdomain')->getList($Page->firstRow,$Page->listRows,$pageParam);

        $this->assign('domain',$pageParam);
        $this->assign('page',$show);
        $this->assign('one',$res);
        return $this->fetch('adzdomain-list');
    }

    /**
     * 广告位域名新增
     */
    public function add()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            //获取参数
            $params = $request->post();
            //添加广告位域名
            $res = Loader::model('Adzdomain')->add($params);
            if($res>0){
                $this->_success("",'广告位域名添加成功');
            }else{
                $this->_error('广告位域名添加失败');
            }
        }else{
            return $this->fetch('adzdomain-add');
        }

    }

    /**
     * 广告位域名编辑
     */
    public function edit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //获取参数
        $params = $request->param('');
        if($request->isPost()){
            //编辑广告位域名
            $res = Loader::model('Adzdomain')->edit($params);
            if($res>0){
                $this->_success("",'广告位域名编辑成功');
            }else{
                $this->_error('广告位域名编辑失败');
            }
        }else{
            //获取该广告位的域名
            $res = Loader::model('Adzdomain')->getadzdomain($params['adz_id']);
            $this->assign('one',$res[0]);
            return $this->fetch('adzdomain-edit');
        }
    }


    /**
     * 删除 ajax
     */
    public function del()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $adz_id = $request->post('adz_id');
        $res = Loader::model('Adzdomain')->delOne($adz_id);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 查询站长是否存在
     */
    public function adzcheck()
    {
        $resquest = Request::instance();
        $params = $resquest->post();
        if(!empty($params)){
            $res = Loader::model('Adzdomain')->adzcheck($params['adz_id']);
        }
        if(!empty($res)){
            echo $res[0]['adz_id'];
        }else{
            echo 0;
        }
    }
}