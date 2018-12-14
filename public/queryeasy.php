<?php
$type = !empty($_GET['type']) ? $_GET['type'] : '';
if($type == 1){
	//登录请求
	$url = 'http://new.lezun.com/admin/Api/curlLogin';
//	$url = 'http://www.lzbdgit.com/admin/Api/curlLogin';
	$data = array(
		'username'=>$_POST['username'],
		'password'=>$_POST['password'],
	);
//	$data = 'username'=$_POST['username']&'password'=$_POST['password'];
	//curl  请求
	$result = curl($data,$url);
	$_COOKIE['token'] = $result;
	setcookie('token',$result,time()+180);
	echo json_encode($result);

}else{
	$token = $_COOKIE['token'];
	// token 为空直接返回
	if(!empty($token)){	
		//查询请求
		$url = 'http://new.lezun.com/admin/Api/queryEasy';
		$content = $_POST['content'];
		if(!empty($content)){
			$data = "content=".$content;

			//curl  请求
			$result = curl($data,$url);
			//查询数据返回为空
			if($result == '0'){
				echo 0;
			}elseif($result == '1'){

				echo 1;
			}elseif($result == '2'){

				echo json_encode(2);
			}else{
				if(!empty($result)){
					//取出key值
					foreach($result[0] as $key => $val){
						$result['title'][] = $key;
					}
					$result['num'] = count($result)-1;
					echo json_encode($result);
				}else{
					echo json_encode(0);
				}

			}
		}else{
			echo '<script>alert("非法参数请求");window.location.href="http://www.lzbd.com/queryeasy.html";</script>';
		}
	}else{
		echo json_encode(2);
	}
}


/**
 * 公用 curl方法
 * */
function curl($data,$url)
{
	$ch = curl_init();

// 添加参数
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 执行HTTP请求
	curl_setopt($ch , CURLOPT_URL , $url);
	$res = curl_exec($ch);

	$result = json_decode($res);
	return $result;
}
