<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Controller;
use think\Cache;
use think\Db;
use think\Request;
use think\Config;
use think\Session;

class Admin extends Controller
{
    /**
     * 控制器初始化
     * @access protected
     * @return void
     */
    protected function _initialize()
    {
        $this->init_site();
        //$this->init_user();
        
    }
    /**
     * 加载网站配置
     * @access protected
     * @return void
     */
    private function init_site()
    {
        $site_config = Cache::get('SITE_CONFIG');
        if (!$site_config){
            $site_config = Db::table('tp_site_config')->field('code,value')->select();
            Cache::set('SITE_CONFIG', $site_config);            
        }
        foreach ($site_config as $arr) {
            Config::set($arr['code'], $arr['value']);
        }
    }
    /**
     * 管理员初始化
     * @access protected
     * @return void
     */
    private function init_user()
    {
        //不满足条件跳转到登录界面
        $request = Request::instance();
        if ((empty(Session::get('admin_id')) && ($request->action() != 'login'))) 
        {              
            $this->redirect('admin/user/login',['from'=>1]);                                                                                            
        }
    }
}