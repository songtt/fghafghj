<?php

namespace app\admin\model;
use think\Db;

class Auxiliary extends \think\Model
{
    /**
     * 查看所有手机机型
     */
    public function getListCount($params)
    {
        if($params['status'] == "all"){
            $sql = 'SELECT COUNT(id) as count FROM lz_modle WHERE name like "%'.$params['search'].'%" ';
            $res = Db::query($sql);
        }elseif($params['status'] == "4"){
            $sql = 'SELECT COUNT(id) as count FROM lz_modle WHERE type_pay=? AND name like "%'.$params['search'].'%" ';
            $res = Db::query($sql,[1]);
        }else{
            $sql = 'SELECT COUNT(id) as count FROM lz_modle WHERE type=? AND name like "%'.$params['search'].'%" ';
            $res = Db::query($sql,[$params['status']]);
        }
        return $res[0]['count'];
    }

    /**
     * 查看所有手机机型
     */
    public function getList($params,$offset,$count)
    {
        if($params['status'] == "all"){
            $sql = 'SELECT id,name,type,type_pay,notice FROM lz_modle WHERE name like "%'.$params['search'].'%" LIMIT ?,? ';
            $res = Db::query($sql,[$offset,$count]);
        }elseif($params['status'] == "4"){
            $sql = 'SELECT id,name,type,type_pay,notice FROM lz_modle WHERE type_pay=? AND name like "%'.$params['search'].'%" LIMIT ?,? ';
            $res = Db::query($sql,[1,$offset,$count]);
        }else{
            $sql = 'SELECT id,name,type,type_pay,notice FROM lz_modle WHERE type=? AND name like "%'.$params['search'].'%" LIMIT ?,? ';
            $res = Db::query($sql,[$params['status'],$offset,$count]);
        }
        return $res;
    }

    /**
     * 获取编辑的机型内容
     */
    public function getOne($id)
    {
        $sql = 'SELECT id,name,type,type_pay,notice FROM lz_modle WHERE id=?';
        $res = Db::query($sql,[$id]);
        return $res;
    }

    /**
     * 查看该机型是否已经添加
     */
    public function getName($name)
    {
        $sql = 'SELECT id FROM lz_modle WHERE name=?';
        $res = Db::query($sql,[$name]);
        return $res;
    }

    /**
     * add data
     * param data 
     */
    public function add($data)
    {
        $data['ctime'] = time();
        $res = Db::name('modle')->insert($data);
        return $res;
    }

    /**
     * 编辑手机机型
     */
    public function edit($params)
    {
        $sql = 'UPDATE lz_modle SET type=?,type_pay=?,notice=? WHERE id=?';
        $res = Db::execute($sql,[$params["type"],$params["type_pay"],$params["notice"],$params["id"]]);
        return $res;
    }

    /**
     * 删除
     * param
     */
    public function delOne($id)
    {
        $sql = 'DELETE FROM lz_modle WHERE id=?';
        $res = Db::execute($sql,[$id]);
        return $res;
    }

    /**
     * 机型展示列表
     * param data 
     */
    public function mobileLst($offset,$count)
    {
        $sql = 'SELECT id,name,num,ctime FROM lz_mobile_modle WHERE num>=? ORDER BY num DESC LIMIT ?,?';
        $res = Db::query($sql,[50000,$offset,$count]);
        return $res;
    }

    /**
     * 机型展示列表页码
     * param data 
     */
    public function mobileCount()
    {
        $sql = 'SELECT COUNT(id) AS count FROM lz_mobile_modle WHERE num>=?';
        $res = Db::query($sql,[50000]);
        if(empty($res)){
            return '';
        }else{
            return $res[0]['count'];
        }
        
    }



}