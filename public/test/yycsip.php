<!doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <title>area列表</title>
</head>
<body>
<style>
    .tabself tr td {
        padding:5px;
    }

</style>


<?php
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

//这是从库  从库只能读（也就是只能select）
$db_link='mysql:host=127.0.0.1;dbname=lezun;port=3306';
$db_root='root';
$db_password='xya197a3321';

try{
    $selPdo = new PDO($db_link,$db_root,$db_password);
    $selPdo->query("set names utf8");
    $selPdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}catch(PDOException $e){
    echo 'database error! ';
}

$sql = ' SELECT a.*,b.username FROM lz_area_ip_stat as a  LEFT JOIN lz_users as b ON a.uid=b.uid ORDER BY a.id desc limit 500 ';
$select = $selPdo->prepare($sql);
$select->execute();
$selRes = $select->fetchAll();
?>

<table class="tabself" border="1" align="center" >
<tr><td colspan="7"><span>注：第一个是最新的（倒序）</span></td></tr>
<tr>
    <td>站长uid</td>
    <td>站长用户名</td>
    <td>IP</td>
    <td>地域</td>
    <td>机型</td>
    <td>最新访问时间</td>
    <td>登陆次数</td>
</tr>



<?php

foreach ($selRes as $key => $value) {

?>

<tr>

<td><?php echo $value['uid'];  ?></td>
<td><?php echo $value['username'];  ?></td>
<td><?php echo $value['ip'];  ?></td>
<td><?php echo $value['area'];  ?></td>
<td><?php echo $value['model'];  ?></td>
    <td><?php echo date("Y-m-d H:i:s",$value['stime']);  ?></td>
    <td><?php echo $value['num'];  ?></td>

</tr>


<?php

}


$selPdo = null;
exit;


?>

</table>
</body>
</html>