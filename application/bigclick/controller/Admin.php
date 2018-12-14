<?php
namespace app\bigclick\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Loader;
use think\Db;

class Admin extends Controller{

    protected $_uid      = '';   //用户id

    protected $_nickname = '';   //用户昵称

    protected $_status   = '';   //身份

    public function _initialize()
    {
        $this->assign('meta_title','后台管理系统');
        $this->_uid = Session::get('user_login_id');
        $this->getCuid();//判断redis是否过期
        $this->getName();//获取登录的用户名
        $this->WebTitle();//获取网站标题
//        $this->getService();//获取js服务器地址
        // $this->_nickname = $_SESSION['nickname'];
        // $this->assign('uid',$this->_uid);
        // define('NICKNAME', $_SESSION['nickname']);
        // $this->assign('username',$this->_nickname);
        // $this->assign('HTTP_HOST',$_SERVER['HTTP_HOST']);
//         $uid = Session::get('user_login_id');
//        $this->_uid = '1';
        if(!$this->_uid){
            $this->redirect('/admin/index/login');
        }

    }
    /*
     *  判断方法名并输出网站标题
     */
    public function WebTitle()
    {   
        $controller=request()->controller();
        $action=request()->action();
        $title= '';
        if($controller == 'Setting'){
            switch ($action){
                case 'basic':
                  $title = '基本设置-';
                  break;
                case 'roles':
                  $title = '权限管理-';
                  break;
                case 'finance':
                  $title = '财务相关-';
                  break;
                case 'service':
                  $title = '服务器设置-';
                  break;
            }
        }elseif($controller == 'Deduction'){
            if($action == 'webdeduction'){
                $title = '站长扣量设置-';
            }
        }elseif($controller == 'Users'){
            switch ($action){
                case 'webmaster':
                  $title = '站长管理-';
                  break;
                case 'advertiser':
                  $title = '广告商管理-';
                  break;
                case 'customer':
                  $title = '客服管理-';
                  break;
                case 'business':
                  $title = '商务管理-';
                  break;
                case 'webmasteredit':
                  $title = '站长编辑-';
                  break;
                case 'usersToSite':
                  $title = '站长下网站-';
                  break;
                case 'usersToAdzone':
                  $title = '站长下广告位-';
                  break;
                case 'usersToplan':
                  $title = '广告商下计划-';
                  break;
                case 'usersToAds':
                  $title = '广告商下广告-';
                  break;
                case 'performance':
                  $title = '我的业绩-';
                  break;
                case 'gamelist':
                  $title = '游戏部管理-';
                  break;   
            }
        }elseif($controller == 'Plan'){
            switch ($action){
                case 'list':
                  $title = '计划列表-';
                  break;
                case 'add':
                  $title = '新建计划-';
                  break;
                case 'price':
                  $title = '新建单价模板-';
                  break;
                case 'pricemodel':
                  $title = '查看单价模板-';
                  break;
                case 'templatePrice':
                  $title = '单价模板管理-';
                  break;
                case 'editModlePrice':
                  $title = '编辑计划单价-';
                  break;
                case 'planToUser':
                  $title = '计划所属广告商-';
                  break;
                case 'planprice':
                  $title = '计划单价管理-';
                  break;
                case 'addprice':
                  $title = '新建单价-';
                  break;
                case 'batchaddprice':
                  $title = '批量新建单价-';
                  break;
                case 'edit':
                  $title = '编辑计划-';
                  break;
                case 'lock':
                  $title = '锁定计划-';
                  break; 
                case 'activity':
                  $title = '活动计划-';
                  break; 
                case 'pending':
                  $title = '待审计划-';
                  break; 
                case 'quota':
                  $title = '超限额计划-';
                  break;  
                case 'one':
                  $title = '广告所属计划-';
                  break; 
                case 'planclass':
                  $title = '计划分类管理-';
                  break; 
                case 'planclassedit':
                  $title = '修改计划分类-';
                  break; 
                case 'gamePromote':
                  $title = '游戏推广-';
                  break; 
                case 'gameLock':
                  $title = '游戏推广-';
                  break;   
                case 'gameActivity':
                    $title = '游戏推广-';
                    break; 
                case 'gamePending':
                    $title = '游戏推广-';
                    break;
                case 'gameQuota':
                    $title = '游戏推广-';
                    break;
                case 'editPrice':
                    $title = '编辑计划单价-';
                    break;
            }
        }elseif($controller == 'Ads'){
            switch ($action){
                case 'list':
                    $title = '广告列表-';
                    break;
                case 'add':
                    $title = '新建广告-';
                    break;
                case 'batchUpImg':
                    $title = '新建批量广告-';
                    break;
                case 'adsToUser':
                    $title = '广告所属广告商-';
                    break;
                case 'edit':
                    $title = '编辑广告-';
                    break;
                case 'lock':
                    $title = '锁定广告-';
                    break;
                case 'activity':
                    $title = '活动广告-';
                    break;
                case 'pending':
                    $title = '待审广告-';
                    break;
            }
        }elseif($controller == 'Adtpl'){
            switch ($action){
                case 'adtype':
                    $title = '广告类型-';
                    break;
                case 'addType':
                    $title = '新建广告类型-';
                    break;
                case 'adtpl':
                    $title = '广告模式-';
                    break;
                case 'addTpl':
                    $title = '新增广告模式-';
                    break;
                case 'adstyle':
                    $title = '广告样式-';
                    break;
                case 'adstyleAdd':
                    $title = '新增广告样式-';
                    break;
                case 'adspecs':
                    $title = '广告尺寸-';
                    break;
                case 'adspecsAdd':
                    $title = '新建广告尺寸-';
                    break;
                case 'editType':
                    $title = '编辑广告类型-';
                    break;
                case 'adtplEdit':
                    $title = '编辑广告模式-';
                    break;
                case 'adstyleEdit':
                    $title = '编辑广告样式-';
                    break;
                case 'adspecsEdit':
                    $title = '修改广告尺寸-';
                    break;
            }
        }elseif($controller == 'Site'){
            switch ($action){
                case 'index':
                    $title = '网站管理-';
                    break;
                case 'siteAdd':
                    $title = '新建网站-';
                    break;
                case 'siteEdit':
                    $title = '编辑网站-';
                    break;
                case 'adzone':
                    $title = '广告位管理-';
                    break;
                case 'adzoneEdit':
                    $title = '修改广告位-';
                    break;
                case 'zonecopylist':
                    $title = '广告位包天详情管理-';
                    break;
                case 'cpdEdit':
                    $title = '编辑包天价钱-';
                    break;
            }
        }elseif($controller == 'Classes'){
            switch ($action){
                case 'list':
                    $title = '网站分类管理-';
                    break;
                case 'relationclassadd':
                    $title = '新建子分类-';
                    break;
                case 'edit':
                    $title = '修改分类-';
                    break;
            }
        }elseif($controller == 'Report'){
            switch ($action){
                case 'planReport':
                    $title = '计划报表-';
                    break;
                case 'webReport':
                    $title = '站长报表-';
                    break;
                case 'adsReport':
                    $title = '广告报表-';
                    break;
                case 'zoneReport':
                    $title = '广告位报表-';
                    break;
                case 'advReport':
                    $title = '广告商报表-';
                    break;
                case 'siteReport':
                    $title = '网站报表-';
                    break;
                case 'classReport':
                    $title = '网站分类报表-';
                    break;
                case 'adzClassReport':
                    $title = '广告位分类报表-';
                    break;
                case 'iplist':
                    $title = 'IP报表-';
                    break;
                case 'gameReport':
                    $title = '游戏推广报表-';
                    break;
            }
        }elseif($controller == 'Monitor'){
            switch ($action){
                case 'pvMonitor':
                    $title = 'pv监控列表-';
                    break;
                case 'clickMonitor':
                    $title = '点击率监控列表-';
                    break;
            }
        }elseif($controller == 'Paylog'){
            switch ($action){
                case 'clearing':
                    $title = '财务结算-';
                    break;
                case 'paypal':
                    $title = '等待支付-';
                    break;
                case 'paid':
                    $title = '已支付-';
                    break;
                case 'paytouser':
                    $title = '站长管理-';
                    break;
                case 'rechaegelog':
                    $title = '充值管理-';
                    break;
                case 'addLog':
                    $title = '手动充值-';
                    break;
            }
        }elseif($controller == 'Auxiliary'){
            switch ($action){
                case 'modleList':
                    $title = '手机机型列表-';
                    break;
                case 'add':
                    $title = '新增手机型号-';
                    break;
                case 'edit':
                    $title = '编辑手机型号-';
                    break;
                case 'list':
                    $title = '机型列表-';
                    break;
            }
        }elseif($controller == 'Operationlog'){
            if($action == 'list'){
                $title = '操作日志列表-';
            }
        }
        $this->assign('title',$title);//获取方法名
    }

    /**
     * 空函数
     */
    public function _empty()
    {
        return $this->fetch('index@public/404');
    }

    /**
     * 获取js服务器地址
     */
    protected function getService()
    {
        $service = Loader::model('Setting')->getJsService();
        $this->assign('service',$service);
    }

    /**
     * 用户登录uid是否存在
     * return boolean
     */
    protected function is_login(){
        $user_login_id = $_SESSION['user_login_id'];
        if(empty($user_login_id)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * ajax成功返回
     * param $data array  数据数组
     * param $info string 成功返回的字符串
     * return json
     */
    protected function _success($datas = array(),$info='success'){
        $data['status']  = 1;
        $data['data'] = $datas;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * ajax成功返回
     * param $info string 错误返回的字符串
     * return json
     */
    protected function _error($info='error'){
        $data['status']  = 0;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * 选择显示状态
     */
    protected function _doFlag($flag = '')
    {
        $status = 0;
        switch ($flag) {
            case 'add':
                $status = 1;
                break;
            case 'save':
                $status = 2;
                break;
            case 'edit':
                $status = 3;
                break;
            case 'delete':
                $status = 4;
                break;
            default:
                $status = 0;
                break;
        }
        $this->assign('cmd_status',$status);
    }

    /**
     * 获取cuid
     */
    protected function getCuid()
    {
//        $Redis = new \org\Redis;
//        $uname = $Redis::get('user_login_uname');
        $uname = Session::get('user_login_uname');
        if(empty($uname)){
            $this->redirect('/admin/index/login');
        }
        Session::set('user_login_uname',$uname);
    }

    /**
     *  获取当前登录用户
     */
    protected function getName()
    {
        //获取当前Session
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);

            $judge_name = Request::instance()->session('uname');
			//待处理提醒的个数
            $reminding_num = Session::get('reminding_num');
            $this->assign('judge_name',$judge_name);
            $this->assign('Administrators',$res);
			$this->assign('reminding_num',$reminding_num);
        }
    }

    /**
     * 日志
     */
    protected function logWrite($ERROR,$text1='',$text2='',$text3='')
    {

        //获取操作时间
        $time = time();
        //获取操作人名称
        $uname = Session::get('user_login_uname');
        $operation = $this->_errorlog($ERROR,$text1,$text2,$text3);
        // Loader::model('Operationlog')->insertLog($time,$uname,$operation);
        $this->insertLog($time,$uname,$operation);
    }

   private function insertLog($time,$uname,$operation)
    {
        $sql = 'INSERT INTO lz_operation_log (name,operation,time) VALUES (?,?,?)';
        $res = Db::query($sql,[$uname,$operation,$time]);
        return $res;
    }

    /**
     * 日志写入
     */
    private function writeFile($file,$str,$mode='a+')
    {
        $fp = @fopen($file,$mode);
        if($fp){
            @fwrite($fp,$str."\n");
            @fclose($fp);
        }
    }

    /**
     * 日志写入
     */
    private function _errorlog($ERROR,$text1,$text2,$text3)
    {
        switch ($ERROR){
            case '0001':$text = '为广告商"'.$text1.'"充值,充值金额"'.$text2.'"';
                break;
            case '0002':$text = '新建站长，名称为"'.$text1.'"';
                break;
            case '0003':$text = '新建广告商，名称为"'.$text1.'"';
                break;
            case '0004':$text = '新建客服，名称为"'.$text1.'"';
                break;
            case '0005':$text = '新建商务，名称为"'.$text1.'"';
                break;
            case '0006':$text = '激活会员id为"'.$text1.'"的会员';
                break;
            case '0007':$text = '锁定会员id为"'.$text1.'"的会员';
                break;
            case '0008':$text = '删除id为"'.$text1.'"的会员';
                break;
            case '0009':$text = '编辑id为"'.$text1.'"的站长';
                break;
            case '0010':$text = '编辑id为"'.$text1.'"的广告商';
                break;
            case '0011':$text = '编辑id为"'.$text1.'"的客服';
                break;
            case '0012':$text = '编辑id为"'.$text1.'"的商务';
                break;
            case '0013':$text = '新建计划名称为"'.$text1.'"的计划';
                break;
            case '0014':$text = '批量删除计划id为"'.$text1.'"的计划';
                break;
            case '0015':$text = '激活计划id为"'.$text1.'"的计划';
                break;
            case '0016':$text = '锁定计划id为"'.$text1.'"的计划';
                break;
            case '0017':$text = '为计划id为"'.$text1.'"的计划新建：类型+尺寸为"'.$text2.'"的单价';
                break;
            case '0018':$text = '删除计划id为"'.$text1.'"下的单价，单价id为'.$text2;
                break;
            case '0019':$text = '编辑计划id为"'.$text1.'"的单价';
                break;
            case '0020':$text = '删除id为"'.$text1.'"的计划';
                break;
            case '0021':$text = '编辑id为"'.$text1.'"的计划';
                break;
            case '0022':$text = '编辑计划id为"'.$text1.'"的权重为"'.$text2.'"';
                break;
            case '0023':$text = '编辑计划id为"'.$text1.'"的限额为"'.$text2.'"';
                break;
            case '0024':$text = '新建广告名称为"'.$text1.'"的广告';
                break;
            case '0025':$text = '激活广告id为"'.$text1.'"的广告';
                break;
            case '0026':$text = '锁定广告id为"'.$text1.'"的广告';
                break;
            case '0027':$text = '删除广告id为"'.$text1.'"的广告';
                break;
            case '0028':$text = '编辑广告id为"'.$text1.'"的广告';
                break;
            case '0029':$text = '批量删除广告id为"'.$text1.'"的广告';
                break;
            case '0030':$text = '将广告id为"'.$text1.'"的权重更改为'.$text2;
                break;
            case '0031':$text = '新建网站，名称为"'.$text1.'"';
                break;
            case '0032':$text = '激活id为"'.$text1.'"的网站';
                break;
            case '0033':$text = '锁定id为"'.$text1.'"的网站';
                break;
            case '0034':$text = '新建网站，名称为"'.$text1.'"';
                break;
            case '0035':$text = '删除id为"'.$text1.'"的网站';
                break;
            case '0036':$text = '删除广告位包天';
                break;
            case '0037':$text = '激活id为"'.$text1.'"的广告位';
                break;
            case '0038':$text = '锁定id为"'.$text1.'"的广告位';
                break;
            case '0039':$text = '删除id为"'.$text1.'"的广告位';
                break;
            case '0040':$text = '编辑广告位，名称为"'.$text1.'"';
                break;
            case '0041':$text = '删除数据报表';
                break;
            case '0042':$text = '删除实时ip';
                break;
            case '0043':$text = '修改时间为'.$text1.'广告id为"'.$text2.'"的广告的下载数为'.$text3;
                break;
            case '0044':$text = '为id为"'.$text1.'"站长手动结算';
                break;
            case '0045':$text = '手动结算等待支付的站长';
                break;
            case '0046':$text = '删除等待支付';
                break;
            case '0047':$text = '删除已支付记录，id为"'.$text1;
                break;
            case '0048':$text = '为广告商"'.$text1.'"充值,充值金额"'.$text2.'"';
                break;
            case '0049':$text = '删除充值记录';
                break;
            case '0050':$text = '新建单价模板,名称为"'.$text1.'"的单价模板';
                break;
            case '0051':$text = '删除单价模板名称为'.$text1;
                break;
            case '0052':$text = '新建网站分类的子分类名称为"'.$text1.'"';
                break;
            case '0053':$text = '编辑网站分类子分类id为"'.$text1.'"名称为"'.$text2.'"';
                break;
            case '0054':$text = '编辑网站分类id为"'.$text1.'"名称为"'.$text2.'"';
                break;
            case '0055':$text = '删除网站分类，id为'.$text1;
                break;
            case '0056':$text = '新建计划分类，名称为'.$text1;
                break;
            case '0057':$text = '编辑计划分类id为"'.$text1.'"名称为"'.$text2.'"';
                break;
            case '0058':$text = '删除计划分类，id为'.$text1;
                break;
            case '0059':$text = '批量新建计划单价，id为'.$text1;
                break;
            case '0060':$text = '批量修改'.$text1['type'].'服务器地址，从'.$text1['find_url'].'改为'.$text1['update_url'].', id为'.$text2;
                break;
            case '0061':$text = '修改样式，id为'.$text1;
                break;
            case '0062':$text = '批量修改计划id为'.$text1['pid'].'的所有广告的广告链接';
                break;

            case '1001':$text = 'android产品ID为'.$text1.'已删除';
                break;
            case '1002':$text = 'android产品ID为'.$text1.'已锁定';
                break;
            case '1003':$text = 'android产品ID为'.$text1.'已激活';
                break;
            case '1005':$text = '新增android产品ID为'.$text1;
                break;
            case '1006':$text = 'android产品ID为'.$text1.'产品名称为'.$text2.'新增链接ID为'.$text3;
                break;
            case '1007':$text = 'android产品链接ID为'.$text1.'已删除';
                break;
            case '1008':$text = 'android产品链接ID为'.$text1.'已锁定';
                break;
            case '1009':$text = 'android产品链接ID为'.$text1.'已激活';
                break;
            case '1010':$text = '编辑android链接ID为'.$text1.'url地址'.$text2.'日期'.$text3;
                break;

            case '1011':$text = 'ios产品ID为'.$text1.'已删除';
                break;
            case '1012':$text = 'ios产品ID为'.$text1.'已锁定';
                break;
            case '1013':$text = 'ios产品ID为'.$text1.'已激活';
                break;
            case '1015':$text = '新增ios产品ID为'.$text1;
                break;
            case '1016':$text = 'ios产品ID为'.$text1.'产品名称为'.$text2.'新增链接ID为'.$text3;
                break;
            case '1017':$text = 'ios产品链接ID为'.$text1.'已删除';
                break;
            case '1018':$text = 'ios产品链接ID为'.$text1.'已锁定';
                break;
            case '1019':$text = 'ios产品链接ID为'.$text1.'已激活';
                break;
            case '1020':$text = '编辑ios链接ID为'.$text1.'url地址'.$text2.'日期'.$text3;
                break;
        }
        return $text;
    }
}