<?php
/* 广告管理
 * date   2016-6-2
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Hook;
use think\config;
use app\user\api\DelApi as DelApi;


class Ads extends Admin
{
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    /**
     * 广告列表
     */
    public function list()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //广告分类列表
        $pageParam = $request->param('');
        if(isset($pageParam['ptype'])){
            $ptype = $pageParam['ptype'];
        }else{
            $ptype = '';
        }
        $total = Loader::model('Ads')->adLstCount($ptype,$pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['page'] = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Ads')->adLst($Page->firstRow,$Page->listRows,$ptype,$pageParam);
        $data['img'] = Loader::model('Ads')->getImgService();
        $data['img'] = empty($data['img']['img_server']) ? array('img_server' => '/') : $data['img'];
        $judge_name = $request->session('uname');
        if(!isset($pageParam['ads'])){
            $pageParam['ads'] = '';
        }
        $this->assign('ads',$pageParam);
        $this->assign('judge_name',$judge_name);
        $this->assign('ads_list',$res);
        $this->assign('data',$data);
        return $this->fetch('ads-list');
    }

    /**
     * 计划下查看广告
     */
    public function planToAds()
    {
        $pid = Request::instance()->get('pid');
        $res = Loader::model('Ads')->adPlanLst($pid);
        $data['img'] = Loader::model('Ads')->getImgService();
        $data['img'] = empty($data['img']['img_server']) ? array('img_server' => '/') : $data['img'];
        $data['page'] = '';
        if(!isset($pageParam['ads'])){
            $pageParam['ads'] = '';
        }
        //如果从计划下跳转，添加修改该计划下所有广告链接的功能
        $pageParam['plan_ads'] = $pid;

        $this->assign('ads',$pageParam);
        $this->assign('ads_list',$res);
        $this->assign('data',$data);
        return $this->fetch('ads-list');
    }

    /**
     * 批量修改计划所属的广告链接
     */
    public function updateAdsurl()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $param = $request->param('');
        if($request->isPost()){
            $param['url'] = str_replace('amp;','',$param['url']);
            $res = Loader::model('Ads')->updateAdsUrl($param);
            //写操作日志
            $this->logWrite('0062',$param);
            if($res > 0){
                $this->_success('','修改成功');
            }else{
                $this->_error('修改失败');
            }
        }else{
            //获取该计划的名称
            $data = Loader::model('Ads')->getPlanName($param['pid']);
            $this->assign('data',$data);
            $this->assign('param',$param);
            return $this->fetch('adsurl-update');
        }

    }

    /**
     * 新建广告展示
     */
    public function add()
    {
        Hook::listen('auth',$this->_uid); //权限
	    // 判断是否由计划列表跳转
        $ads_name = array();
        $ads_name['pid'] = isset($_GET['pid']) ? $_GET['pid'] : '';
        $ads_name['plan_name'] = isset($_GET['plan_name']) ? $_GET['plan_name'] : '';
        $ads_name['uid'] = isset($_GET['uid']) ? $_GET['uid'] : '';
        if(!empty($ads_name['pid']) && !empty($ads_name['plan_name']) && !empty($ads_name['uid'])){
            $this->assign('ads_name',$ads_name);
        }else{
            $this->assign('ads_name',"0");
        }
        // 获取计划
        $res = Loader::model('Plan')->getAll();

        $adsRes = Loader::model('Adtype')->adsTypeLst();
        $arr = array();
        foreach ($adsRes as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        //广告类型
        $this->assign('adtype_list',$arr);

        //处理
        $newRes = $this->_doPlanType($res);
        $this->assign('ptype_list',$newRes);

        return $this->fetch('ads-add');
    }

    /**
     * 新建广告
     */
    public function doAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //提交
        $params = $request->post();
        if(stristr($params['url'],'http://') == false){
            if(stristr($params['url'],'https://') == false){
                $text_http = 'http://';
            }else{
                $text_http = '';
            }
        }else{
        $text_http = '';
        }
        $params['url'] = $text_http.$params['url'];
        $params['url'] =  str_replace("&amp;","&",$params['url']);
        if(!empty($params['adtpl_id'])){
            $arr = explode(',', $params['adtpl_id']);
            $params['adtpl_id'] = $arr[0];
            $params['tc_id'] = $arr[1];
            $specs = explode('*', $arr[2]);
            $params['width'] = $specs[0];
            $params['height'] = $specs[1];
        }
        //为空默认
        $params['imageurl'] = empty($params['files']) ? '' : $params['files'];
        $params['file'] = empty($params['file']) ? 0 : $params['file'];
        if($params['file'] == '1'){
            //上传文件
            $file = Request::instance()->file('files');
            $params['imageurl'] = $this->_upfile($file);
        }
        //验证数据
        $validate = Loader::validate('Ads');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $params['text'] = empty($params['text'])?'':$params['text'];
        $params['textcheck'] = serialize($params['text']);
        $res = Loader::model('Ads')->addOne($params);
        //写操作日志
        $this->logWrite('0024',$params['ad_name']);
        // $this->_addUpdateAdzone($params);
        if($res>0){  //保存成功
            $this->redirect('list',['cmd_flag' => 'add']);
        }else{
            $this->_error();
        }
    }

    /**
     * 新建广告展示   多广告
     */
    public function moreAdd()
    {
        // 判断是否由计划列表跳转
        $ads_name = array();
        $ads_name['pid'] = isset($_GET['pid']) ? $_GET['pid'] : '';
        $ads_name['plan_name'] = isset($_GET['plan_name']) ? $_GET['plan_name'] : '';
        $ads_name['uid'] = isset($_GET['uid']) ? $_GET['uid'] : '';
        if(!empty($ads_name['pid']) && !empty($ads_name['plan_name']) && !empty($ads_name['uid'])){
            $this->assign('ads_name',$ads_name);
        }else{
            $this->assign('ads_name',"0");
        }
        // 获取计划
        $res = Loader::model('Ads')->getAll();

        
        $adsRes = Loader::model('Adtype')->adsTypeLst();
        $arr = array();
        foreach ($adsRes as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        //广告类型
        $this->assign('adtype_list',$arr);

        //处理
        $newRes = $this->_doPlanMoreType($res);
        $this->assign('ptype_list',$newRes);

        return $this->fetch('ads-moreadd');
    }

    /**
     * 新建广告  多广告添加
     */
    public function moredoAdd()
    {
        $request = Request::instance();
        //提交
        $params = $request->post();

        //判断是否是多个图文广告
        if(!empty($params['url'])){
            foreach($params['url'] as $key => $value){
                if(stristr($value,'http://') == false){
                    if(stristr($value,'https://') == false){
                        $text_http = 'http://';
                    }else{
                        $text_http = '';
                    }
                }else{
                    $text_http = '';
                }
                //替换完在赋给原值
                $params['url'][$key] = $text_http.$value;
                $params['url'][$key] =  str_replace("&amp;","&",$value);
            }
        }

        if(!empty($params['adtpl_id'])){
            $arr = explode(',', $params['adtpl_id']);
            $params['adtpl_id'] = $arr[0];
            $params['tc_id'] = $arr[1];
            $specs = explode('*', $arr[2]);
            $params['width'] = $specs[0];
            $params['height'] = $specs[1];
        }

        //为空默认
        $params['imageurl'] = empty($params['files']) ? '' : $params['files'];

        //判断是否是多个图文广告
        $params['file'] = empty($params['file'])? '' : $params['file'];
        if($params['file'] == '1'){
            //上传文件
            $file = Request::instance()->file('files');
            //多广告上传地址
            $params['imageurl'] = $this->_moreupfile($file);

        }

        //验证数据
        $validate = Loader::validate('Ads');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }

        $num = 0;
        $new_array = array();
        foreach($params['url'] as $key => $value){

            //判断是否是多个文字广告
            if(empty($params['file'])){
                $new_array[$key]['text_chain'] = empty($params['text_chain'][$key])?0:$params['text_chain'][$key];
            }else{
                $new_array[$key]['adinfo'] = empty($params['adinfo'][$key])?0:$params['adinfo'][$key];
            }
            $new_array[$key]['pid'] = $params['ads_pid'];
            $new_array[$key]['uid'] = $params['uid'];
            //广告name 一样，用key 区分
            $new_array[$key]['adname'] = $params['ad_name'].'_'.$num;
            $new_array[$key]['tpl_id'] = $params['adtpl_id'];
            $new_array[$key]['tc_id'] = $params['tc_id'];
            $new_array[$key]['files'] = empty($params['file'])?'':$params['file'];
            $new_array[$key]['imageurl'] = empty($params['imageurl'][$key])?0:$params['imageurl'][$key];
            $new_array[$key]['url'] = empty($params['url'][$key])?0:$params['url'][$key];
            $new_array[$key]['priority'] = empty($params['priority'])?0:$params['priority'];

            $new_array[$key]['width'] = empty($params['width'])?0:$params['width'];
            $new_array[$key]['height'] = empty($params['height'])?0:$params['height'];
            $new_array[$key]['web_deduction'] = empty($params['web_deduction'])?0:$params['web_deduction'];
            $new_array[$key]['adv_deduction'] = empty($params['adv_deduction'])?0:$params['adv_deduction'];
            $new_array[$key]['status'] = 1;
            $new_array[$key]['ctime'] = time();
            $num = $key+1;

            //写操作日志
            $this->logWrite('0024',$params['ad_name'].'_'.$num);
        }

        $res = Loader::model('Ads')->addOneMore($new_array);
//        $this->_addUpdateAdzone($params);  往show_adid更新，不需要
        if($res>0){  //保存成功
            $this->redirect('list',['cmd_flag' => 'add']);
        }else{
            $this->_error();
        }
    }

    /**
     * 新建文字广告
     */
    public function doTextAdd()
    {
        $request = Request::instance();
        //提交
        $params = $request->post();

        if(stristr($params['url'],'http://') == false){
            if(stristr($params['url'],'https://') == false){
                $text_http = 'http://';
            }else{
                $text_http = '';
            }
        }else{
            $text_http = '';
        }
        $params['url'] = $text_http.$params['url'];
        $params['url'] =  str_replace("&amp;","&",$params['url']);
        if(!empty($params['adtpl_id'])){
            $arr = explode(',', $params['adtpl_id']);
            $params['adtpl_id'] = $arr[0];
            $params['tc_id'] = $arr[1];
            $specs = explode('*', $arr[2]);
            $params['width'] = $specs[0];
            $params['height'] = $specs[1];
        }
        $params['imageurl'] = empty($params['files']) ? '0' : $params['files'];

        //广告显示的名称
        $params['text_chain'] = empty($params['text_chain']) ? '0' : $params['text_chain'];
        //验证数据
        $validate = Loader::validate('Ads');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $res = Loader::model('Ads')->addtextOne($params);
        //写操作日志
        $this->logWrite('0024',$params['ad_name']);
        $this->_addUpdateAdzone($params);
        if($res>0){  //保存成功
            $this->redirect('list',['cmd_flag' => 'txtadd']);
        }else{
            $this->_error();
        }
    }

    /**
     * 批量上传图片
     */
    public function batchUpImg()
    {
        Hook::listen('auth',$this->_uid); //权限
        // 判断是否由计划列表跳转
        $ads_name = array();
        $ads_name['pid'] = isset($_GET['pid']) ? $_GET['pid'] : '';
        $ads_name['plan_name'] = isset($_GET['plan_name']) ? $_GET['plan_name'] : '';
        $ads_name['uid'] = isset($_GET['uid']) ? $_GET['uid'] : '';
        if(!empty($ads_name['pid']) && !empty($ads_name['plan_name']) && !empty($ads_name['uid'])){
            $this->assign('ads_name',$ads_name);
        }else{
            $this->assign('ads_name',"0");
        }
        // 获取计划
        $res = Loader::model('Plan')->getAll();

        $adsRes = Loader::model('Adtype')->adsTypeLst();
        $arr = array();
        foreach ($adsRes as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        //广告类型
        $this->assign('adtype_list',$arr);

        //处理
        $newRes = $this->_doPlanType($res);
        $this->assign('ptype_list',$newRes);
        
        return $this->fetch('ads-addimgs');
    }

    /**
     * 新建文字广告
     */
    public function txtadd()
    {

        // 获取计划
        $res = Loader::model('Plan')->gettextAll();

        // 判断是否由计划列表跳转
        $ads_name = array();
        $ads_name['pid'] = isset($_GET['pid']) ? $_GET['pid'] : '';
        $ads_name['plan_name'] = isset($_GET['plan_name']) ? $_GET['plan_name'] : '';
        $ads_name['uid'] = isset($_GET['uid']) ? $_GET['uid'] : '';
        if(!empty($ads_name['pid']) && !empty($ads_name['plan_name']) && !empty($ads_name['uid'])){
            $this->assign('ads_name',$ads_name);
        }else{
            $this->assign('ads_name',"0");
        }

        $adsRes = Loader::model('Adtype')->adsTypeLst();
        $arr = array();
        foreach ($adsRes as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        //广告类型
        $this->assign('adtype_list',$arr);

        //处理
        $newRes = $this->_doPlanTypeText($res);
        $this->assign('ptype_list',$newRes);

        return $this->fetch('ads-txtadd');
    }

    /**
     * 批量新建广告
     */
    public function doAddImg()
    {

        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //提交
        $params = $request->post();
        if(!empty($params['adtpl_id'])){
            $arr = explode(',', $params['adtpl_id']);
            $params['adtpl_id'] = $arr[0];
            $params['tc_id'] = $arr[1];
            $specs = explode('*', $arr[2]);
            $params['width'] = $specs[0];
            $params['height'] = $specs[1];
        }
        //循环插入广告
        foreach($params['imageurl'] as $key=>$value){
            if((!empty($value)) && (!empty($value))){
                $params['imageurl'] = $value;
                $params['name'] = $params['ad_name'].($key+1);
                $params['adsUrl'] = $params['url'][$key];
                $params['adsUrl'] =  str_replace("&amp;","&",$params['adsUrl']);
                $params['files'] = $params['file'][$key];
                //写操作日志
                $this->logWrite('0024',$params['name']);
                $res = Loader::model('Ads')->add($params);
                $this->_addUpdateAdzone($params);
                if($res<0){  //保存失败
                    $this->_error();
                }
            }
        }
        $this->redirect('list',['cmd_flag' => 'add']);
    }

    /**
     * 批量上传图片
     */
    public function upImg()
    {
        $file = Request::instance()->file();
        $params = $this->_upfile($file['file']);
        $this->_success($params);
    }

    /**
     * 处理计划类型数组
     * param  数组
     */
    private function _doPlanType($arrs)
    {
        $res = array();
        foreach ($arrs as $key => $value) {
            
            if(strpos($arrs[$key]['plan_name'],'文字') === false){
                $res[$value['plan_type']][$key]['plan_name']=$value['plan_name'];
                $res[$value['plan_type']][$key]['pid']=$value['pid'];
                $res[$value['plan_type']][$key]['uid']=$value['uid'];
            }

        }
        return $res;
    }

    /**
     * 处理计划类型数组   多排计划 多个广告显示的计划
     * param  数组
     */
    private function _doPlanMoreType($arrs)
    {
        $res = array();
        foreach ($arrs as $key => $value) {

            $res[$value['plan_type']][$key]['plan_name']=$value['plan_name'];
            $res[$value['plan_type']][$key]['pid']=$value['pid'];
            $res[$value['plan_type']][$key]['uid']=$value['uid'];

        }
        return $res;
    }

    /**
     * 处理计划类型数组
     * param  数组
     */
    private function _doPlanTypeText($arrs)
    {
        $res = array();
        foreach ($arrs as $key => $value) {

            if(strpos($arrs[$key]['plan_name'],'文字') !== false || strpos($arrs[$key]['price_name'],'文字') !== false){
                $res[$value['plan_type']][$value['plan_name']]['plan_name']=$value['plan_name'];
                $res[$value['plan_type']][$value['plan_name']]['pid']=$value['pid'];
                $res[$value['plan_type']][$value['plan_name']]['uid']=$value['uid'];
            }

        }
        return $res;
    }

    /**
     * 激活/锁定
     */
    public function activate()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $status = $request->post('status');
        $aid = $request->post('aid');
        $res = Loader::model('Ads')->updateStatus($aid,$status);
        if($res>0){
            //判断操作日志的写入内容
            if($status == 1){
                //写操作日志
                $this->logWrite('0025',$aid);
            }else{
                //写操作日志
                $this->logWrite('0026',$aid);
            }
            $this->_updateAdzone($aid,$status);
            $this->_success();

        }else{
            $this->_error('修改失败');
        }
    }

    /**
     * 删除 ajax
     */
    public function del()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pid = $request->post('aid');
        $UserApi = new DelApi();
        $UserApi->del($pid,'ad');
        $res = Loader::model('Ads')->delOne($pid);
        if($res>0){
            //写操作日志
            $this->logWrite('0027',$pid);
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            $data = array(
                'adname' => $params['ad_name'],
                'priority' => $params['priority'],
                'adinfo' => empty($params['adinfo'])?'':$params['adinfo'],
                'url' => $params['url'],
                'files' => $params['files'],
                'click_url' => empty($params['click_url'])?'':$params['click_url'],
            );
            $data['url'] =  str_replace("&amp;","&",$data['url']);
            if(!empty($params['web_deduction'])){
                $data['web_deduction'] = $params['web_deduction'];
                $data['adv_deduction'] = $params['adv_deduction'];
            }
            if($params['files'] == '1'){
                //上传文件
                $file = Request::instance()->file('imageurl');
                if(!empty($file)){
                    $data['imageurl'] = $this->_upfile($file);
                }
            }else{
                $data['imageurl'] = $params['imageurl'];
            }
            //广告显示名称
            $data['text_chain'] = empty($params['text_chain']) ? '' : $params['text_chain'];
            //mip 模版广告   3+5字广告 
            if($params['adtpl_id'] == 19){
                $data['textcheck'] = serialize($params['textcheck']);
            } else{
                $data['textcheck'] = serialize($params['text']);
            }
            $res = Loader::model('Ads')->editOne($data,$params['hid_aid']);
            if($res>=0){
                //写操作日志
                $this->logWrite('0028',$params['hid_aid']);
                $pms = array(
                    'aid' => $params['hid_aid'],
                    'cmd_flag' => 1,
                );
                $this->redirect('Ads/edit',$pms);
            }else{
                $this->error('error');
            }
        }else{
            $aid = $request->param('aid');
            $res = Loader::model('Ads')->getOne($aid);
            $img = Loader::model('Ads')->getImgService();
            $img = empty($img['img_server']) ? array('img_server' => '/') : $img;
            if(!empty($res)){
                $res['adtype'] = Loader::model('Adtype')->adsTypeOne($res['tpl_id']);
                if(empty($res['adtype'])){
                    $this->redirect('Ads/add');
                }
                //mip模版广告  文字
                $res['textcheck'] = unserialize($res['textcheck']);
                if(empty($res['textcheck'])){
                    $res['textcheck'] = array();
                }
                $res['textcheck']['0'] = !isset($res['textcheck']['0'])?'':$res['textcheck']['0'];
                $res['textcheck']['1'] = !isset($res['textcheck']['1'])?'':$res['textcheck']['1'];
                $res['textcheck']['2'] = !isset($res['textcheck']['2'])?'':$res['textcheck']['2'];
                $res['textcheck']['3'] = !isset($res['textcheck']['3'])?'':$res['textcheck']['3'];
                //获取用户名
                $name = $request->session('uname');
                $this->assign('name',$name);
                $this->assign('one',$res);
                $this->assign('img',$img);
                return $this->fetch('ads-edit');
            }else{
                $this->redirect('Ads/add');
            }
        }
    }

    /**
     * 在广告管理页面跳转到会员管理页面
     */
    public function adsToUser()
    {
        $uid = Request::instance();
        $pageParam = $uid->param();
        $uid = $pageParam['uid'];
        $res = Loader::model('Users')->getAdvOne($uid);
        if(!empty($res)) {
            
            //获取广告商今日消耗(今日支出) 
            $params['adv_id'] = $res[0]['uid'];
            $params['day'] = date("Y-m-d");
            $resTotalNow = Loader::model('Plan')->advMoney($params);
            $res[0]['Today'] = empty($resTotalNow[0]['sumadvpay']) ? 0 : round($resTotalNow[0]['sumadvpay'],2);
            //获取广告商昨日消耗(昨日支出)
            $params['day'] = date("Y-m-d",strtotime("-1 day"));
            $resTotalYes = Loader::model('Plan')->advMoney($params);
            $res[0]['Yesterday'] = empty($resTotalYes[0]['sumadvpay']) ? 0 : round($resTotalYes[0]['sumadvpay'],2);

        }
        $this->assign('params',$pageParam);
        $this->assign('advertiser_list',$res);
        $this->assign('page','');

        return $this->fetch('users/advertiser-list');
    }

    /**
     * 更新广告位的数据
     */
    private function _addUpdateAdzone($params)
    {
        $params['ad_name'] = empty($params['name']) ? $params['ad_name'] : $params['name'];
        $ad_id = Loader::model('Ads')->getAdid($params);
        $res = Loader::model('Ads')->getAdzone($params);
        foreach($res as $key=>$value){
            $show_adid = explode(',',$value['show_adid']);
            $show_adid = empty($show_adid['0']) ? array() : $show_adid;
            $ads = array('ad_id' => $ad_id['0']['ad_id']);
            $show_adid = array_merge($show_adid,$ads);
            $show_adid = array_unique($show_adid);
            $show_adid = implode(',',$show_adid);
            Loader::model('Ads')->updateAdzone($show_adid,$value);
        }
    }

    /**
     * 更新广告位的数据
     */
    private function _updateAdzone($aid,$status)
    {
        if($status == 1){
            $ad_id = Loader::model('Ads')->getAds($aid);
            $res = Loader::model('Ads')->getAdzone($ad_id['0']);
            foreach($res as $key=>$value){
                $show_adid = explode(',',$value['show_adid']);
                $show_adid = empty($show_adid['0']) ? array() : $show_adid;
                $ads = array('ad_id' => $ad_id['0']['ad_id']);
                $show_adid = array_merge($show_adid,$ads);
                $show_adid = array_unique($show_adid);
                $show_adid = implode(',',$show_adid);
                Loader::model('Ads')->updateAdzone($show_adid,$value);
            }
        }
    }

    /**
     * 上传文件   多广告上传
     */
    private function _moreupfile($file)
    {
        $str = array();
        if(isset($file)){

            //广告图片循环上传
            foreach($file as $key => $value){
                //上传
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $value->move(Config::get('file_upload'));
                //多个广告上传的地址
                $path = $info->getPathname();

                $stropos = strpos($path,'\uploads');
                if(!$info){
                    // 上传失败获取错误信息
                    $this->_error($file->getError());
                }

                $str[] = substr($path, $stropos);
            }

        }
        return $str;
    }

    /**
     * 上传文件
     */
    private function _upfile($file)
    {
        $str = '';
        if(isset($file)){
            //上传
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(Config::get('file_upload'));
            $path = $info->getPathname();
            $stropos = strpos($path,'\uploads');
            if(!$info){
                // 上传失败获取错误信息
                $this->_error($file->getError());
            }
            $str = substr($path, $stropos);
        }
        return $str;
    }

    /**
     * select改变广告类型
     */
    public function changeAdtpl()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $tpl_id = $request->post('tpl_id');
        $tpl_id = explode(',',$tpl_id );
        $tplRes = Loader::model('Ads')->getAdHtml($tpl_id[0]);
        if(!empty($tplRes)){
            $tplRes['htmlcontrol'] = unserialize($tplRes['htmlcontrol']);
            $tplRes['specs'] = unserialize($tplRes['specs']);
            $this->_success($tplRes);
        }else{
            $this->_error();
        }
    }

    /**
     * select改变计划
     */
    public function changePlan()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $ptype = $request->post('ptype');
        $planRes = Loader::model('Adtype')->adsTypeLst();
        $new = array();
        $i = 0;
        foreach ($planRes as $key => $value) {
            if(false !== stripos($value['stats_type'], $ptype)){
                $new[$i]['tpl_id'] = $value['tpl_id'];
                $new[$i]['tplname'] = $value['tplname'];
                $new[$i]['type_name'] = $value['type_name'];
                $i++;
            }
        }
        $arr = array();
        foreach ($new as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        // $new = json_encode($new);
        if(!empty($arr)){
            $this->_success($arr);
        }else{
            $this->_error();
        }
    }

    /**
     * select改变计划
     */
    public function changePrice()
    {
        $request = Request::instance();
        $pid = $request->param('pid');
        $res = Loader::model('Ads')->getPrice($pid);

        foreach($res as $key => $value){
            if(strstr($value['price_name'],'文字') == true){
                //删除文字广告类型的尺寸
                unset($res[$key]);
            }
        }

        if(!empty($res)){
            $this->_success($res);
        }else{
            $this->_error();
        }
    }

    /**
     * select改变计划   多广告展示
     */
    public function changePriceMore()
    {
        $request = Request::instance();
        $pid = $request->param('pid');
        $res = Loader::model('Ads')->getPriceMore($pid);
//        foreach($res as $key => $value){
//            if(strstr($value['price_name'],'文字') == true){
//                //删除文字广告类型的尺寸
//                unset($res[$key]);
//            }
//        }

        if(!empty($res)){
            $this->_success($res);
        }else{
            $this->_error();
        }
    }

    /**
     * select改变计划   新建文字广告
     */
    public function changePriceText()
    {
        $request = Request::instance();
        $pid = $request->param('pid');
        $res = Loader::model('Ads')->getPriceText($pid);

        foreach($res as $key => $value){
            if(strstr($value['price_name'],'文字') == false){
                //删除不是文字广告类型的尺寸
                unset($res[$key]);
            }
        }

        if(!empty($res)){
            $this->_success($res);
        }else{
            $this->_error();
        }
    }

    /**
     * 批量删除操作
     */
    public function batchDel()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid,'ads-del'); //权限
        $params = $request->post();
        if(!isset($params['id'])){
            $this->redirect('Ads/list');
        }
        $UserApi = new DelApi();
        $UserApi->del($params['id'],'ads');
        $ids = implode(',', $params['id']);
        $res = Loader::model('Ads')->delLst($ids);
        if($res>0){
            //写操作日志
            $this->logWrite('0029',$ids);
            $this->redirect('Ads/list');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 广告筛选器页面
     */
    public function adsSelTool()
    {
        $request = Request::instance();
//        Hook::listen('auth',$this->_uid); //权限
        $ads_sel_click = Loader::model('Setting')->get_ads_sel_click();
        $this->assign('ads_sel_click',$ads_sel_click);
        return $this->fetch('ads-selads');
    }

    /**
     * 广告筛选器执行
     */
    public function saveAdsSel()
    {
        $request = Request::instance();
//        Hook::listen('auth',$this->_uid); //权限
        $click = $request->post('click');
        $res = Loader::model('Setting')->saveAsc($click);
        if($res>=0){
            $this->redirect('Ads/adsSelTool');
        }else{
            $this->_error();
        }
    }

    /**
     * 启用广告筛选器
     */
    public function openAsc()
    {
        $request = Request::instance();
//        Hook::listen('auth',$this->_uid); //权限
        // 筛选器点击基数
        $ads_sel_click = Loader::model('Setting')->get_ads_sel_click();
        //查询所有的广告、把次数超过筛选器基数的权重+1
        $allAds = Loader::model('Ads')->getAllAds($ads_sel_click);

        $res = loader::model('Ads')->saveAllAds($allAds);
        if($res>0){
            $this->_success();
        }else{
            $this->_error($ads_sel_click);
        }
    }

    /**
     * 锁定广告列表
     */
    public function lock()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
         //广告分类列表
        $pageParam = $request->param('');
        $total = Loader::model('Ads')->adsLock($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['page'] = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Ads')->adLstOne($Page->firstRow,$Page->listRows,$pageParam);
        $data['img'] = Loader::model('Ads')->getImgService();
        $data['img'] = empty($data['img']['img_server']) ? array('img_server' => '/') : $data['img'];
        if(empty($pageParam)){
            $pageParam = array('ads' =>'' , );
        }
        $this->assign('ads',$pageParam);
        $this->assign('ads_list',$res);
        $this->assign('data',$data);
        return $this->fetch('ads-lock');
    }
   
    /**
     * 活动的广告列表
     */
    public function activity()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
         //广告分类列表
        $pageParam = $request->param('');
        $total = Loader::model('Ads')->adsAtv($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['page'] = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Ads')->adLstTwo($Page->firstRow,$Page->listRows,$pageParam);
        $data['img'] = Loader::model('Ads')->getImgService();
        $data['img'] = empty($data['img']['img_server']) ? array('img_server' => '/') : $data['img'];
        if(!isset($pageParam['ads'])){
            $pageParam['ads'] = '';
        }
        $this->assign('ads',$pageParam);
        $this->assign('ads_list',$res);
        $this->assign('data',$data);
        return $this->fetch('ads-activity');
    }

    /**
     * 待审的广告列表
     */
    public function pending()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
         //广告分类列表
        $pageParam = $request->param('');
        $total = Loader::model('Ads')->adsPend($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['page'] = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Ads')->adLstThree($Page->firstRow,$Page->listRows,$pageParam);
        $data['img'] = Loader::model('Ads')->getImgService();
        $data['img'] = empty($data['img']['img_server']) ? array('img_server' => '/') : $data['img'];
        if(!isset($pageParam['ads'])){
            $pageParam['ads'] = '';
        }
        $this->assign('ads',$pageParam);
        $this->assign('ads_list',$res);
        $this->assign('data',$data);

        return $this->fetch('ads-pending');
    }

    /**
     * 更改广告权重
     */
    public function changePriority()
    {
        // Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['priority'] =  $params['money'];
        $res = Loader::model('Ads')->updatePriority($params['ad_id'],$data);
        //写操作日志
        $this->logWrite('0030',$params['ad_id'],$data['priority']);
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 更改广告列表扣量
     */
    public function deduction()
    {
//        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['ad_id'] =  $params['ad_id'];
        if($params['type'] == 'adv'){
            $data['adv_deduction'] =  $params['money'];
        }else{
            $data['web_deduction'] =  $params['money'];
        }
        $res = Loader::model('Ads')->deduction($data);
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /*
    * 批量修改广告图片链接
    */
    public function saveimgurl()
    {
        //权限
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        $params = $request->post();
        if($params){
            //拼接 修改后的图片地址
            $userInfo = Loader::model('Ads')->updateImgUrl($params);
            $img_name = $params['img_name'];
            if(!empty($userInfo)){
                $i = 0;$user = [];
                foreach ($userInfo as $key => $value) {
                    $c = substr($value['adname'], -2);
                    if($c == $img_name){
                          $user[$i]['save'] = str_replace($params['beforUrl'],$params['url'],$value['imageurl']);
                          $user[$i]['ad_id'] = $value['ad_id'] ;
                        $i++;
                    }
                }
                $res_s = Loader::model('Ads')->saveImg($user);
                if($res_s){
                    //操作日志
                    $this->logWrite('0063',$params['img_name'],$params['url']);
                    $this->_success('','批量修改成功');
                }else{
                    $this->_error("批量修改失败，请重试");
                }
            }else{
                $this->_error('该计划下没有'.$params['img_name'].'图片链接地址');
            }
        }else{
            $param = $request->param('');
            $pid = $param['pid'] ;
            $this->assign("pid",$pid);
        }
        return $this->fetch('ads-saveimgurl');
    }


    /**
     * 批量修改计划下广告名称
     * @author wx@lezun.com
     * @return mixed
     */
    public function upAdName()
    {
        Hook::listen('auth', $this->_uid);
        $request = Request::instance();
        $param = $request->param('');
        if ($request->isPost()) {
            $param = $request->post();
            $checkPid = Loader::model('Ads')->getPlanName($param['pid']);
            if (empty($checkPid)) {
                return $this->_error('pid不存在');
            }
            $res = Loader::model('Ads')->updateAdsName($param);
            if ($res) {
                $this->logWrite('0064', $param);
                return $this->_success('批量修改成功');
            } else {
                return $this->_error('批量修改失败，请重试');
            }
        }
        $this->assign('param', $param);
        return $this->fetch('ads-upAdName');
    }

}