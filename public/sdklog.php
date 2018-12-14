<?php
//线上的话  打开内存设置
ini_set('memory_limit','4096M');


set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE) {

    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (false) {
        $output = PHP_EOL . $label . $output . PHP_EOL;
    } else {
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, $flags);
        }
        $output = '<pre>' . $label . $output . '</pre>';
    }
    if ($echo) {
        echo($output);
        return null;
    } else {
        return $output;
    }
}

$h = array(
	"00","01","02","03","04","05","06","07","08","09","10","11","12","13","14","15","16","17","18","19","20","21","22","23"
);
$f = array(
	0,1,2,3,4,5
);

//获取时间
$daylog = date('Ymd');
$data = array();
foreach ($h as $key => $value) {
	foreach ($f as $k => $v) {
		$file_path = $daylog."/"."sdk".$value."-".$v.".log";
		if(file_exists($file_path)){
    		$file_one = file($file_path);
		}
		$data[$key] = $file_one;
	}
}
$res = array();
$i = 0;
foreach ($data as $i => $arr) {
	foreach ($arr as $a => $aa) {
		$star = substr($aa,0,strpos($aa, ',modle'));
		//iemi
		$unique = substr($star,13);
        $id = substr($aa,0,strpos($aa, ',unique='));
		$i = $i +1;
		$res[$id][$unique]['i'] = $i;
		$res[$id][$unique]['id'] = $id;

	}
}


foreach ($res as $key => $value) {
    echo $key, " IMEI 去重后 :".count($res[$key])."</br>";
}
exit;

// $file_path = "/home/lezunlog/nginx1/".$daylog."/".$file_name;

// @fclose($file_one);