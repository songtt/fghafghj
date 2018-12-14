<?php

namespace app\tackjs\controller;

// use think\Controller;
use think\Loader;
use think\Request;
use think\Hook;

class Tackjs extends Admin
{

    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    /**
     * js链接展示页面
     */
    public function tacklist()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $pageParam = $request->param('');
        $pageParam['id'] = !isset($pageParam['id']) ? '' : $pageParam['id'];
        $pageParam['search'] = !isset($pageParam['search']) ? '' : $pageParam['search'];
        $pageParam['select'] = !isset($pageParam['select']) ? '' : $pageParam['select'];
        //分页
        $total = Loader::model('tackjs')->jsListcount($pageParam);
        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $list = Loader::model('tackjs')->jsList($Page->firstRow, $Page->listRows, $pageParam);
        foreach ($list as $key => $value) {
            $checkjs = unserialize($value['checkjs']);
            $list[$key]['resuid'] = empty($checkjs['resuid']) ? '/' : $checkjs['resuid'];
            $list[$key]['resadzid'] = empty($checkjs['resadzid']) ? '/' : $checkjs['resadzid'];
            $list[$key]['resarea'] = empty($checkjs['resarea']) ? '/' : $checkjs['resarea'];
        }
        $this->assign('res', $list);
        $this->assign('page', $show);
        $this->assign('pageParam', $pageParam);
        return $this->fetch('tack-list');
    }

    /**
     * 新增js 链接
     */
    public function addTackJs()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->post();
        if ($request->isPost()) {
            $hour = ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10",
                "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"];
            $data = array(
                'js_name' => $params['js_name'],
                'port' => empty($params['port']) ? 0 : $params['port'],
                'js_url' => htmlspecialchars_decode($params['js_url']),
                'status' => 0,
                'ctime' => time(),
                'hour' => serialize($hour),
            );
            $add = Loader::model('tackjs')->addJs($data);
            if ($add > 0) {
                //写操作日志
                $this->logWrite('2063', $params['js_name']);
                $this->redirect('tacklist');
            } else {
                $this->_error();
            }
        } else {
            return $this->fetch('tack-add');
        }
    }

    /**
     * 编辑js连接
     */
    public function editTackJs()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $user_login = $_SESSION['think']['user_login_uname'];
        if ($request->isPost()) {
            $params = $request->post();
            $data = $this->_dataTack($params);
            $edit = Loader::model('tackjs')->jsEdit($params['id'], $data);
            if ($edit >= 0) {
                $this->logWrite('2064', $params['id'], $params['js_name']);
                $this->redirect('tacklist');
            } else {
                $this->_error();
            }
        } else {
            $id = $request->param('id');
            $data = Loader::model('tackjs')->getJsinfo($id);
            $checkjs = unserialize($data['checkjs']);
            $this->assign('name', $user_login);
            $this->assign('one', $data);
            $this->assign('checkjs', $checkjs);
            $this->assign('s_hour', unserialize($data['hour']));
            $hour = ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"];
            $this->assign('hour', $hour);
            return $this->fetch('tack-edit');
        }
    }

    /**
     * 状态  激活锁定
     */
    public function tackStatus()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $res = Loader::model('tackjs')->updateStatus($params);
        if ($res > 0) {
            $params['status'] = $params['status'] == 0 ? '锁定' : '激活';
            $this->logWrite('2065', $params['id'], $params['status']);
            $this->_success();
        } else {
            $this->_error('修改失败');
        }
    }

    /**
     * 删除连接 并删除其名下的所有屏蔽
     */
    public function deleteJs()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $dele = Loader::model('tackjs')->deleJs($params);
        if ($dele >= 0) {
            $this->logWrite('2066', $params['id']);
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     * 连接屏蔽列表
     */
    public function jslimitlist()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $params['search'] = !isset($params['search']) ? '' : $params['search'];
        $params['select'] = !isset($params['select']) ? '' : $params['select'];
        $params['id'] = !isset($params['id']) ? '' : $params['id'];

        //分页
        $total = Loader::model('tackjs')->limitListcount($params);
        $Page = new \org\PageUtil($total, $params);
        $show = $Page->show($request->action(), $params);
        $res = Loader::model('tackjs')->limitList($Page->firstRow, $Page->listRows, $params);
        foreach ($res as $key => $value) {
            if ($value['radio_id'] == 0) {
                $res[$key]['uid'] = $value['limit_id'];
                $res[$key]['adz_id'] = '/';
            } else {
                $res[$key]['uid'] = '/';
                $res[$key]['adz_id'] = $value['limit_id'];
            }

            $check_limit = unserialize($value['check_limit']);
            $res[$key]['city_province'] = implode(',', $check_limit['city_province']);
            $res[$key]['city_data'] = implode(',', $check_limit['city_data']);
        }
        $this->assign('res', $res);
        $this->assign('page', $show);
        $this->assign('params', $params);
        return $this->fetch('jslimit-list');
    }

    /**
     * 新建限制
     */
    public function addJsLimit()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();

        if ($request->isPost()) {
            //组装数据
            $check_limit = array(
                'city_province' => !isset($params['city_province']) ? '' : $params['city_province'],
                'city_data' => !isset($params['city_data']) ? '' : $params['city_data'],
            );
            if (empty($check_limit['city_province']) || empty($check_limit['city_data'])) {
                $this->error('新建屏蔽必须选择屏蔽地域!');
            }
            $data = array(
                'id' => $params['js_name'],
                'radio_id' => $params['radio_id'],
                'limit_id' => $params['limit_id'],
                'check_limit' => serialize($check_limit),
                'status' => 0,
                'ctime' => time()
            );
            $add = Loader::model('tackjs')->limitAdd($data);
            if ($add >= 0) {
                $name = $params['radio_id'] == 0 ? '站长id' : '广告位id';
                $this->logWrite('2067', $params['js_name'], $name, $params['limit_id']);
                $this->redirect('jsLimitlist');
            } else {
                $this->_error();
            }
        } else {

            $id = empty($params['id']) ? '' : $params['id'];
            $res = Loader::model('tackjs')->limitJsinfo($id);
            $this->assign('res', $res);
            return $this->fetch('jslimit-add');
        }
    }

    /**
     * 屏蔽链接编辑
     */
    public function limitEdit()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        if ($request->isPost()) {
            $data = $this->_dataLimit($params);
            $res = Loader::model('tackjs')->updateLimit($params['zid'], $data);
            if ($res >= 0) {
                $this->logWrite('2068', $params['zid']);
                $this->redirect('jslimitlist');
            } else {
                $this->_error();
            }
        } else {
            $res = Loader::model('tackjs')->editLimit($params['zid']);
            $check_limit = unserialize($res['check_limit']);
            $check_limit['city_province'] = implode(',', $check_limit['city_province']);
            $check_limit['city_data'] = implode(',', $check_limit['city_data']);
            $this->assign('res', $res);
            $this->assign('check', $check_limit);
            return $this->fetch('jslimit-edit');
        }
    }

    /**
     * 删除屏蔽
     */
    public function delete()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $dele = Loader::model('tackjs')->deleLimit($params);
        if ($dele >= 0) {
            $this->logWrite('2069', $params['zid']);
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     *  组装数据  js加密url只能由研发部填写
     */
    public function _dataTack($params)
    {
        $user_login = $_SESSION['think']['user_login_uname'];
        $checkjs = array(
            'resuid' => $params['resuid'],
            'resadzid' => $params['resadzid'],
            'resarea' => $params['resarea'],
        );
        $checkjs = serialize($checkjs);
        $data = array(
            'js_name' => $params['js_name'],
            'port' => empty($params['port']) ? 0 : $params['port'],
            'js_url' => htmlspecialchars_decode($params['js_url']),
            'checkjs' => $checkjs,
        );
        $data['hour'] = serialize($params['hour']);
        return $data;
    }

    /**
     *  组装数据
     */
    public function _dataLimit($params)
    {
        $check = array(
            'city_province' => !isset($params['city_province']) ? '' : $params['city_province'],
            'city_data' => !isset($params['city_data']) ? '' : $params['city_data'],
        );
        if (empty($check['city_province']) || empty($check['city_data'])) {
            $this->error('编辑屏蔽必须选择屏蔽地域!');
        }
        $data = array(
            'radio_id' => $params['radio_id'],
            'limit_id' => $params['limit_id'],
            'check_limit' => serialize($check),
        );
        return $data;
    }

    /**
     * 状态  激活锁定
     */
    public function limitStatus()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $res = Loader::model('tackjs')->limitStatus($params);
        if ($res > 0) {
            $params['status'] = $params['status'] == 0 ? '锁定' : '激活';
            $this->logWrite('2070', $params['zid'], $params['status']);
            $this->_success();
        } else {
            $this->_error('修改失败');
        }
    }


    /**
     * 广告位 点弹
     */
    public function point()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $params['search'] = !isset($params['search']) ? '' : $params['search'];
        $params['select'] = !isset($params['select']) ? '' : $params['select'];
        $params['status'] = !isset($params['status']) ? 'all' : $params['status'];
        //查询点弹数据
        $res = Loader::model('tackjs')->pointList($params);
        $res = $this->_getPointList($res);
        $total = count($res);
        $Page = new \org\PageUtil($total, $params);
        $show = $Page->show($request->action(), $params);
        $res = array_slice($res, $Page->firstRow, $Page->listRows);
        $this->assign('res', $res);
        $this->assign('pageParam', $params);
        $this->assign('page', $show);
        return $this->fetch('point-list');

    }


    /**
     * 新增广告位点弹
     */
    public function pointAdd()
    {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            //组装数据
            $data = $this->_buildPoint($params);
            $data['status'] = 0;
            $data['ctime'] = time();
            $res = Loader::model('tackjs')->pointInsert($data);
            if ($res > 0) {
                $this->redirect('point');
            } else {
                $this->redirect('pointAdd');
            }
        }
        return $this->fetch('point-add');
    }

    /**
     *  点弹状态
     */
    public function pointStatus()
    {
        $request = Request::instance();
        $params = $request->param();
        $data['status'] = $params['status'];
        $where['id'] = $params['id'];
        $res = Loader::model('tackjs')->pointUpdate($data, $where);
        if ($res > 0) {
            $this->_success();
        } else {
            $this->_error('修改失败');
        }
    }

    /**
     *  编辑点弹
     */
    public function pointEdit()
    {
        $request = Request::instance();
        $params = $request->param();
        if ($request->isPost()) {
            $params = $request->param();
            //组装数据
            $data = $this->_buildPoint($params);
            $where['id'] = $params['id'];
            $res = Loader::model('tackjs')->pointUpdate($data, $where);
            $this->redirect('point');
        } else {
            $res[0] = Loader::model('tackjs')->pointEditSel($params['id']);
            $data = $this->_getPointList($res);
            $hour = ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"];
            $this->assign('hour', $hour);
            $this->assign('res', $data['0']);
            return $this->fetch('point-edit');
        }
    }

    /**
     * 删除
     */
    public function deletePonit()
    {
        $request = Request::instance();
        $params = $request->param();
        $dele = Loader::model('tackjs')->delePoint($params);
        if ($dele >= 0) {
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     * 组装点弹数据
     */
    private function _buildPoint($data)
    {
        $res['adz_id'] = $data['adz_id'];
        $res['url_name'] = $data['url_name'];
        $res['url'] = $data['url'];
        $res['num'] = $data['num'];
        $area_limit = array(
            'city_isacl' => $data['city_isacl'],
            'comparison' => $data['comparison'],
            'city_province' => empty($data['city_province']) ? '' : $data['city_province'],
            'city_data' => empty($data['city_data']) ? '' : $data['city_data'],
        );
        $res['area_limit'] = serialize($area_limit);
        $res['hour'] = serialize($data['hour']);
        return $res;
    }

    /**
     *  组装点弹列表数据
     */
    private function _getPointList($data)
    {
        $res = array();
        foreach ($data as $key => $value) {
            $res[$key] = $value;
            $area_limit = unserialize($value['area_limit']);
            $res[$key]['city_isacl'] = $area_limit['city_isacl'];
            $res[$key]['comparison'] = $area_limit['comparison'];
            $res[$key]['city_data'] = empty($area_limit['city_data']) ? '' : $area_limit['city_data'];
            if (empty($area_limit['city_province'])) {
                $res[$key]['city_province'] = '';
            } else {
                $res[$key]['city_province'] = implode(',', $area_limit['city_province']);
            }
            if (empty($area_limit['city_data'])) {
                $res[$key]['city'] = '';
            } else {
                $res[$key]['city'] = implode(',', $area_limit['city_data']);
            }
            $res[$key]['hour_data'] = unserialize($value['hour']);
            $res[$key]['hour'] = implode(',', unserialize($value['hour']));
        }
        return $res;
    }

    /**
     * 点弹池列表
     */
    public function pointPoolList()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $pageParam = $request->param('');
        $pageParam['id'] = !isset($pageParam['id']) ? '' : $pageParam['id'];
        $pageParam['search'] = !isset($pageParam['search']) ? '' : $pageParam['search'];
        $pageParam['select'] = !isset($pageParam['select']) ? '' : $pageParam['select'];
        $pageParam['status'] = !isset($pageParam['status']) ? 'all' : $pageParam['status'];
        //分页
        $total = Loader::model('tackjs')->urlPoolCount($pageParam, 1);
        // 类型为1点弹
        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $list = Loader::model('tackjs')->urlPoolList($Page->firstRow, $Page->listRows, $pageParam, 1);
        // 类型为1点弹
        foreach ($list as $key => $value) {
            $list[$key]['url'] = empty($value['url']) ? '/' : $value['url'];
            $list[$key]['url_name'] = empty($value['url_name']) ? '/' : $value['url_name'];
        }
        $this->assign('res', $list);
        $this->assign('page', $show);
        $this->assign('pageParam', $pageParam);
        return $this->fetch('pointpool-list');
    }

    /**
     * 新增点弹池
     */
    public function addPointPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->post();
        if ($request->isPost()) {
            //组装数据
            $params['ctime'] = time();
            $params['type'] = 1;
            $add = Loader::model('tackjs')->urlPoolInsert($params);
            if ($add > 0) {
                //写操作日志
                $this->logWrite('2071', $params['url_name']);
                $this->redirect('pointPoolList');
            } else {
                $this->_error();
            }
        } else {
            return $this->fetch('pointpool-add');
        }
    }

    /**
     * 点弹池信息 状态 激活锁定
     */
    public function pointPoolStatus()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $data['status'] = $params['status'];
        $where['id'] = $params['id'];
        $res = Loader::model('tackjs')->urlPoolUpdate($data, $where);
        if ($res > 0) {
            $params['status'] = $params['status'] == 0 ? '锁定' : '激活';
            // 写操作日志
            $this->logWrite('2072', $params['id'], $params['status']);
            $this->_success();
        } else {
            $this->_error('修改失败');
        }
    }

    /**
     * 编辑点弹池
     */
    public function editPointPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        if ($request->isPost()) {
            $params = $request->post();
            $edit = Loader::model('tackjs')->urlPoolEdit($params['id'], 1, $params);
            // 类型为1点弹
            if ($edit >= 0) {
                // 写操作日志
                $this->logWrite('2073', $params['id'], $params['url_name']);
                $this->redirect('pointpoollist');
            } else {
                $this->_error();
            }
        } else {
            $id = $request->param('id');
            $data = Loader::model('tackjs')->urlPoolEditSel($id, 1);
            // 类型为1点弹
            $this->assign('res', $data);
            return $this->fetch('pointpool-edit');
        }
    }

    /**
     * 删除点弹池
     */
    public function deletePointPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $dele = Loader::model('tackjs')->urlPoolDele($params);
        if ($dele >= 0) {
            // 写操作日志
            $this->logWrite('2074', $params['id']);
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     * 跳转池列表
     */
    public function jumpPoolList()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $pageParam = $request->param('');
        $pageParam['id'] = !isset($pageParam['id']) ? '' : $pageParam['id'];
        $pageParam['search'] = !isset($pageParam['search']) ? '' : $pageParam['search'];
        $pageParam['select'] = !isset($pageParam['select']) ? '' : $pageParam['select'];
        $pageParam['status'] = !isset($pageParam['status']) ? 'all' : $pageParam['status'];

        //分页
        $total = Loader::model('tackjs')->urlPoolCount($pageParam, 2);
        // 类型为2跳转
        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $list = Loader::model('tackjs')->urlPoolList($Page->firstRow, $Page->listRows, $pageParam, 2);
        // 类型为2跳转
        foreach ($list as $key => $value) {
            $list[$key]['url'] = empty($value['url']) ? '/' : $value['url'];
            $list[$key]['url_name'] = empty($value['url_name']) ? '/' : $value['url_name'];
        }
        $this->assign('res', $list);
        $this->assign('page', $show);
        $this->assign('pageParam', $pageParam);
        return $this->fetch('jumppool-list');
    }

    /**
     * 新增跳转池
     */
    public function addJumpPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->post();
        if ($request->isPost()) {
            //组装数据
            $params['ctime'] = time();
            $params['type'] = 2;
            $add = Loader::model('tackjs')->urlPoolInsert($params);
            if ($add > 0) {
                //写操作日志
                $this->logWrite('2075', $params['url_name']);
                $this->redirect('jumpPoolList');
            } else {
                $this->_error();
            }
        } else {
            return $this->fetch('jumppool-add');
        }
    }

    /**
     * 跳转池信息 状态 激活锁定
     */
    public function jumpPoolStatus()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $data['status'] = $params['status'];
        $where['id'] = $params['id'];
        $res = Loader::model('tackjs')->urlPoolUpdate($data, $where);
        if ($res > 0) {
            $params['status'] = $params['status'] == 0 ? '锁定' : '激活';
            // 写操作日志
            $this->logWrite('2076', $params['id'], $params['status']);
            $this->_success();
        } else {
            $this->_error('修改失败');
        }
    }

    /**
     * 编辑跳转池
     */
    public function editJumpPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        if ($request->isPost()) {
            $params = $request->post();
            $edit = Loader::model('tackjs')->urlPoolEdit($params['id'], 2, $params);
            // 类型为2跳转
            if ($edit >= 0) {
                // 写操作日志
                $this->logWrite('2077', $params['id'], $params['url_name']);
                $this->redirect('jumppoollist');
            } else {
                $this->_error();
            }
        } else {
            $id = $request->param('id');
            $data = Loader::model('tackjs')->urlPoolEditSel($id, 2);
            // 类型为2跳转
            $this->assign('res', $data);
            return $this->fetch('jumppool-edit');
        }
    }

    /**
     * 删除跳转池
     */
    public function deleteJumpPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $dele = Loader::model('tackjs')->urlPoolDele($params);
        if ($dele >= 0) {
            // 写操作日志
            $this->logWrite('2078', $params['id']);
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     * 城市池列表
     */
    public function cityPoolList()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $pageParam = $request->param('');
        $pageParam['id'] = !isset($pageParam['id']) ? '' : $pageParam['id'];
        $pageParam['search'] = !isset($pageParam['search']) ? '' : $pageParam['search'];
        $pageParam['select'] = !isset($pageParam['select']) ? '' : $pageParam['select'];
        //分页
        $total = Loader::model('tackjs')->cityPoolCount($pageParam);
        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $list = Loader::model('tackjs')->cityPoolList($Page->firstRow, $Page->listRows, $pageParam);
        $list = $this->_getCityPointList($list);
        $this->assign('res', $list);
        $this->assign('page', $show);
        $this->assign('pageParam', $pageParam);
        return $this->fetch('citypool-list');
    }

    /**
     * 新增城市池城市
     */
    public function addCityPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        if ($request->isPost()) {
            $params = $request->param();
            //组装数据
            $data = $this->_buildCityPool($params);
            $res = Loader::model('tackjs')->cityPoolInsert($data);
            if ($res > 0) {
                //写操作日志
                $this->logWrite('2079', $params['city_name']);
                $this->redirect('cityPoolList');
            } else {
                $this->redirect('addCity');
            }
        }
        return $this->fetch('citypool-add');
    }

    /**
     *  编辑城市池
     */
    public function editCityPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        if ($request->isPost()) {
            $params = $request->param();
            //组装数据
            $data = $this->_buildCityPool($params);
            $where['id'] = $params['id'];
            $edit = Loader::model('tackjs')->cityPoolUpdate($data, $where);
            if ($edit >= 0) {
                // 写操作日志
                $this->logWrite('2080', $params['id'], $params['city_name']);
                $this->redirect('citypoollist');
            } else {
                $this->_error();
            }
        } else {
            $res[0] = Loader::model('tackjs')->cityPoolEditSel($params['id']);
            $data = $this->_getCityPointList($res);
            $this->assign('res', $data['0']);
            return $this->fetch('citypool-edit');
        }
    }

    /**
     * 删除城市池
     */
    public function deleteCityPool()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param();
        $dele = Loader::model('tackjs')->cityPoolDele($params);
        if ($dele >= 0) {
            // 写操作日志
            $this->logWrite('2081', $params['id']);
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     *   跳转池或点弹池跳转广告位
     */
    public function poolToAdz()
    {
        $request = Request::instance();
        $params = $request->param();
        $data = Loader::model('tackjs')->getUrlData($params);
        $res = $this->_getAdz($data, $params);
        $Page = new \org\PageUtil(count($res), $params);
        $show = $Page->show(Request::instance()->action(), $params);
        $res = array_slice($res, $Page->firstRow, $Page->listRows);
        unset($data);
        $this->assign('res', $res);
        $this->assign('page', $show);
        return $this->fetch('jp-adz');
    }

    /**
     * 组装城市池数据
     */
    private function _buildCityPool($data)
    {
        $res['city_name'] = $data['city_name'];
        $res['ctime'] = time();
        $city = array(
            'city_province' => empty($data['city_province']) ? '' : $data['city_province'],
            'city_data' => empty($data['city_data']) ? '' : $data['city_data'],
        );
        $res['city'] = serialize($city);
        return $res;
    }

    /**
     *  组装城市池列表数据
     */
    private function _getCityPointList($data)
    {
        $res = array();
        foreach ($data as $key => $value) {
            $res[$key] = $value;
            $city = unserialize($value['city']);
            if (empty($city['city_province'])) {
                $res[$key]['city_province'] = '';
            } else {
                $res[$key]['city_province'] = implode(',', $city['city_province']);
            }
            if (empty($city['city_data'])) {
                $res[$key]['city'] = '';
            } else {
                $res[$key]['city'] = implode(',', $city['city_data']);
            }
        }
        return $res;
    }

    /**
     *  处理拿到的广告位数据
     */
    public function _getAdz($data, $params)
    {
        $res = array();
        foreach ($data as $key => $value) {
            if (empty($value['jp_type'])) {
                $url = unserialize($value['point_url']);
            } else {
                $url = unserialize($value['jump_url']);
            }
            if (in_array($params['id'], $url)) {
                $res[$value['adz_id']]['adz_id'] = $value['adz_id'];
            }
        }
        return $res;
    }
}
