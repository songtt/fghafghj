<?php
namespace app\api\controller;
use think\Loader;
use think\Request;
use app\user\common\Encrypt as Encrypt;

/**
* 获取广告状态
*/
class GetAdsStatus
{
    public function index(){
        $res = ['status'=>'200','msg'=>'成功','data'=>'hello'];
        return json_encode($res);
    }

    public function read(){
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

    public function charge()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        if($pageParam['status']=='1')
        {
            $encrypt = new Encrypt;
            $urlImg = $encrypt->fb_ucenter_decrypt($pageParam['statsParams'],$key='lezun');
            $pos = strpos($urlImg,"?");
            $params = substr($urlImg, $pos+1);
            $res = explode("&", $params);
            $statsParams = array(
                    'adz_id' => explode("=", $res[0])[1],
                    'ad_id'  => explode("=", $res[1])[1],
                    'pid'    => explode("=", $res[2])[1],
                    'uid'    => explode("=", $res[3])[1],
                    'tc_id'  => explode("=", $res[4])[1],
                    'tpl_id' => explode("=", $res[5])[1],
                    'plan_type' => explode("=", $res[6])[1],
                    'planuid'   => explode("=", $res[7])[1],
                    'site_id'   => explode("=", $res[8])[1],
                    'ip_infos_useradd' => explode("=", $res[9])[1],
                    'user_ip' => explode("=", $res[10])[1],
                    'base_cookies' => explode("=", $res[11])[1],
                    'modle_name'=> explode("=", $res[12])[1],
                    'system_version'=> explode("=", $res[13])[1],
                );
            Loader::model('Chapv')->charge($statsParams);
        }else{
            exit;
        }
    }
}
?>