<?php

/**
 * 定向功能 for plan-list 
 */ 
function f_planlst($arr){
    $data = unserialize($arr);
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
    $arr = $data['expire_date'];
    $date = $arr['year'].'-'.$arr['month'].'-'.$arr['day'];
    $nowDate = date('Y-m-d',time());
    $var = $nowDate.'至'.$date;
    return $var;
}