<?php
/**
 * js 添加
 *------------------------------------------------------
 * @author wangxz<3528319@qq.com>
 * @date   2016-6-2
 *------------------------------------------------------
 */
namespace app\tackjs\model;

use think\Db;
use think\Model;


class Tackjs extends Model
{
    /**
     * JS连接列表
     */
    public function jsList($offset, $count, $params)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT * FROM lz_tackjs';
        if (!empty($params['search'])) {
            if ($params['select'] == 'js_name') {
                $sele = $params['search'];
                $sql .= ' WHERE js_name like "%' . $sele . '%" ORDER BY id DESC Limit ?,? ';
                $res = Db::query($sql, [$offset, $count]);
            } else {
                $sql .= ' WHERE id=? ORDER BY id DESC Limit ?,? ';
                $res = Db::query($sql, [$params['search'], $offset, $count]);
            }
        } else {
            $sql .= ' ORDER BY id DESC Limit ?,? ';
            $res = Db::query($sql, [$offset, $count]);
        }
        return $res;
    }

    /**
     * JS连接列表分页
     */
    public function jsListcount($params)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT js_name,id FROM lz_tackjs';
        if (!empty($params['search'])) {
            if ($params['select'] == 'js_name') {
                $sele = $params['search'];
                $sql .= ' WHERE js_name like "%' . $sele . '%" ORDER BY id DESC';
                $res = Db::query($sql);
            } else {
                $sql .= ' WHERE id=? ORDER BY id DESC';
                $res = Db::query($sql, [$params['search']]);
            }
        } else {
            $sql .= ' ORDER BY id DESC';
            $res = Db::query($sql);
        }
        return count($res);
    }

    /**
     *  添加js
     */
    public function addJs($data)
    {
        $insert = Db::name('tackjs')->insert($data);
        return $insert;
    }

    /**
     *  编辑js
     */
    public function getJsinfo($id)
    {
        $sql = 'SELECT * FROM lz_tackjs WHERE id=?';
        $res = Db::query($sql, [$id]);
        return empty($res) ? 0 : $res['0'];
    }

    /**
     *  编辑js
     */
    public function jsEdit($id, $data)
    {
        $map = array(
            'id' => $id,
        );
        $res = Db::name('tackjs')->where($map)->update($data);
        return $res;
    }

    /**
     *  更新链接状态
     */
    public function updateStatus($parsm)
    {
        $map = array(
            'id' => $parsm['id'],
        );
        $data = array(
            'status' => $parsm['status'],
        );
        $res = Db::name('tackjs')->where($map)->update($data);
        return $res;
    }


    /**
     *  新建链接屏蔽
     */
    public function limitAdd($data)
    {
        $insert = Db::name('tackjs_limit')->insert($data);
        return $insert;
    }

    /**
     * 链接屏蔽列表
     */
    public function limitList($offset, $count, $params)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT a.id,a.js_name,b.* FROM lz_tackjs_limit AS b LEFT JOIN lz_tackjs AS a ON a.id=b.id';
        if (empty($params['search'])) {
            $sql .= ' ORDER BY zid DESC Limit ?,? ';
            $res = Db::query($sql, [$offset, $count]);
        } else {
            if ($params['select'] == 'js_name') {
                $sele = $params['search'];
                $sql .= ' WHERE a.js_name like "%' . $sele . '%" ORDER BY zid DESC  Limit ?,? ';
                $res = Db::query($sql, [$offset, $count]);
            } elseif ($params['select'] == 'limit_id') {
                $sql .= ' WHERE b.limit_id=? ORDER BY zid DESC  Limit ?,? ';
                $res = Db::query($sql, [$params['search'], $offset, $count]);
            } else {
                $sql .= ' WHERE a.id=? ORDER BY zid DESC  Limit ?,? ';
                $res = Db::query($sql, [$params['search'], $offset, $count]);
            }
        }
        return $res;
    }

    /**
     * 链接屏蔽列表个数
     */
    public function limitListcount($params)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT a.id,a.js_name,b.limit_id FROM lz_tackjs_limit AS b LEFT JOIN lz_tackjs AS a ON a.id=b.id';
        if (empty($params['search'])) {
            $res = Db::query($sql);
        } else {
            if ($params['select'] == 'js_name') {
                $sele = $params['search'];
                $sql .= ' WHERE a.js_name like "%' . $sele . '%"';
                $res = Db::query($sql);
            } elseif ($params['select'] == 'limit_id') {
                $sql .= ' WHERE b.limit_id=?';
                $res = Db::query($sql, [$params['search']]);
            } else {
                $sql .= ' WHERE a.id=?';
                $res = Db::query($sql, [$params['search']]);
            }
        }
        return count($res);
    }

    /**
     *  新建js 链接屏蔽
     */
    public function limitJsinfo($id)
    {
        if (empty($id)) {
            $last = '';
        } else {
            $last = ' WHERE id=' . $id . '';
        }
        $sql = 'SELECT id,js_name FROM lz_tackjs ' . $last . '';
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 编辑链接屏蔽
     */
    public function editLimit($zid)
    {
        $sql = 'SELECT a.id,a.js_name,b.* FROM lz_tackjs AS a LEFT JOIN lz_tackjs_limit AS b ON a.id=b.id WHERE b.zid=?';
        $res = Db::query($sql, [$zid]);
        return empty($res) ? 0 : $res['0'];
    }

    /**
     *  编辑更新连接屏蔽
     */
    public function updateLimit($zid, $data)
    {
        $map = array(
            'zid' => $zid,
        );
        $res = Db::name('tackjs_limit')->where($map)->update($data);
        return $res;
    }


    /**
     *  更新链接状态
     */
    public function limitStatus($parsm)
    {
        $map = array(
            'zid' => $parsm['zid'],
        );
        $data = array(
            'status' => $parsm['status'],
        );
        $res = Db::name('tackjs_limit')->where($map)->update($data);
        return $res;
    }


    /**
     *  删除屏蔽
     */
    public function deleLimit($params)
    {
        $map = array(
            'zid' => $params['zid'],
        );
        $res = Db::name('tackjs_limit')->where($map)->delete();
        if ($res > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 删除js链接 和其名下的所有屏蔽
     */
    public function deleJs($params)
    {
        $map = array(
            'id' => $params['id'],
        );
        $res = Db::name('tackjs')->where($map)->delete();
        Db::name('tackjs_limit')->where($map)->delete();
        if ($res > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 查询点弹
     */
    public function pointList($params)
    {
        $sql = 'SELECT id,adz_id,num,url,url_name,area_limit,hour,status FROM lz_point';
        if (!empty($params['search']) && $params['status'] == 'all') {
            $params['search'] = trim($params['search']);
            if ($params['select'] == 'adz_id') {
                $sql .= ' WHERE adz_id="' . $params['search'] . '" ORDER BY ctime DESC';
            } else {
                $sql .= ' WHERE url like "%' . $params['search'] . '%" ORDER BY ctime DESC';
            }
        }elseif(!empty($params['search'])&& $params['status'] != 'all'){
            if ($params['select'] == 'adz_id') {
                $sql .= ' WHERE adz_id="' . $params['search'] . '" and status ='.$params['status'].' ORDER BY ctime DESC';
            } else {
                $sql .= ' WHERE url like "%' . $params['search'] . '%" and status ='.$params['status'].'. ORDER BY ctime DESC';
            }
        } else {
            if($params['status'] == 'all'){
                $sql .= ' ORDER BY ctime DESC';
            }else{
                $sql .= ' where status='.$params['status'].' ORDER BY ctime DESC';
            }
        }
        $res = Db::query($sql);
        return $res;
    }

    /**
     * 点弹插入数据
     */
    public function pointInsert($data)
    {
        $res = Db::name('point')->insert($data);
        return $res;
    }

    /**
     * 点弹更新数据
     */
    public function pointUpdate($data, $where)
    {
        $res = Db::name('point')->where($where)->update($data);
        return $res;
    }

    /**
     * 点弹编辑
     */
    public function pointEditSel($id)
    {
        $res = Db::name('point')->where('id', $id)->find();
        return $res;
    }

    /**
     * 删除
     */
    public function delePoint($params)
    {
        $map = array(
            'id' => $params['id'],
        );
        $res = Db::name('point')->where($map)->delete();
        if ($res > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 点弹池跳转池列表
     */
    public function urlPoolList($offset, $count, $params, $type)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT id, url_name, url, status FROM lz_url_pool WHERE type=?';
        if($params['status'] == 'all'){
            if (!empty($params['search'])) {
                if ($params['select'] == 'url_name') {
                    $sele = $params['search'];
                    $sql .= ' AND url_name like "%' . $sele . '%" ORDER BY id DESC Limit ?,? ';
                    $res = Db::query($sql, [$type, $offset, $count]);
                } elseif ($params['select'] == 'url') {
                    $sele = $params['search'];
                    $sql .= ' AND url like "%' . $sele . '%" ORDER BY id DESC Limit ?,? ';
                    $res = Db::query($sql, [$type, $offset, $count]);
                } else {
                    $sql .= ' AND id=? ORDER BY id DESC Limit ?,? ';
                    $res = Db::query($sql, [$type, $params['search'], $offset, $count]);
                }
            } else {
                $sql .= ' ORDER BY id DESC Limit ?,? ';
                $res = Db::query($sql, [$type, $offset, $count]);
            }
        }else{
            if (!empty($params['search'])) {
                if ($params['select'] == 'url_name') {
                    $sele = $params['search'];
                    $sql .= ' AND url_name like "%' . $sele . '%" AND status = '.$params['status'].' ORDER BY id DESC Limit ?,?';
                    $res = Db::query($sql, [$type, $offset, $count]);
                } elseif ($params['select'] == 'url') {
                    $sele = $params['search'];
                    $sql .= ' AND url like "%' . $sele . '%"  AND status = '.$params['status'].' ORDER BY id DESC Limit ?,?';
                    $res = Db::query($sql, [$type, $offset, $count]);
                } else {
                    $sql .= ' AND id=?  AND status =  '.$params['status'].' ORDER BY id DESC Limit ?,?';
                    $res = Db::query($sql, [$type, $params['search'], $offset, $count]);
                }
            } else {
                $sql .= ' AND status = '.$params['status']. ' ORDER BY id DESC Limit ?,? ';
                $res = Db::query($sql, [$type, $offset, $count]);
            }
        }

        return $res;
    }

    /**
     * 点弹池/跳转池记录总数
     */
    public function urlPoolCount($params, $type)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT count(*) as count FROM lz_url_pool WHERE type=?';
        if (!empty($params['search'])) {
            if ($params['select'] == 'url_name') {
                $sele = $params['search'];
                $sql .= ' AND url_name like "%' . $sele . '%"';
                $res = Db::query($sql, [$type]);
            } elseif ($params['select'] == 'url') {
                $sele = $params['search'];
                $sql .= ' AND url like "%' . $sele . '%"';
                $res = Db::query($sql, [$type]);
            } else {
                $sql .= ' AND id=?';
                $res = Db::query($sql, [$type, $params['search']]);
            }
        } else {
            $res = Db::query($sql, [$type]);
        }
        return $res[0]['count'];
    }

    /**
     * 点弹池/跳转池插入数据
     */
    public function urlPoolInsert($data)
    {
        $res = Db::name('url_pool')->insert($data);
        return $res;
    }

    /**
     * 点弹池/跳转池更新数据
     */
    public function urlPoolUpdate($data, $where)
    {
        $res = Db::name('url_pool')->where($where)->update($data);
        return $res;
    }

    /**
     *  点弹池/跳转池编辑
     */
    public function urlPoolEdit($id, $type, $data)
    {
        $map = array(
            'id' => $id,
            'type' => $type
        );
        $res = Db::name('url_pool')->where($map)->update($data);
        return $res;
    }

    /**
     * 点弹池/跳转池获取指定ID信息
     */
    public function urlPoolEditSel($id, $type)
    {
        $map = array(
            'id' => $id,
            'type' => $type
        );
        $res = Db::name('url_pool')->where($map)->find();
        return $res;
    }

    /**
     * 点弹池/跳转池删除
     */
    public function urlPoolDele($params)
    {
        $map = array(
            'id' => $params['id'],
        );
        $res = Db::name('url_pool')->where($map)->delete();
        if ($res > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 城市池列表
     */
    public function cityPoolList($offset, $count, $params)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT id, city_name, city FROM lz_city_pool';
        if (!empty($params['search'])) {
            if ($params['select'] == 'city_name') {
                $sele = $params['search'];
                $sql .= ' WHERE city_name like "%' . $sele . '%" ORDER BY id DESC Limit ?,? ';
                $res = Db::query($sql, [$offset, $count]);
            } else {
                $sql .= ' WHERE id=? ORDER BY id DESC Limit ?,? ';
                $res = Db::query($sql, [$params['search'], $offset, $count]);
            }
        } else {
            $sql .= ' ORDER BY id DESC Limit ?,? ';
            $res = Db::query($sql, [$offset, $count]);
        }
        return $res;
    }

    /**
     * 城市池记录总数
     */
    public function cityPoolCount($params)
    {
        if (!empty($params['id'])) {
            $params['search'] = $params['id'];
            $params['select'] = 'id';
        }
        $sql = 'SELECT count(*) as count FROM lz_city_pool';
        if (!empty($params['search'])) {
            if ($params['select'] == 'city_name') {
                $sele = $params['search'];
                $sql .= ' WHERE city_name like "%' . $sele . '%"';
                $res = Db::query($sql);
            } else {
                $sql .= ' WHERE id=?';
                $res = Db::query($sql, [$params['search']]);
            }
        } else {
            $res = Db::query($sql);
        }
        return $res[0]['count'];
    }

    /**
     * 城市池插入数据
     */
    public function cityPoolInsert($data)
    {
        $res = Db::name('city_pool')->insert($data);
        return $res;
    }

    /**
     * 城市池更新数据
     */
    public function cityPoolUpdate($data, $where)
    {
        $res = Db::name('city_pool')->where($where)->update($data);
        return $res;
    }

    /**
     * 城市池编辑
     */
    public function cityPoolEditSel($id)
    {
        $res = Db::name('city_pool')->where('id', $id)->find();
        return $res;
    }

    /**
     * 城市池删除
     */
    public function cityPoolDele($params)
    {
        $map = array(
            'id' => $params['id'],
        );
        $res = Db::name('city_pool')->where($map)->delete();
        if ($res > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     *  获取点弹或跳转链接应用的广告位
     */
    public function getUrlData($param)
    {
        if (empty($param['type'])) {
            $url = 'point_url';
        } else {
            $url = 'jump_url';
        }
        $sql = 'SELECT adz_id,rule_name,jp_type,point_url,jump_url FROM lz_adzone_rule WHERE jp_type=? AND ' . $url . ' REGEXP ' . $param['id'] . '';
        return Db::query($sql, [$param['type']]);
    }
}
