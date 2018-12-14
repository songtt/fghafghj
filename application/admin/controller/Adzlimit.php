<?php
/** 二次点击报表
 * date   2017-5-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Hook;
use think\config;

class Adzlimit extends Controller
{
    /**
     *  广告位id 限制
     */
    public function index()
    {
        $request = Request::instance();
    	$params = $request->param();
        //文件地址
        $url = './ad/adzlimit.txt';
        if($request->isPost()){

            //提交的数据  (trim 移除字符串两边空白字符)
            $data = trim($params['limit']);
            //判断提交的id 是否重复
//            $array = explode(',',$data);

//            if(count($array) != count(array_unique($array))){
//                $this->success('广告位id不能重复', 'adzlimit/index');
//            }

            $file = fopen($url,'w+');
            fwrite($file,$data);
            $this->redirect('adzlimit/index');

        }else{
            if(!is_file($url)){
                //文件不存在则创建
                fopen($url,'w+');

            }
            //读取文件数据放入字符串里
            $text = file_get_contents($url);

            $this->assign('text',$text);
        }

        return $this->fetch('adzlimit');
    }


}
