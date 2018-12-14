<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class Person extends Model
{
    /**
     * 查询数据
     */
    public function getone($param)
    {
        // 查询数据
        // $one =Db::table('person')->select();
        // $one = Person::all();
        $one = Db::query('select * from person where id=?',[$param]);
        // echo $sql = 'select * from person where id='.$param;
        // $one = Db::query("select * from person where id=$sql");
        echo Db::getlastsql();exit;
        return $one;
    }
}