<?php

// ctime 创建时间
function ctime($ctime){
	$ctime = date('Y-m-d H:m',$ctime);
	return $ctime;
}

//处理小数
function decimal($arr)
{

    $substr = stripos($arr,'.');
    if($substr == false){
        $str = $arr;
    }else{
        $substr = $substr+3;
        $str = substr($arr,0,$substr);
    }
    return $str;
}

//处理小数
function integer($arr)
{

    $substr = stripos($arr,'.');
    if($substr == false){
        if($arr>1){
           $str = $arr; 
        }else{
            $str = '缓存中';
        }
    }else{
        if($arr >= 1){
            $substr = $substr+3;
            $str = substr($arr,0,$substr);
        }else{
            $str = '0.00';
        }
    }
    return $str;
}

/**
 * 投放设备 for plan-list
 */
function f_plan_mobile($arr){
    $data = unserialize($arr);
    if(empty($data['mobile']['data'])){
        return '不限制';
    }else{
        $mdata = $data['mobile']['data'];
        $res = '不限制';
        if('1'==$data['mobile']['isacl']){
            $res = implode(',', $mdata);
        }
    }
    return $res;
}

/**
 * 取整
 */
function num_Round($arr)
{
    $integer = floor($arr);
    return $integer;
}

/*
 * 处理小数
 */
function process_decimal($arr)
{

    $substr = stripos($arr,'.');
    if($substr == false){
        $str = $arr;
    }else{
        $substr = $substr+3;
        $str = substr($arr,0,$substr);
    }
    return $str;
}