<?php
/* 网站分类
 * @date   2016-6-23
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Db;
use think\Hook;

class Classes extends Admin
{
    /**
     * users list
     * 网站分类列表
     */
    public function list()
    {
        Hook::listen('auth',$this->_uid); //权限
        $sele = Loader::model('classes')->classList();

        $this->assign('classes_list',$sele);
        return $this->fetch('list');
    }
    
    /**
     * 网站分类添加
     */
    public function add()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $class_post = $request->Post();
        if(!in_array('',$class_post)){
            $data = array(
              'class_name'=>$class_post['class_name'],
              'type'      =>$class_post['type'],
            );
            $classname = Loader::model('classes')->classname($class_post['class_name']);
            if(empty($classname)){
                  $int = Loader::model('classes')->add($data);
               if($int >0){
                   $this->redirect('list');
               }else{
                   $this->error();
               }
           }else{
                $this->error('此网站分类已添加');
           }
        }else{
            $this->redirect('不能为空','add');
        }
    }

    /**
     *   分类修改
     */
    public function edit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //查询默认值
        $id = $request->get('classid');
        if($request->post()){
            $params = $request->post();
            $id = $params['classid'];
            $res = Loader::model('classes')->editClass($params);
            if($res >0){
                //写操作日志
                $this->logWrite('0054',$params['classid'],$params['class_name']);
                $this->redirect('list');
            }else{
                $this->_error();
            }
        }
            $res = Loader::model('classes')->minClass($id);
            $this->assign('find', $res);
            $this->assign('uid', $id);
        return $this->fetch('classes-edit');
    }

    /**
     *   修改子分类名称
     */
    public function editSub()
    {
        $request = Request::instance();
        // Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        $res = Loader::model('classes')->editClass($params);
        if($res >0){
            //写操作日志
            $this->logWrite('0053',$params['relation_class_id'],$params['class_name']);
            $this->_success();
        }else{
            $this->_error();
        }
    }
    
    /**
     *   删除分类
     */
    public function delete()
    {
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        $params = $request->param();
        if(!empty($params['relation_class_id'])){
            $data = array(
                // relation_class_id 代表子分类的class_id
                'class_id' => $params['relation_class_id'],
                'relation' => $params['relation'],
            );
        }else{
            $data = array(
                'class_id' => $params['class_id'],
            );
        }
        $del = Loader::model('classes')->delClass($data);
        if($del >0){
            //写操作日志
            $this->logWrite('0055',$data['class_id']);
            $this->_success();
        }else{
            $this->_error();
        }

    }

    /**
     *   添加网站子分类
     */
    public function relationclassadd()
    {
//        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        $params = $request->post();
        if(($request->isPost())){
            if(!empty($params['class_name'])){
                $data = array(
                    'type'=>1,
                    'class_name'=>$params['class_name'],
                    'relation'=>$params['relation'],
                );
                $res = Loader::model('classes')->add($data);
                if($res >0){
                    //写操作日志
                    $this->logWrite('0052',$params['class_name']);
                    $this->redirect('list');
                }else{
                    $this->_error();
                }
            }else{
                $this->success('子分类不能为空','relationclassadd');
            }

        }else{
            $res = Loader::model('classes')->addClass();
            $this->assign('res',$res);
        }

        return $this->fetch('relation-class-add');
    }
}