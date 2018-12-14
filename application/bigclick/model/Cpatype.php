<?php
/**
 * 产品分类
 *------------------------------------------------------
 * @date   2018-9-10
 *------------------------------------------------------
 */
namespace app\bigclick\model;
use think\Db;
use think\Model;


class Cpatype extends Model
{

	//产品分类了列表
	public function typeList()
	{
		$sql = 'SELECT id,type_name,type_info,type FROM lz_pd_type';
		$res = Db::query($sql);
		return $res;
	}
	//产品分类编辑
	public function typeEdit($params)
	{
		$res = Db::name('pd_type')->where($params)->find();
		return $res;
	}
	//新增产品分类
	public function addType($data)
	{
		$res = Db::name('pd_type')->insert($data);
		return $res;
	}

	//查询此分类应用的产品
	public function getPro($id)
	{
		$sql = 'SELECT pro_type FROM lz_bigclick_cpm_product WHERE pro_type=?' ;
		$res = Db::query($sql,[$id]);
		return $res;
	}

	//更新数据
	public function typeUpdate($where,$data)
	{
		$res = Db::name('pd_type')->where($where)->update($data);
		return $res;
	}
}