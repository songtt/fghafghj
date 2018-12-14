<?php
/* 广告管理
 * date   2016-6-2
 */
namespace app\webmaster\controller;

use think\Loader;
use think\Request;
use think\Controller;
use think\Session;

class Webmaster extends Client
{
    /**
     * 站长列表
     */
    public function overduelist()
    {
        $request = Request::instance();
        $params = $request->param();
        $res = Loader::model('Web')->getManagerList();
        //将查询出来的数据进行排序处理，并且将过期的站长标红
        $arr = array();$array=array();
        foreach($res as $key=>$value){
            $res[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
            $time = strtotime(date('Y-m-d',$value['start_time']));
            $now = time();
            if($now - $time >= 604800){
                $res[$key]['colour'] = 1;
                if($value['shows'] == 0){
                    $arr[] = $res[$key];
                }else{
                    $array[] = $res[$key];
                }
            }else{
                $res[$key]['colour'] = 0;
                $array[] = $res[$key];
            }
        }
        $arr = array_merge($arr,$array);
        $params['searchName'] = empty($params['searchName']) ? 'web_name' : $params['searchName'];
        $this->assign('one',$arr);
        $this->assign('params',$params);
        return $this->fetch('web/web-overduelist');
    }
}