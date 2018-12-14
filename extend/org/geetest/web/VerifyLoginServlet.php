<?php
/*
 * geetest
 *----------------------------------------------------------------
 * @author: liumin
 * @date : 2016-9-14 10:25:49
 *----------------------------------------------------------------
 */
namespace org\geetest\web;
use org\geetest\lib\GeetestLib as GeetestLib;


class VerifyLoginServlet
{
   /**
    * 二次验证
    * author LIUMIN
    */
    public function geetest()
    {
        require_once dirname(dirname(__FILE__)) . '/lib/class.geetestlib.php';
        require_once dirname(dirname(__FILE__)) . '/config/config.php';
        session_start();
        if($_POST['type'] == 'pc'){
            $GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        }elseif ($_POST['type'] == 'mobile') {
            $GtSdk = new GeetestLib(MOBILE_CAPTCHA_ID, MOBILE_PRIVATE_KEY);
        }

        $user_id = $_SESSION['user_id'];
        if ($_SESSION['gtserver'] == 1) {   //服务器正常
            $result = $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $user_id);
            if ($result) {
                echo '{"status":"success"}';
            } else{
                echo '{"status":"fail"}';
            }
        }else{  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
                echo '{"status":"success"}';
            }else{
                echo '{"status":"fail"}';
            }
        }
    }
}
