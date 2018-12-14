<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

$logday = date('Ymd');
//文件目录
if (!file_exists(__DIR__.'/log/'.$logday)){
    mkdir (__DIR__."/log/".$logday,0755,true);
}
$log_test_file = __DIR__.'/log/'.$logday.'/day.log';
$log_test_str = "1\n";
writeFileForPv($log_test_file,$log_test_str);
//统计专用写日志
function writeFileForPv($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
    $fp = @fopen($file, $mode);
    // @flock($fp, 3);
    if (!$fp) {

    } else {
        @fwrite($fp, $str);
        @fclose($fp);
        // @umask($oldmask);
        // Return true;
    }
}
exit;