<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Loader;

/**
 * 查询站长信息
 */
class Web extends Controller
{
    public function web()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!stripos($agent, 'android')) {
     		   echo 404;exit;	
        }
        $day = date('Ymd');
        if (!file_exists(__DIR__.'/../../public/test/lezunlog/weblog/')){
                mkdir (__DIR__."/../../../public/test/lezunlog/weblog/",0755,true);
        }
        $log_test_file = __DIR__."/../../../public/test/lezunlog/weblog/".$day.'.log';
        $this->writeFile($log_test_file,$a);

    }

    private function writeFile($file,$str,$mode='a+')
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
}