<?php
/* 客户端商务后台  
 * @date   2016-8-3 
 */
namespace app\home\controller;;
use think\Session;
use think\Request;
use think\Loader;
use app\user\common\Encrypt;

class Business extends Client
{
    /**
     * 我的首页
     */
    public function index()
    {
        $res['uid'] = Session::get('businessUid');

        $User = Loader::model('Users','logic');

        //得到top50
        $res[] = $User->getTop($res['uid']); //res['adver'] res['plan'] res['ads']

        //得到个数
        $num = $User->getNum($res['uid']); //num['add'] num['adver'] num['plan'] num['ads']

        //查询该客服下所有的站长
        $adv = Loader::model('Business')->getAdvs($res['uid']);
        //计算所有站长的今日消耗
        $num['money'] = $this->_getMoney($adv,date("Y-m-d"));

        //全局服务器配置地址
        $globalRes =Loader::model('Business')->globalList();
        $res['url'] = $globalRes[0]['jump_server'];

        $this->assign('res',$res);
        $this->assign('num',$num);
        return $this->fetch('bs-index');
    }

    /**
     * 广告商管理
     */
    public function adver()
    {
        $Request = Request::instance();
        //获取初期数据
        $pageParam = $Request->param();
        $date['params'] = $this->_getParams($Request);
        $date['uid'] = Session::get('businessUid');

        //分页功能
        $total = Loader::model('Business')->getAdvListCount($date['uid'],$date['params']);
        $pageParam = $Request->param('');
        $Page = new \org\PageUtil($total,$pageParam);
        $date['show'] = $Page->show($Request->action(),$date['params']);

        //判断是升序还是降序排列
        if($date['params']['sortMethod'] == 'descend'){
            //查询线下会员
            $date['res'] = Loader::model('Business')->getAdvDes($date);
        }else{
            //查询线下会员
            $date['res'] = Loader::model('Business')->getAdvAs($date);
        }
        $date['day'] = date("Y-m-d");
        $date['yesday'] = date("Y-m-d",strtotime("-1 day"));
        //今日消耗
        $now = Loader::model('Business')->advReportNow($date);
        $today = array_column($now,'sumadvpay','uid');
        //拼入数组
        foreach($date['res'] as $key=> $value){
            if(array_key_exists($date['res'][$key]['uid'],$today)){
                $date['res'][$key]['today'] = $today[$date['res'][$key]['uid']];
                $date['res'][$key]['money'] -= $date['res'][$key]['today'];
            }else{ 
                $date['res'][$key]['today'] = '0.00';
            }
        }
        //昨日消耗
        $yesday = Loader::model('Business')->advReportYes($date);
        $Yesterday = array_column($yesday,'sumadvpay','uid');
        //拼入数组
        foreach($date['res'] as $key=> $value){
            if(array_key_exists($date['res'][$key]['uid'],$Yesterday)){
                $date['res'][$key]['Yesterday'] = $Yesterday[$date['res'][$key]['uid']];
            }else{ 
                $date['res'][$key]['Yesterday'] = '0.00';
            }
        }
        $date['res'] = array_slice($date['res'],$Page->firstRow,$Page->listRows);
        $this->assign('one',$date);
        return $this->fetch('bs-adver');
    }

    /**
     * 计划管理
     */
    public function plan()
    {
        $request = Request::instance();
        $params = $request->post();
        $pageParam = $request->param('');
        $data['uid'] = session('businessUid');

        $data = $this->_planData($pageParam,$request,$data);

        $this->assign('data',$data);

        return $this->fetch('bs-plan');
    }

    /**
     * 广告管理
     */
    public function ad()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        $data['uid'] = session('businessUid');

        $data = $this->_adData($pageParam,$request,$data);

        $this->assign('data',$data);
        return $this->fetch('bs-ad');
    }

    /**
     * 账户设置
     */
    public function account()
    {
        $Request = Request::instance();
        if($Request->isPost()){
            $params = $Request->post();
            $uid = $params['uid'];
            //组装 修改的字段的数据
            $data = $this->_getAccount($params);

            //验证数据
            $validate = Loader::validate('Users');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = Loader::model('Business')->accountEdit($uid,$data);
            if($res >=0){
                $this->redirect('account');
            }else{
                $this->error('error');
            }
        }else{
            $uid = Session::get('businessUid');
            $res = Loader::model('Business')->getBasic($uid);

            $this->assign('one',$res[0]);
            return $this->fetch('bs-account');
        }
    }

    /**
     *   修改账号密码
     */
    public function passEdit(){
        $params = Request::instance()->post();
        $uid = $params['uid'];

        if(!in_array('',$params)){
            // 查询原始密码
            $res = Loader::model('Business')->getPwd($uid);
            //用户输入的密码加密
            $Encrypt = new Encrypt();
            $password = $Encrypt->fb_ucenter_encrypt($params['password']);
            //判断新密码和原始密码是否一致
            if($params['new_password'] != $params['password']){
                //判断 用户输入的2次新密码是否一致
                if($params['new_password'] == $params['new_password_1']){
                    //判断 用户输入的原始密码是否正确
                    if($password == $res['password']){
                        $new_password = $Encrypt->fb_ucenter_encrypt($params['new_password']);
                        $data = array(
                            'password'=>$new_password,
                        );
                        $update = Loader::model('Business')->passEdit($uid,$data);
                        if($update >= 0){
                            $this->_success(array(),'修改成功');
                        }else{
                            $this->_error('修改失败');
                        }
                    }else{
                        $this->_error('原密码输入错误');
                    }
                }else{
                    $this->_error('新密码不一致');
                }
            }else{
                $this->_error('新密码和原始密码一致');
            }
        }else{
            $this->_error('密码不能为空');
        }
    }

    /**
     * 广告商审核/锁定
     */
    public function userActive()
    {
        $params = Request::instance()->param();
        $res = Loader::model('Users')->updateStatus($params['uid'],$params['status']);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 广告计划审核/锁定
     */
    public function planActive()
    {
        $params = Request::instance()->param();
        $res = Loader::model('Plan')->updateStatus($params['pid'],$params['status']);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 广告审核/锁定
     */
    public function adsActive()
    {
        $params = Request::instance()->param();
        $res = Loader::model('Ads')->updateStatus($params['ad_id'],$params['status']);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 会员管理跳转到客户端
     */
    public function toAdvertiser()
    {
        $pageParam = Request::instance()->param();
        Session::set('advertiserUid',$pageParam['uid']);
        $this->redirect('/home/Advertiser/homePage');
    }

    /**
     * 新增下属厂商
     */
    public function getNewUsers()
    {
        $serviceid = Request::instance()->param('uid');
        Session::set('serviceid',$serviceid);

        $this->redirect('index/index/register');
    }

    /**
     * 统计商务今日业绩
     */
    private function _getMoney($web,$day)
    {
        $money = 0;
        foreach($web as $key=>$value){
            $res = Loader::model('Business')->getMoney($value['uid'],$day);
            $money = $res['money'] + $money;
        }
        return $money;
    }

    /**
     * 获取初期数据
     */
    private function _getParams($Request)
    {
        $pageParam = $Request->param();
        if($Request->isPost() || (!empty($pageParam['p']))){
            $date = array(
                'num' => empty($pageParam['num']) ? 0:ltrim($pageParam['num']),
                'selectName' => $pageParam['selectName'],
                'sortMethod' => $pageParam['sortMethod'],
                'sortName' => $pageParam['sortName'],
            );
        }else{
            $date = array(
                'num' => '0',
                'selectName' => 'uname',
                'sortMethod' => 'descend',
                'sortName' => 'ctime',
            );
        }
        return $date;
    }

    /**
     * 组装 修改的字段的数据
     */
   private function _getAccount($params)
   {
       $data = array(
           'mobile'  =>$params['mobile'],
           'qq'      =>$params['qq'],
           'email'   =>$params['email'],
           'tel'     =>$params['tel'],
           'idcard'  =>$params['idcard'],

       );
       return $data;
   }

    /**
     * 计划管理 处理
     */
    private function _planData($pageParam,$request,$data)
    {
        if(!empty($pageParam['typename'])){
            $data['typename']= $pageParam['typename'];
            $data['type'] = $pageParam['type'];
        }elseif(!empty($pageParam['type']) && empty($pageParam['typename'])){
            $data['typename']= '';
            $data['type'] = $pageParam['type'];
        }else{
            $data['typename']= '';
            $data['type'] = 'pid';
        }
        $total = Loader::model('Business')->planLstCount($data,$pageParam);
        $Page = new \org\PageUtil(count($total),$pageParam);

        $data['page'] = $Page->show($request->action(),$pageParam);

        $data['res'] = Loader::model('Business')->getLst($data,$Page->firstRow,$Page->listRows);
        return $data;
    }

    /**
     * 广告管理 处理
     */
    private function _adData($pageParam,$request,$data)
    {
        if(!empty($pageParam['typename'])){
            $data['typename']= $pageParam['typename'];
            $data['type'] = $pageParam['type'];
        }elseif(!empty($pageParam['type']) && empty($pageParam['typename'])){
            $data['typename']= '';
            $data['type'] = $pageParam['type'];
        }else{
            $data['typename']= '';
            $data['type'] = 'adid';
        }
        $total = Loader::model('Business')->adLstCount($data,$pageParam);

        $Page = new \org\PageUtil(count($total),$pageParam);
        $data['page'] = $Page->show($request->action(),$pageParam);

        $data['res'] = Loader::model('Business')->adLst($data,$Page->firstRow,$Page->listRows);
        $data['img'] = Loader::model('Advertiser')->getImgService();
        $data['img'] = empty($date['img']['img_server']) ? array('img_server' => '/') : $date['img'];
        return $data;
    }
}