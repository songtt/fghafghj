<?php
/* 广告管理
 * date   2016-6-2
 */
namespace app\webmaster\controller;

use think\Loader;
use think\Request;
use think\Controller;
use think\Session;
use think\Db;

class Web extends Admin
{
    /**
     * 所有网站列表
     */
    public function list()
    {
        $request = Request::instance();
        $params = $request->param();
        $params['status'] = empty($params['status']) ? 'all' : $params['status'];
        $Session_name = Session::get("webmaster_login_id");
        $res = Loader::model('Web')->getList($params,$Session_name);
        $total = count($res);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show($request->action(),$params);
        //将查询出来的数据进行排序处理，并且将过期的站长标红
        foreach($res as $key=>$value){
            $res[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
        }

        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        $params['searchName'] = empty($params['searchName']) ? 'web_url' : $params['searchName'];
        $this->assign('show',$show);
        $this->assign('one',$res);
        $this->assign('params',$params);
        return $this->fetch('web-list');
    }
    
    /**
     * 跟进中   2
     */
    public function followUp()
    {
        $request = Request::instance();
        $params = $request->param();
        $params['status'] = empty($params['status']) ? '2' : $params['status'];
        $Session_name = Session::get("webmaster_login_id");
        $res = Loader::model('Web')->getFollow($params,$Session_name);
        $total = count($res);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show($request->action(),$params);
        //将查询出来的数据进行排序处理，并且将过期的站长标红
        foreach($res as $key=>$value){
            $res[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
            $res[$key]['num'] = Loader::model('Web')->getCommentNum($Session_name,$value);
        }
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        $params['searchName'] = empty($params['searchName']) ? 'web_url' : $params['searchName'];
        $this->assign('show',$show);
        $this->assign('one',$res);
        $this->assign('params',$params);
        return $this->fetch('web-follow');
    }

    /**
     * 已合作   1
     */
    public function cooper()
    {
        $request = Request::instance();
        $params = $request->param();
        $params['status'] = empty($params['status']) ? '1' : $params['status'];
        $Session_name = Session::get("webmaster_login_id");
        $res = Loader::model('Web')->getCooper($params,$Session_name);
        $total = count($res);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show($request->action(),$params);
        //将查询出来的数据进行排序处理，并且将过期的站长标红
        foreach($res as $key=>$value){
            $res[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
            $res[$key]['num'] = Loader::model('Web')->getCommentNum($Session_name,$value);
        }
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        $params['searchName'] = empty($params['searchName']) ? 'web_url' : $params['searchName'];
        $this->assign('show',$show);
        $this->assign('one',$res);
        $this->assign('params',$params);
        return $this->fetch('web-cooper');
    }

    /**
     * 未合作   3
     */
    public function noCooper()
    {
        $request = Request::instance();
        $params = $request->param();
        $params['status'] = empty($params['status']) ? '3' : $params['status'];
        $Session_name = Session::get("webmaster_login_id");
        $res = Loader::model('Web')->getStatusList($params,$Session_name,3);
        $total = count($res);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show($request->action(),$params);
        //将查询出来的数据进行排序处理，并且将过期的站长标红
        foreach($res as $key=>$value){
            $res[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
        }
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        $params['searchName'] = empty($params['searchName']) ? 'web_url' : $params['searchName'];
        $this->assign('show',$show);
        $this->assign('one',$res);
        $this->assign('params',$params);
        return $this->fetch('web-list');
    }

    /**
     * 黑名单  4
     */
    public function blacklist()
    {
        $request = Request::instance();
        $params = $request->param();
        $params['status'] = empty($params['status']) ? '3' : $params['status'];
        $Session_name = Session::get("webmaster_login_id");
        $res = Loader::model('Web')->getStatusList($params,$Session_name,4);
        $total = count($res);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show($request->action(),$params);
        //将查询出来的数据进行排序处理，并且将过期的站长标红
        foreach($res as $key=>$value){
            $res[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
        }
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        $params['searchName'] = empty($params['searchName']) ? 'web_url' : $params['searchName'];
        $this->assign('show',$show);
        $this->assign('one',$res);
        $this->assign('params',$params);
        return $this->fetch('web-list');
    }

    /**
     * 批注
     */
    public function comment()
    {
        $request = Request::instance();
        $params = $request->param();
        $data['name'] = Session::get("webmaster_login_id");
        $data['id'] = $params['id'];
        $res = Loader::model('web')->getComment($params);
        $this->assign('res',$res);
        $this->assign('data',$data);
        return $this->fetch('comment');
    }

    /**
     * 新增批注或新增回复
     */
    public function addCom()
    {
        $request = Request::instance();
        $params = $request->param();
        $params['status'] = 0;
        if ($params['user'] == 'admin') {
            $params['type'] = 1;
        }else{
            $params['type'] = 2;
        }
        $params['ptime'] = time();
        //入库
        $res = Db::name('webcomment')->insert($params);
        if($res>0){
            $this->redirect('comment',array('id'=>$params['web_id']));
        }else{
            $this->error();
        }
    }

    /**
     * 一键全读
     */
    public function read()
    {
        $request = Request::instance();
        $params = $request->param();
        //type  是区分身份的   1.管理员  2.客服 
        //管理员一键读取的是客服的信息    客服读取管理员的信息
        if ($params['user'] == 'admin') {
            $where['type'] = 2;
        }else{
            $where['type'] = 1;
        }
        $where['web_id'] = $params['web_id'];
        $data['status'] = 1;
        //更新批注的状态
        $res = Loader::model('web')->updateRead($where,$data);
        $this->redirect('comment',array('id'=>$params['web_id']));
    }

    /**
     * 新增站长信息
     */
    public function add()
    {
        $request = Request::instance();
        if($request->isPost()){
            $params = $request->post();
            //判断并获取根域名
            $params['web_url'] = $this->_getRootDomain($params['web_url']);
            $num = Loader::model('Web')->decide($params['web_url']);
            $params['status'] = 3;
            if(empty($num)){
                $res = Loader::model('Web')->add($params);
                $this->_success('',"添加成功！");
            }else{
                //新增网站  网站存在情况下弹出相应的提示弹框
                $info = $this->_getInfo($num,$params);
            }
        }else{
            $class = Loader::model('web')->getClass();
            $this->assign('class',$class);
            return $this->fetch('web-add');
        }
    }

    //新增网站  网站存在情况下弹出相应的提示弹框
    private function _getInfo($data,$params){
        $res = 0;
        switch ($data['status']) {
            case '1':
                $info = $data['customer']."已合作,添加失败！";
                break;
            case '2':
                $info = $data['customer']."跟进中,添加失败！";
                break;
            case '3':
                $info = "添加成功！";
                $res = Loader::model('Web')->add($params);
                break;
            case '4':
                $info = "此网站已纳入黑名单,添加失败！";
                break;
            default:
                $info = "添加成功！";
                $info = Loader::model('Web')->add($params);
                break;
        }
        if($res >0 ){
            $this->_success('',$info);
        }else{
            $this->_error($info);
        }
    }

    /**
     * 新增站长信息
     */
    public function butchAdd()
    {
        $request = Request::instance();
        if($request->isPost()){
            $params = $request->post();
            $res = explode('----',$params['web_name']);
            foreach($res as $key=>$value){
                $arr = explode('|',$value);
                $arr = array(
                    'web_url' => empty($arr[0]) ? '' : $arr[0],
                    'web_name' => empty($arr[1]) ? '' : $arr[1],
                    'ip' => empty($arr[2]) ? '' : $arr[2],
                    'qq' => empty($arr[3]) ? '' : $arr[3],
                    'tel' => empty($arr[4]) ? '' : $arr[4],
                    'email' => empty($arr[5]) ? '' : $arr[5],
                    'customer' => empty($arr[6]) ? '' : $arr[6],
                    'status' => empty($arr[7]) ? '' : $arr[7],
                    'type' => 0,
                );
                //判断并获取根域名
                $arr['web_url'] = $this->_getRootDomain($arr['web_url']);
                if(is_array($arr['web_url'])){
                    $this->redirect('echoUrl');
                }
                $num = Loader::model('Web')->decide($arr['web_url']);
                if(empty($num)){
                    Loader::model('Web')->add($arr);
                }else{
                    $this->redirect('echoCopy');
                }
            }
            $this->redirect('list',['status'=>$params['type']]);
        }else{
            return $this->fetch('butch-add');
        }
    }

    /**
     * 编辑网站信息
     */
    public function edit()
    {
        $request = Request::instance();
        $params = $request->param();
        if($request->isPost()){
            $param = $request->param();
            $data= $this->_getEdit($param);
            $res = Loader::model('Web')->edit($params['id'],$data);
            if($res > 0){
                $this->_success($param['url'],"编辑成功！");
            }else{
                $this->_success($param['url'],"编辑失败！");
            }
        }else{
            $url = $params['url'];
            $class = Loader::model('web')->getClass();
            $this->assign('class',$class);
            $res = Loader::model('Web')->getEditList($params);
            $res[0] = empty($res[0]) ? '' : $res[0];
            $this->assign('one',$res[0]);
            $this->assign('url',$url);
            return $this->fetch('web-edit');
        }
    }

    /**
     * 罗总站长列表
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
        return $this->fetch('web-overduelist');
    }

    /**
     * 罗总新增站长信息
     */
    public function lzAdd()
    {
        $request = Request::instance();
        if($request->isPost()){
            $params = $request->post();
            //判断并获取根域名
            $params['web_url'] = $this->_getRootDomain($params['web_url']);
            $params['type'] = 1;
            if(is_array($params['web_url'])){
                $this->redirect('echoUrl');
            }
            $num = Loader::model('Web')->lzDecide($params['web_url']);
            if(empty($num)){
                $res = Loader::model('Web')->add($params);
            }else{
                $this->redirect('echoCopy');
            }
            if ($res >= 0){
                $this->redirect('overduelist');
            }else{
                $this->error();
            }
        }else{
            return $this->fetch('web-lzadd');
        }
    }

    /**
     * 罗总新增站长信息
     */
    public function lzButchAdd()
    {
        $request = Request::instance();
        if($request->isPost()){
            $params = $request->post();
            $res = explode('----',$params['web_name']);
            foreach($res as $key=>$value){
                $arr = explode('|',$value);
                $arr = array(
                    'web_url' => empty($arr[0]) ? '' : $arr[0],
                    'web_name' => empty($arr[1]) ? '' : $arr[1],
                    'ip' => empty($arr[2]) ? '' : $arr[2],
                    'qq' => empty($arr[3]) ? '' : $arr[3],
                    'tel' => empty($arr[4]) ? '' : $arr[4],
                    'email' => empty($arr[5]) ? '' : $arr[5],
                    'customer' => empty($arr[6]) ? '' : $arr[6],
                    'status' => empty($arr[7]) ? '' : $arr[7],
                    'type' => 1,
                );
                //判断并获取根域名
                $arr['web_url'] = $this->_getRootDomain($arr['web_url']);
                if(is_array($arr['web_url'])){
                    $this->redirect('echoUrl');
                }
                $num = Loader::model('Web')->lzDecide($arr['web_url']);
                if(empty($num)){
                    Loader::model('Web')->add($arr);
                }else{
                    $this->redirect('echoCopy');
                }
            }
            $this->redirect('overduelist');
        }else{
            return $this->fetch('butch-lzadd');
        }
    }

    /**
     * 罗总编辑网站信息
     */
    public function lzEdit()
    {
        $request = Request::instance();
        $params = $request->param();
        if($request->isPost()){
            $param = $request->post();
            //判断并获取根域名
            $param['web_url'] = $this->_getRootDomain($param['web_url']);
            $num = Loader::model('Web')->lzDecide($param['web_url']);
            $res = 0;
            $id = array();
            foreach($num as $key=>$value){
                if($value['id'] != $params['id']){
                    $id[] = $value['id'];
                }
            }
            $data= $this->_getEdit($param);
            if(empty($id)){
                $res = Loader::model('Web')->edit($params['id'],$data);
            }else{
                $this->redirect('echoCopy');
            }
            if ($res >= 0){
                $this->redirect('overduelist');
            }else{
                $this->error();
            }
        }else{
            $url = $_SERVER['HTTP_REFERER'];
            $res = Loader::model('Web')->getEditList($params);
            $res[0] = empty($res[0]) ? '' : $res[0];
            $this->assign('one',$res[0]);
            $this->assign('url',$url);
            $this->assign('status',$params);
            return $this->fetch('web-lzedit');
        }
    }

    /**
     * 罗总变红信息
     */
    public function editShows()
    {
        $request = Request::instance();
        $params = $request->param();
        if($params['shows'] == 1){
            $time = $params['start_time'];
        }else{
            $time = time();
        }
        $res = Loader::model('Web')->editShows($params['id'],$params['shows'],$time);
        if ($res >= 0){
            $this->redirect('overduelist');
        }else{
            $this->error();
        }
    }

    /**
     *  批量操作
     */
    public function batchEdit()
    {
        $request = Request::instance();
        $params = $request->param();
        if(!isset($params['id'])){
            $this->redirect('overduelist');
        }
        foreach($params['id'] as $key=>$value){
            $time = Loader::model('Web')->getTime($value);
            if($params['shows'] == 1){
                $time = $time[0]['start_time'];
            }else{
                $time = time();
            }
            $res = Loader::model('Web')->editShows($value,$params['shows'],$time);
        }
        if($res>0){
            $this->redirect('overduelist');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 删除 ajax
     */
    public function del()
    {
        $request = Request::instance();
        $id = $request->post('id');
        $res = Loader::model('Web')->delOne($id);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 删除 ajax
     */
    public function delCom()
    {
        $request = Request::instance();
        $id = $request->post('id');
        $res = Loader::model('Web')->delCom($id);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     *  批量删除
     */
    public function batchDel()
    {
        $request = Request::instance();
        $params = $request->param();
        if(!isset($params['id'])){
            $this->redirect('list',['status' => $params['status']]);
        }
        $ids = implode(',', $params['id']);
        $res = Loader::model('Web')->delLst($ids);
        if($res>0){
            $this->redirect('list',['status' => $params['status']]);
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 站长状态修改
     */
    public function updateStatus()
    {
        $request = Request::instance();
        $param = $request->param();
        //状态，1已合作 2跟进中 3未合作 4黑名单
        //修改状态的判断限制
        $id = $param['id'];
        $status = $param['status'];
        $res = 0;
        if ($param['status'] == 1 || $param['status'] == 2) {
            $webUser = Loader::model('web')->getWeb($param);
        }else{
            $res = Loader::model('Web')->updateStatus($id,$status);
        }
        //若需要修改状态为合作或这跟进中需要完善信息
        if($param['status'] == 2&&(!empty($webUser['ip']))&&(!empty($webUser['pv']))&&(!empty($webUser['area']))&&(!empty($webUser['weights']))&&(!empty($webUser['position']))&&(!empty($webUser['qq']))){
            $res = Loader::model('Web')->updateStatus($id,$status);
        }
        if($param['status'] == 1&&(!empty($webUser['uid']))){
            $res = Loader::model('Web')->updateStatus($id,$status);
        }
        if($res>0){
            $this->_success();
        }else{
            $this->_error('网站信息不完整，修改失败！');
        }
    }

    /**
     * 获取最顶级域名
     * param $url 需要提取根域名的url链接 可选 不提供则自动取当前主机名称
     */
    private function _getRootDomain($params)
    {
        $params = str_replace("https","http",$params);
        $url = '/^(http:\/\/)?([^\/]+)/i';
        preg_match_all($url,$params,$url);
        if(!empty($url[2])){
            if(preg_match('%^[\d\.]$%',$url[2][0])) {return $url;}
            if(preg_match('%[^:\.\/]+(?:(?<ext>\.(?:com|net|org|edu|gov|biz|tv|me|pro|name|cc|co|info|cm|so|top))|(?<ctr>\.(?:cn|us|hk|tw|uk|it|fr|br|in|de))|\k<ext>\k<ctr>)+$%i',$url[2][0],$match))
            {
                return $match[0];
            }
            return $url;
        }else{
            return '';
        }
    }

    /**
     * 组装编辑参数
     */
    private function _getEdit($param)
    {
        $data = array(
          'web_type' =>  $param['web_type'],
          'ip' =>  $param['ip'],
          'pv' =>  $param['pv'],
          'about_ip' =>  $param['about_ip'],
          'area' =>  $param['area'],
          'weights' =>  $param['weights'],
          'position' =>  $param['position'],
          'qq' =>  $param['qq'],
          'tel' => $param['tel'],
          'email' =>  $param['email'],
          'price' =>  $param['price'],
          'customer' =>  $param['customer'],
          'uid' =>  $param['uid'],
          'info' =>  $param['info'],
          'method' => $param['method'],
        );
        return $data;
    }
}