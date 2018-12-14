<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class Feedback extends Model
{
	/**
	 *  用户反馈信息插入数据表中
	 */
	public function add($data)
	{
		$res = Db::name('feed')->insert($data);
		return $res;
	}

	/**
	 *  查询数据
	 */
	public function info()
	{
		$sql = 'SELECT a.id,a.name,a.message,a.image,a.ctime,b.uid,b.reply,b.ctime AS time FROM lz_feed as a LEFT JOIN
				 lz_rep as b ON a.id=b.uid ORDER BY a.id DESC';
		$res = Db::query($sql);
		return $res;
	}

	/**
	 *  用户回复信息插入数据表中
	 */
	public function addOne($data)
	{
		
		$res = Db::name('rep')->insert($data);
		return $res;
	}
}
