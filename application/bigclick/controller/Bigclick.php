<?php
namespace app\bigclick\controller;
use think\Controller;
use think\Loader;
use think\Request;
use think\Hook;
use think\Cache;


class Bigclick extends Admin
{
    //后退不报错
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }
    /**
     * 大点击分类列表
     */
    public function prolist()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //自动计算产品下几个激活的链接
        $re = Loader::model('bigclick')->saveurlsum();
        $res = Loader::model('bigclick')->saveurlsun();
        foreach ($re as $key => $value) {
            $re[$key]['save'] = 0;
            foreach ($res as $k => $v) {
                if($value['pro_id'] == $v['pro_id']){//匹配 产品下的所有链接
                    if($v['status'] == 1){//激活状态的
                        $re[$key]['save']++;
                    }
                }
            }
        }
        Loader::model('bigclick')->updatepronum($re);
        $pageParam = $request->param('');
        if(empty($pageParam)){
            $pageParam = array('status' => 'all',
                'index' => '',
                'search'=> '' );
        }else{
            $pageParam['search'] = empty($pageParam['search']) ? '' : $pageParam['search'];
            $pageParam['index'] = empty($pageParam['index']) ? '' : $pageParam['index'];
        }
        $total = Loader::model('bigclick')->proListCount($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $list = Loader::model('bigclick')->proList($Page->firstRow,$Page->listRows,$pageParam);
        //处理产品下单链接的数据展示
        $list = $this->_proDataList($list);
        $this->assign('one',$pageParam);
        $this->assign('list',$list);
        $this->assign('page',$show);
        return $this->fetch('pro-list');
    }

    /**
     * 大点击新增产品
     */
    public function proAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if ($request->isPost()){
            $data = array(
                'pro_name' => trim($params['pro_name']),
                'adv_name' => trim($params['adv_name']),
                'plan_name' => $params['plan_name'],
                'billing_model' => $params['billing_model'],
                'launch_model' => $params['launch_model'],
                'post_type' => $params['post_type'],
                'ctime'   =>time(),
            );
            $add = Loader::model('bigclick')->proAdd($data);
            if ($add > 0){
                //操作日志
                 $re = Loader::model('bigclick')->checkadd($data);
                $this->logWrite('1015',$re[0]['pro_id']); //新增日志
                $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
                
                $this->redirect('prolist');
            }else{
                $this->_error();
            }
        }else{
            return $this->fetch('pro-add');
        }
    }

    /**
     * 大点击编辑产品
     */
    public function proEdit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if ($request->isPost()){
            $params = $request->post();
            $data = array(
                'pro_name' => trim($params['pro_name']),
                'adv_name' => trim($params['adv_name']),
                'plan_name' => $params['plan_name'],
                'billing_model' => $params['billing_model'],
                'launch_model' => $params['launch_model'],
                'post_type' => $params['post_type'],
            );
            $add = Loader::model('bigclick')->proEdit($params['pro_id'],$data);
            if ($add >= 0){
                $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
                
                $this->redirect('prolist');
            }else{
                $this->_error();
            }
        }else{
            $id = $request->param('id');
            $data = Loader::model('bigclick')->getPro($id);
            $this->assign('one',$data);
            return $this->fetch('pro-edit');
        }
    }

    /**
     * 激活/锁定
     */
    public function proEditStatus()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $status = $request->post('status');
        $proid = $request->post('pro_id');
        $res = Loader::model('bigclick')->updateStatus($proid,$status);
        //操作日志 0锁定 1激活
        if($status == 1){
            $this->logWrite('1013',$proid); //激活日志
        }else{
            $this->logWrite('1012',$proid); //锁定日志
        }
        if($res>0){
            $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
            
            $this->_success();
        }else{
            $this->_error('修改失败');
        }
    }

    /**
     * 删除产品
     */
    public function proDel()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->post('id');
        $res = Loader::model('bigclick')->proDel($id);
        $this->logWrite('1011',$id);
        if($res>0){
            $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
            
            //写操作日志
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 大点击分类链接
     */
    public function urllist()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(empty($pageParam)){
            $pageParam = array('status' => 'all',
                'index' => '',
                'search'=> '' );
        }else{
            $pageParam['search'] = empty($pageParam['search']) ? '' : $pageParam['search'];
            $pageParam['index'] = empty($pageParam['index']) ? '' : $pageParam['index'];
        }
        if(!empty($pageParam['id'])){
            //获取产品名称
            $data = Loader::model('bigclick')->getPro($pageParam['id']);
            $pageParam['search'] = empty($data['pro_name']) ? '' : $data['pro_name'];
            $pageParam['status'] = 'all';
        }
        $total = Loader::model('bigclick')->urlListCount($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $list = Loader::model('bigclick')->urlList($Page->firstRow,$Page->listRows,$pageParam);
        foreach($list as $key => $value){
            $list[$key]['url'] = htmlspecialchars(base64_decode($value['url']));
            $checkplan = unserialize($value['checkplan']);
            $checkplan['hour'] = empty($checkplan['hour']) ? array() : $checkplan['hour'];
            $checkplan['day'] = empty($checkplan['day']) ? array() : $checkplan['day'];
            if($value['delivery_mode'] == 1){
                $list[$key]['delivery_mode'] = '周期投放';
                $list[$key]['start_day'] = $checkplan['start_day'];
                $list[$key]['revolution'] = $checkplan['revolution'];
                $list[$key]['hour'] = (implode(',',$checkplan['hour']) == '00,01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23') ?
                                      '全天' : implode(',',$checkplan['hour']);
            }else{
                $list[$key]['delivery_mode'] = '按日投放';
                $list[$key]['day'] = (implode(',',$checkplan['day']) == '01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31') ?
                                     '每日' : implode(',',$checkplan['day']);
                $list[$key]['hour'] = (implode(',',$checkplan['hour']) == '00,01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23') ?
                                     '全天' : implode(',',$checkplan['hour']);
            }
        }
        $this->assign('one',$pageParam);
        $this->assign('list',$list);
        $this->assign('page',$show);
        return $this->fetch('url-list');
    }

    /**
     * 大点击新增链接
     */
    public function urlAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        if ($request->isPost()){
            $params['url'] = htmlspecialchars_decode(trim($params['url']));
            $params['url'] = base64_encode($params['url']);
            if($params['delivery_mode'] == 1){
                $checkplan = array(
                    'start_day' => $params['start_day'],
                    'revolution' => $params['revolution'],
                    'hour' => empty($params['hour']) ? '' : $params['hour'],
                );
            }else{
                $checkplan = array(
                    'day' => empty($params['day']) ? '' : $params['day'],
                    'hour' => empty($params['hour']) ? '' : $params['hour'],
                );
            }
            $data = array(
                'pro_id' => $params['pro_id'],
                'url_name'=>$params['url_name'],
                'url' => $params['url'],
                'delivery_mode' => $params['delivery_mode'],
                'percent' => $params['percent'],
                'checkplan' => serialize($checkplan),
                'ctime'   =>time(),
            );
            $add = Loader::model('bigclick')->urlAdd($data);
            if ($add > 0){
                //操作日志
                $re = Loader::model('bigclick')->urlAddCheck($data);//链接ID
                $res = Loader::model('bigclick')->proname($params);//产品名称
                $this->logWrite('1016',$params['pro_id'],$res[0]['pro_name'],$re[0]['url_id']);//1：产品ID 2：产品名称，3:新增链接ID
                $this->_success();
                $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
                
            }else{
                $this->_error();
            }
        }else{
            if(!empty($params['id'])){
                //从产品跳转
                $data[] = Loader::model('bigclick')->getPro($params['id']);
            }else{
                //获取所有的产品链接
                $data = Loader::model('bigclick')->getProName();
            }
            $params['date'] = date("Y-m-d");
            $this->assign('one',$data);
            $this->assign('param',$params);
            return $this->fetch('url-add');
        }
    }

    /**
     * 大点击编辑链接
     */
    public function urledit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        if ($request->isPost()){
            $params['url'] = htmlspecialchars_decode(trim($params['url']));
            $params['url'] = base64_encode($params['url']);
            if($params['delivery_mode'] == 1){
                $checkplan = array(
                    'start_day' => $params['start_day'],
                    'revolution' => $params['revolution'],
                    'hour' => empty($params['hour']) ? '' : $params['hour'],
                );
            }else{
                $checkplan = array(
                    'day' => empty($params['day']) ? '' : $params['day'],
                    'hour' => empty($params['hour']) ? '' : $params['hour'],
                );
            }
            $data = array(
                'pro_id' => $params['pro_id'],
                'url_name'=>$params['url_name'],
                'url' => $params['url'],
                'delivery_mode' => $params['delivery_mode'],
                'percent' => $params['percent'],
                'checkplan' => serialize($checkplan),
                'ctime'   =>time(),
            );

            $add = Loader::model('bigclick')->urledit($params['url_id'],$data);
            if ($add >= 0){
                //操作日志
                $re = Loader::model('bigclick')->urleditdata($params['url_id']);
                $c = date('Y-m-d H:i:s', $re[0]['ctime']).'频率'.$re[0]['percent'] ;
                $this->logWrite('1020',$params['url_id'],$c);
                $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
                $this->_success();
            }else{
                $this->_error();
            }
        }else{
            //获取链接内容
            $data = Loader::model('bigclick')->geturl($params['id']);
            $data['url'] = htmlspecialchars(base64_decode($data['url']));
            if($data['delivery_mode'] == 1){
                $checkplan = unserialize($data['checkplan']);
                $data['start_day'] = $checkplan['start_day'];
                $data['revolution'] = $checkplan['revolution'];
                $data['day'] = '';
                $data['hour'] = json_encode($checkplan['hour']);
            }else{
                $checkplan = unserialize($data['checkplan']);
                $data['start_day'] = '';
                $data['revolution'] = '';
                $data['day'] = json_encode($checkplan['day']);
                $data['hour'] = json_encode($checkplan['hour']);
            }
            $this->assign('one',$data);
            $this->assign('param',$params);
            return $this->fetch('url-edit');
        }
    }

    /**
     * 激活/锁定
     */
    public function urlEditStatus()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $status = $request->post('status');
        $urlid = $request->post('url_id');
        $res = Loader::model('bigclick')->updateurlStatus($urlid,$status);
        //操作日志 0锁定 1激活
        if($status == 1){
            $this->logWrite('1019',$urlid); //激活日志
        }else{
            $this->logWrite('1018',$urlid); //锁定日志
        }
        if($res>0){
            $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
            
            $this->_success();
        }else{
            $this->_error('修改失败');
        }
    }

    /**
     * 删除链接
     */
    public function urlDel()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->post('id');
        $res = Loader::model('bigclick')->urlDel($id);
        if($res>0){
            $this->http_post('http://lezun.sjzwhwy.com/jia/datasql.php','');
            
            //写操作日志
            $this->logWrite('1017',$id);//删除
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 产品列表组装数据
     */
    private function _proDataList($list)
    {
        foreach($list as $key => $value){
            if($value['num'] == 1 && !empty($value['url'])){
                $checkplan = unserialize($value['checkplan']);
                $checkplan['hour'] = empty($checkplan['hour']) ? array() : $checkplan['hour'];
                $checkplan['day'] = empty($checkplan['day']) ? array() : $checkplan['day'];
                if($value['delivery_mode'] == 1){
                    $list[$key]['delivery_mode'] = '周期投放';
                    $list[$key]['start_day'] = $checkplan['start_day'];
                    $list[$key]['revolution'] = $checkplan['revolution'];
                    $list[$key]['hour'] = (implode(',',$checkplan['hour']) == '00,01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23') ?
                        '全天' : implode(',',$checkplan['hour']);
                    $list[$key]['day'] = '/';
                }else{
                    $list[$key]['delivery_mode'] = '按日投放';
                    $list[$key]['day'] = (implode(',',$checkplan['day']) == '01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31') ?
                        '每日' : implode(',',$checkplan['day']);
                    $list[$key]['hour'] = (implode(',',$checkplan['hour']) == '00,01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23') ?
                        '全天' : implode(',',$checkplan['hour']);
                    $list[$key]['start_day'] = '/';
                    $list[$key]['revolution'] = '/';
                }
            }else{
                $list[$key]['delivery_mode'] = '/';
                $list[$key]['url'] = '/';
                $list[$key]['start_day'] = '/';
                $list[$key]['revolution'] = '/';
                $list[$key]['day'] = '/';
                $list[$key]['hour'] = '/';
                $list[$key]['percent'] = '/';
            }
        }

        return $list;
    }

    private function http_post($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($httpCode, $response);
    }
}