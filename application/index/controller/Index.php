<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Loader;
use org\Verify as Verify;
use app\user\common\Encrypt as Encrypt;
use org\phpmailer\phpmailer as PHPMailer;

class Index extends Controller
{
    /**
     *网站主页
     **/
    public function index()
    {
        return $this->fetch('index');
    }

    /**
     * 网站主
     * */
    public function sitemaster()
    {

        return $this->fetch('sitemaster');
    }

    /**
     * 广告商
     * */
    public function advertisers()
    {

        return $this->fetch('advertisers');
    }

    /**
     * 公告
     * */
    public function notice()
    {

        return $this->fetch('notice');
    }

    /**
     * 公告文章   article
     * */
    public function article()
    {
        $params = Request::instance()->get();
        if($params['type'] == 1){

            return $this->fetch('zen');
        }elseif($params['type'] == 2){
            return $this->fetch('yongjin');
        }elseif($params['type'] == 3){
            return $this->fetch('guanggao');
        }else{
            return $this->fetch('guanyu');
        }

    }

    /**
     * 关于
     * */
    public function about()
    {

        return $this->fetch('about');
    }

    /**
     * 注册新用户
     */
    public function register()
    {
        //获取用户所填的信息
        $params = Request::instance()->param();
        if(Request::instance()->isPost()) {

            //检测验证码输入是否正确
            import("extend.org");
            $code = new verify();

            if(!$code->check($params['verify'],1)){
                $this->_error($info='验证码错误！');
            }

            //判断用户名是否存在
            $one= Loader::model('Index')->nameOne($params['reg_name']);
            if(empty($one)) {
                //验证用户注册时的账号和密码不能为空
                if ($params['reg_name'] == '' && $params['reg_password'] == '') {
                    $this->_error($info = '用户名和密码不能为空！');
                } elseif ($params['reg_password'] != $params['reg_password_2']) {
                    $this->_error($info = '两次输入的密码不一致！');
                } elseif ($params['contact'] == '' && $params['email'] == '' && $params['account_name'] == '' && $params['bank_card'] == '') {
                    $this->_error($info = '用户基本信息填写不正确');
                }
            }else{
                $this->_error($info = '用户名存在');
            }
            //验证数据
            $validate = Loader::validate('Users');
            if(!$validate->check($params)){
                $this->error($validate->getError());
            }

            //用户密码加密
            $pwd = $params['reg_password'];
            $Encrypt = new Encrypt();
            $params['password'] = $Encrypt->fb_ucenter_encrypt($pwd);
            //组装数据
            $date = $this->_getUsers($params);

            //将新建的用户信息插入到数据库中
            $res = Loader::model('Index')->add($date);
            if($res>0){

                $this->_success('/','注册成功！');
            }else{
                //注册失败
                $this->_error('注册失败！');
            }
        } else {

            //判断URL是否是 客户或商务的发展链接
            if(empty($params['type']) || $params['type'] == 1){
                $_POST['type'] = 1;
                //正常访问进入
                if(empty($params['type'])){
                    $params['type'] = 1;
                    $params['uid'] = '';
                }

            }else{
                $_POST['type'] = 2;
            }
            $list = $this->typeList();
            $service = Loader::model('Index')->getJsService();
            $this->assign('service',$service);
            $this->assign('list',$list);
            $this->assign('params',$params);

        }
        return $this->fetch('register');
    }

    /**
     * 注册游戏部员工注册
     */
    public function gameRegister()
    {
        //获取用户所填的信息
        $params = Request::instance()->param();
        if(Request::instance()->isPost()) {

            //检测验证码输入是否正确
            import("extend.org");
            $code = new verify();

            if(!$code->check($params['verify'],1)){
                $this->_error($info='验证码错误！');
            }

            //判断用户名是否存在
            $one= Loader::model('Index')->nameOne($params['reg_name']);
            if(empty($one)) {
                //验证用户注册时的账号和密码不能为空
                if ($params['reg_name'] == '' && $params['reg_password'] == '') {
                    $this->_error($info = '用户名和密码不能为空！');
                } elseif ($params['reg_password'] != $params['reg_password_2']) {
                    $this->_error($info = '两次输入的密码不一致！');
                }
            }else{
                $this->_error($info = '用户名存在');
            }
            //验证数据
            $validate = Loader::validate('Users');
            if(!$validate->check($params)){
                $this->error($validate->getError());
            }

            //用户密码加密
            $pwd = $params['reg_password'];
            $Encrypt = new Encrypt();
            $params['password'] = $Encrypt->fb_ucenter_encrypt($pwd);
            //组装数据
            $date = array(
                'type' => $params['type'],
                'nickname' => $params['nickname'],
                'username' => $params['reg_name'],
                'password' => $params['password'],
                'qq' => $params['qq'],
                'email' => $params['email'],
            );

            //将新建的用户信息插入到数据库中
            $res = Loader::model('Index')->add($date);
            if($res>0){

                $this->_success('注册成功！');
            }else{
                //注册失败
                $this->_error('注册失败！');
            }
        }
        return $this->fetch('game-register');
    }

    /**
     * 登录
     */
    public function login(){
        header("Content-Type: text/html;charset=utf-8");
// 	$this->_error($info='后台正在维护,计费一切正常.');
// exit;
        if(Request::instance()->isPost()){
            $params = Request::instance()->post();
            $uname = $params['username'];
            $pwd = $params['password'];

	        

            //检测验证码输入是否正确
            import("extend.org");
            $code = new verify();

            if(!$code->check($params['verify'],1)){
                $this->_error($info='验证码错误！');
            }
            //老平台的md5 加密
            $pwd = ''.$pwd.'zyiis';
            $password = md5($pwd);
            $one= Loader::model('Index')->userList($uname,$password);
            // 验证数据
            if($one == false){
                //密码加密
                $Encrypt = new Encrypt();
                $password = $Encrypt->fb_ucenter_encrypt($params['password']);
                $one= Loader::model('Index')->userList($uname,$password);
            }
            if(!empty($one)){

                if($one[0]['status'] != 1){
                    $this->_error($info='该用户被锁定或未被审核！');
                }
//                if($one[0]['mail_status'] != 1){
//                    $this->_error($info='邮箱未激活！');
//                }
                //用户身份
                Session::set('type',$one[0]['type']);
                Session::set('user_login_uname',$one[0]['username']);
                $type = session::get('type');
                //判断用户身份，跳转到不同页面
                if($type==1){
                    Session::set('webmasterUid',$one[0]['uid']);
                    Session::set('login_id',$one[0]['uid']);
                    $getIP =$this-> _getIp();
                    //按照星期将站长登录存日志
                    $monday =  date('Ymd', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                    $sunday = date('Ymd', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                    $log_test_file = __DIR__.'./../../../public/test/'.$monday.'-'.$sunday.'.log';
                    $log_test_str = '站长用户名：'.$uname.','.'IP:'.$getIP.','.'登录时间'.date('Y-m-d H:i:s').' 登录成功'."\r\n";
                    $this->writeFile($log_test_file,$log_test_str);
                    $this->_success('/home/webmaster/myCenter',$info='登录成功！');
                }elseif($type==2){
                    Session::set('advertiserUid',$one[0]['uid']);
                    Session::set('login_id',$one[0]['uid']);
                    $this->_success('/home/advertiser/homePage',$info='登录成功！');
                }elseif($type==3){
                    Session::set('customerUid',$one[0]['uid']);
                    Session::set('login_id',$one[0]['uid']);
                    // $getIP =$this-> _getIp();
                    // $log_test_file = 'loginlogs.log';
                    // $log_test_str = '客服：'.$uname.','.'密码:'.$params['password'].','.'IP:'.$getIP.','.'登录时间'.date('Y-m-d H:i:s').'登录成功'."\r\n";
                    // $this->writeFile($log_test_file,$log_test_str);
                    $this->_success('/home/customer/index',$info='客服登录成功！');
                }elseif($type==5){
                    Session::set('gameUid',$one[0]['uid']);
                    Session::set('login_id',$one[0]['uid']);
                    $this->_success('/home/game/index',$info='登录成功！');

                }else{
                    Session::set('businessUid',$one[0]['uid']);
                    Session::set('login_id',$one[0]['uid']);
                    $this->_success('/home/business/index',$info='商务登录成功！');
                }
            }else{
                // $getIP =$this-> _getIp();
                // $log_test_file = 'loginlogs.log';
                // $log_test_str = '用户：'.$uname.','.'密码:'.$params['password'].','.'IP:'.$getIP.','.'登录时间'.date('Y-m-d H:i:s').'登录失败'."\r\n";
                // $this->writeFile($log_test_file,$log_test_str);
                //登录失败
                $this->_error($info='用户名或密码错误！');
            }
        }
        $service = Loader::model('Index')->getJsService();
        $this->assign('service',$service);
        return $this->fetch('login');
    }


    function writeFile($file,$str,$mode='a+')
    {
        $oldmask = @umask(0);
        $fp = @fopen($file,$mode);
        // @flock($fp, 3);
        if(!$fp){

        } else {
            @fwrite($fp,$str);
            @fclose($fp);
            // @umask($oldmask);
            // Return true;
        }
    }


    /**
     * 生成验证码
     */
    public function verify()
    {
        //生成验证码
        import("extend.org");
        $verify = new verify();
        $verify->entry(1);
    }

    /**
     * 邮件发送
     **/
    public function emailSend()
    {
        $toemail = $_POST['email'];//定义收件人的邮箱
        $mail_url = 'http://'.$_SERVER['HTTP_HOST'].'/mailStatus?type='.base64_encode($toemail);//邮箱激活账号地址
        //发送邮箱验证码
        $mail = new PHPMailer();
        $mail->isSMTP();// 使用SMTP服务
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
        $mail->Host = "smtp.exmail.qq.com";// 发送方的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
        $mail->Username = "wk@lezun.com";// 发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱</span><span style="color:#333333;">
        $mail->Password = "Le123456";// 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！</span><span style="color:#333333;">
        $mail->SMTPSecure = "ssl";// 使用ssl协议方式</span><span style="color:#333333;">
        $mail->Port = 465;// 163邮箱的ssl协议方式端口号是465/994
        $mail->setFrom("wk@lezun.com","乐遵");// 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
        $mail->addAddress($toemail,'乐遵');// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
        $mail->addReplyTo("wk@lezun.com","乐遵");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        $mail->Subject = "乐遵";// 邮件标题
        $mail->Body = "欢迎注册乐遵网站用户,请您点击此链接激活账号,链接：".$mail_url;// 邮件正文
        if(!$mail->send()){// 发送邮件
            echo "Message could not be sent.";
            echo "Mailer Error: ".$mail->ErrorInfo;// 输出错误信息
        }else{
            echo 1;
        }
    }

    /**
     *  邮箱地址验证
     */
    public function mailStatus()
    {
        $email = base64_decode($_GET['type']);
        $res = Loader::model('index')->mailStatus($email);
        if($res>=0){
            $this->success('验证成功','/');
        }else{
            $this->success('验证失败,请联系客服','/');
        }
    }

    /**
     *  邮箱是否存在
     */
    public function emailOne()
    {
        $email = $_POST['email'];
        $res = Loader::model('index')->emailOne($email);
        if(!empty($res)){
            echo 0;
        }else{
            echo 1;
        }
    }

    /**
     *  用户名是否存在
     */
    public function userOne()
    {
        $name = $_POST['name'];
        $res = Loader::model('index')->nameOne($name);
        if(!empty($res)){
            echo 0;
        }else{
            echo 1;
        }
    }

    /**
     *  站长和广告商选择
     */
    public function typeList()
    {
        if(empty($_POST['type']) || $_POST['type']==1){
            //客服列表
            $data = array(
                'status'=>1,
                'type'=>3
            );
        }else{
            //商务列表
            $data = array(
                'status'=>1,
                'type'=>4
            );

        }
        $res = Loader::model('index')->typeList($data);
        return $res;
    }


    /**
     * 空函数
     */
    public function _empty()
    {
        return $this->fetch('public/404');
    }

    /**
     * 处理注册新用户的数据
     */
    private function _getUsers($params)
    {
        $ip = $this->_getIp();
        $params['serviceid'] = isset($params['serviceid'])?$params['serviceid']:'';
        $date = array(
            'type' => $params['type'],
            'nickname' => $params['nickname'],
            'username' => $params['reg_name'],
            'password' => $params['password'],
            'contact' => $params['contact'],
            'tel' => $params['tel'],
            'qq' => $params['qq'],
            'email' => $params['email'],
            'bank_name' => isset($params['bank_name'])? $params['bank_name']: '',
            'account_name' => isset($params['account_name'])? $params['account_name']: '',
            'bank_branch' => isset($params['bank_branch'])? $params['bank_branch']: '',
            'bank_card' => isset($params['bank_card'])? $params['bank_card']: '',
            'serviceid' => $params['serviceid'],
            'status' => 2,
            'regip' => $ip,
        );
        if(!empty($params['uid'])){
            $date['serviceid'] = $params['uid'];
        }
        return $date;
    }

    /**
     * error函数
     */
    private function _error($info)
    {
        $data['status']  = 0;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * success函数
     */
    protected function _success($info)
    {
        $data['status']  = 1;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    private function _getIp()
    {
        $realip = '';
        $unknown = 'unknown';
        if (isset($_SERVER)){
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach($arr as $ip){
                    $ip = trim($ip);
                    if ($ip != 'unknown'){
                        $realip = $ip;
                        break;
                    }
                }
            }else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)){
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)){
                $realip = $_SERVER['REMOTE_ADDR'];
            }else{
                $realip = $unknown;
            }
        }else{
            if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            }else if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)){
                $realip = getenv("HTTP_CLIENT_IP");
            }else if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)){
                $realip = getenv("REMOTE_ADDR");
            }else{
                $realip = $unknown;
            }
        }
        $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
        return $realip;
    }

}
