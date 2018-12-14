<?php
/* 基本设置
 * date   2016-6-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Loader;
use think\Hook;

class Deduction extends Admin
{
    // /**
    //  * 站点扣量设置
    //  */
    // public function sitededuction()
    // {
    //     $request = Request::instance();
    //     Hook::listen('auth',$this->_uid); //权限
    //     if($request->isPost()){
    //         $params = $request->post();
    //         //更新数据库中的数据
    //         Loader::model('Deduction')->siteDeduction($params);
    //         //将更新后的数据组装，重定向时直接在前台显示
    //         $pms = array(
    //             'adz_id' =>$params['adz_id'],
    //             'web_deduction' =>$params['web_deduction'],
    //             'adv_deduction' =>$params['adv_deduction'],
    //             'cmd_flag' => 1,
    //         );
    //         $this->redirect('Deduction/zonededuction',$pms);
    //     } else{
    //         $res = Loader::model('Deduction')->getSite();
    //         $data = $this->_getData();
    //         $data['id'] = empty($request->param('site_id')) ? 0 : $request->param('site_id');
    //         $data['stats'] = 'site';
    //         $this->assign('res',$res);
    //         $this->assign('data',$data);
    //         return $this->fetch('deduction');
    //     }
    // }

    /**
     * 查询网站是否存在
     **/
    // public function siteone(){
    //     $resquest = Request::instance();
    //     $params = $resquest->post();
    //     if(!empty($params)){
    //         $res = Loader::model('Deduction')->postSite($params);
    //     }

    //     if(!empty($res)){
    //         echo $res[0]['site_id'];
    //     }else{
    //         echo 0;
    //     }
    // }

    /**
     * 广告位扣量设置
     */
    public function zonededuction()
    {
        $request = Request::instance();
        // Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            // 更新数据库中的数据
            Loader::model('Deduction')->zoneDeduction($params);
            //将更新后的数据组装，重定向时直接在前台显示
            $pms = array(
                'adz_id' =>$params['adz_id'],
                'web_deduction' =>$params['web_deduction'],
                'adv_deduction' =>$params['adv_deduction'],
                'cmd_flag' => 1,
            );
            $this->redirect('/admin/deduction/zonededuction?stats=zone',$pms);
        } else{
            $res = Loader::model('Deduction')->getzone();

            $data = $this->_getData();
            $data['id'] = empty($request->param('zone_id')) ? 0 : $request->param('zone_id');
            $data['stats'] = 'zone';
            $this->assign('res',$res);
            $this->assign('data',$data);
            return $this->fetch('deduction');
        }
    }

     /**
     * 查询站长是否存在
     **/
    public function zoneone(){
        $resquest = Request::instance();
        $params = $resquest->post();
        if(!empty($params)){
            $res = Loader::model('Deduction')->postZone($params);
        }
        if(!empty($res)){
            echo json_encode($res);
        }else{
            echo 0;
        }
    }

    /**
     * 站长扣量设置
     */
    public function webdeduction()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //更新数据库中的数据
            Loader::model('Deduction')->webDeduction($params);
            //将更新后的数据组装，重定向时直接在前台显示
            $pms = array(
                'uid' =>$params['uid'],
                'web_deduction' =>$params['web_deduction'],
                'adv_deduction' =>$params['adv_deduction'],
                'cmd_flag' => 1,
            );
            $this->redirect('Deduction/webdeduction',$pms);
        } else{
            $res = Loader::model('Deduction')->getWeb();
            $data = $this->_getData();
            $data['id'] = empty($request->param('uid')) ? 0 : $request->param('uid');
            $data['stats'] = 'webmaster';
            $this->assign('res',$res);
            $this->assign('data',$data);
            return $this->fetch('deduction');
        }
    }

    /**
     * 查询站长是否存在
     **/
    public function webuserone(){
        $resquest = Request::instance();
        $params = $resquest->post();
        if(!empty($params)){
            $res = Loader::model('Deduction')->postWeb($params);
        }

        if(!empty($res)){
            echo $res[0]['uid'];
        }else{
            echo 0;
        }
    }

    /**
     * 广告扣量设置
     */
    public function adsdeduction()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //更新数据库中的数据
            Loader::model('Deduction')->adsDeduction($params);
            //将更新后的数据组装，重定向时直接在前台显示
            $pms = array(
                'ad_id' =>$params['ad_id'],
                'web_deduction' =>$params['web_deduction'],
                'adv_deduction' =>$params['adv_deduction'],
                'cmd_flag' => 1,
            );
            $this->redirect('Deduction/adsDeduction',$pms);
        } else{
            $res = Loader::model('Deduction')->getAds();
            $data = $this->_getData();
            $data['id'] = empty($request->param('ad_id')) ? 0 : $request->param('ad_id');
            $data['stats'] = 'ads';
            $this->assign('res',$res);
            $this->assign('data',$data);
            return $this->fetch('deduction');
        }
    }

    /**
     * 查询广告是否存在
     **/
    public function adsone(){
        $resquest = Request::instance();
        $params = $resquest->post();
        if(!empty($params)){
            $res = Loader::model('Deduction')->postAds($params);
        }

        if(!empty($res)){
            echo $res[0]['ad_id'];
        }else{
            echo 0;
        }
    }

    /**
     * 计划扣量设置
     */
    public function plandeduction()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //更新数据库中的数据
            Loader::model('Deduction')->planDeduction($params);
            //将更新后的数据组装，重定向时直接在前台显示
            $pms = array(
                'pid' =>$params['pid'],
                'web_deduction' =>$params['web_deduction'],
                'adv_deduction' =>$params['deduction'],
                'cmd_flag' => 1,
            );
            $this->redirect('Deduction/plandeduction',$pms);
        } else{
            $res = Loader::model('Deduction')->getPlan();
            $data = $this->_getData();
            $data['id'] = empty($request->param('pid')) ? 0 : $request->param('pid');
            $data['stats'] = 'plan';
            $this->assign('res',$res);
            $this->assign('data',$data);
            return $this->fetch('deduction');
        }
    }

    /**
     * 查询计划是否存在
     **/
    public function planone(){
        $resquest = Request::instance();
        $params = $resquest->post();
        if(!empty($params)){
            $res = Loader::model('Deduction')->postPlan($params);
        }

        if(!empty($res)){
            echo $res[0]['pid'];
        }else{
            echo 0;
        }
    }

    /**
     * 全局扣量设置
     */
    public function alldeduction()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //更新数据库中的数据
            Loader::model('Deduction')->UpdateDeduction($params);
            $this->redirect('Deduction/alldeduction',['cmd_flag' => '1']);
        } else{
            $res = Loader::model('Deduction')->getone();
            $data['keep'] = $request->param('cmd_flag');
            $data['stats'] = 'all';
            $data['status'] = $_SESSION['think']['status'];
            $this->assign('res',$res);
            $this->assign('data',$data);
            return $this->fetch('deduction');
        }
    }

    /**
     * 组装数据
     */
    private function _getData()
    {
        $request = Request::instance();
        $data['keep'] = $request->param('cmd_flag');
        $data['web_deduction'] = empty($request->param('web_deduction')) ? '0': $request->param('web_deduction');
        $data['adv_deduction'] = empty($request->param('adv_deduction')) ? '0' : $request->param('adv_deduction');
        $data['status'] = $_SESSION['think']['status'];

        return $data;
    }
}