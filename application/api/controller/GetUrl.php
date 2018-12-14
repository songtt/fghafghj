<?php
namespace app\api\controller;
use think\Loader;
use think\Request;

/**
* 返回广告图片链接和点击链接
*/
class GetUrl
{
    public function index(){
        $res = ['status'=>'200','msg'=>'成功','data'=>''];
        return json_encode($res);
    }

    //从s或news获取返回信息
    public function read($adz_id){
        $request = Request::instance();
        $pageParam = $request->param();
        $res = Loader::model('News')->prepareData($pageParam['adz_id']);
        return $res;
    }    

    public function create(){
    } 
    
    public function save(){
    }    

    public function edit($adz_id){
    }

    public function update($adz_id){
    }    

    public function delete($adz_id){
    }
}
?>