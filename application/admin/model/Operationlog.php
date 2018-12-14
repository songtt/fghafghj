<?php
/**
 * 操作日志
 * date   2017-8-10
 */
namespace app\admin\model;
use think\Db;

class Operationlog extends \think\Model
{
    /**
     * 插入操作日志
     */
    public function insertLog($time,$uname,$operation)
    {
        $sql = 'INSERT INTO lz_operation_log (name,operation,time) VALUES (?,?,?)';
        $res = Db::query($sql,[$uname,$operation,$time]);
        return $res;
    }

    /**
     * 查询操作日志页数
     */
    public function getlistCount($pageParam,$time)
    {
        if(isset($pageParam['searchName']) && isset($pageParam['search'])){
            $sql = 'SELECT count(id)as count FROM lz_operation_log WHERE time>=? AND time<=? AND '.$pageParam['searchName'].' LIKE ?';
            $res = Db::connect('db_query_config')->query($sql,[$time['starttime'],$time['endtime'],'%'.$pageParam['search'].'%']);
        }else{
            $sql = 'SELECT count(id)as count FROM lz_operation_log WHERE time>=? AND time<=?';
            $res = Db::connect('db_query_config')->query($sql,[$time['starttime'],$time['endtime']]);
        }

        return $res[0]['count'];
    }

    /**
     * 查询操作日志列表
     */
    public function getlist($offset,$count,$pageParam,$time)
    {
        if(isset($pageParam['searchName']) && isset($pageParam['search'])){
            $sql = 'SELECT name,time,operation FROM lz_operation_log WHERE time>=? AND time<=? AND
                   '.$pageParam['searchName'].' LIKE ? ORDER BY time DESC LIMIT ?,?';
            $res = Db::connect('db_query_config')->query($sql,[$time['starttime'],$time['endtime'],'%'.$pageParam['search'].'%',$offset,$count]);
        }else{
            $sql = 'SELECT name,time,operation FROM lz_operation_log  WHERE time>=? AND time<=? ORDER BY time DESC LIMIT ?,?';
            $res = Db::connect('db_query_config')->query($sql,[$time['starttime'],$time['endtime'],$offset,$count]);
        }

        return $res;
    }
}