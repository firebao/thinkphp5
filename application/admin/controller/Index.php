<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\controller\Admin;


class Index extends Admin 
{
    /**
     * Home界面
     * @access public
     * @return
     */
    public function main()
    {
        //TODO:警告信息
        $warning = array();
        $this->assign('warning',$warning);
        //TODO:订单信息
        
        //TODO:商品信息
        
        //TODO:访问信息
        
        //系统信息
        $sys_info = array();
        $sys_info['os']            = PHP_OS;
        $sys_info['ip']            = $_SERVER['SERVER_ADDR'];
        $sys_info['web_server']    = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['php_ver']       = PHP_VERSION;
        $sys_info['zlib']          = function_exists('gzclose') ? "是" : "否";
        $sys_info['safe_mode']     = (boolean) ini_get('safe_mode') ?  "是" : "否";
        $sys_info['safe_mode_gid'] = (boolean) ini_get('safe_mode_gid') ? "是" : "否";
        $sys_info['timezone']      = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "未设置";
        $sys_info['socket']        = function_exists('fsockopen') ? "是" : "否";
        $sys_info['max_filesize']  = ini_get('upload_max_filesize');
        

        $this->assign('sys_info',$sys_info);
        return $this->fetch();
    }
}
