<?php

/**
 * 定向功能 for plan-list 
 */ 
function f_planlst($arr){
    $data = unserialize($arr);
    if(empty($data['city'])){
        return '';   
    }
    if($data['city']['isacl'] == '1'){
        return '已开启';
    }else{
        return '未开启';
    }
}

/**
 * 会员限制 for plan-list 
 */ 
function f_planLimit($param){
    if(isset($param)){
        return '有限制';
    }else{
        return '未限制';
    }
}

/**
 * 活动周期 for plan-list 
 */ 
function f_planExpiredate($arr){
    $data = unserialize($arr);
    if(empty($data['expire_date'])){
        return '';   
    }
    $arr = $data['expire_date'];
    $date = $arr['year'].'-'.$arr['month'].'-'.$arr['day'];
    $nowDate = date('Y-m-d',time());
    $var = $nowDate.'至'.$date;
    return $var;
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

/**
 * 取整
 */
function num_Round($arr)
{
    $integer = floor($arr);
    return $integer;
}
