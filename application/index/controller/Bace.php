<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: Bace控制器模块
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Config;
use think\Session;
use think\Cookie;
use app\common\model\Category;
use think\Request;
use app\index\model\Area;

class Bace extends Controller
{
    public $session_id;             //会话id
    public $cate_tree = array();    //分类树信息
    
    /**
     * 控制器初始化
     * @access protected
     * @return void
     */
    protected function _initialize()
    {
        parent::_initialize();                      //父类初始化
        $this->session_id = session_id();           //获取当前会话id
        define('SESSION_ID', $this->session_id);    //定义会话id常量，供其他方法调用 

        //判断当前用户是否为手机用户，并保存cookie
        if (weido_is_mobile()) {
            cookie('weido_is_mobile', 1, 3600);
        } else {
            cookie('weido_is_mobile', 0, 3600);              
        }
        
        $this->initSite();                         //网站配置初始化
        $this->initUser();                         //用户信息初始化
    }
    
    /**
     * 网站配置信息初始化
     * @access private
     * @return void
     */
    private function initSite()
    {        
        date_default_timezone_set('PRC');           //设定用于所有日期时间函数的默认时区
        
        //从数据库获取网站配置并保存在THINKPHP Config中
        if (!Config::has('site')) {
            $arr = array();
            $result = Db::table('tp_site_config')->cache(true, WEIDO_CACHE_TIME)->field('code,value')->select();
            foreach ($result as $row) {
                $arr[$row['code']] = $row['value'];
            }
            Config::set('site', $arr);
        }
        
        //如果网站关闭，输出关闭的信息
        if (Config::get('site.shop_closed')) {
            //TODO:可以用美化后的页面显示网站关闭，现在是简易的方式            
            header('Content-Type: text/html; charset=utf-8');
            die('<div style="margin: 150px; text-align: center; font-size: 14px"><p>抱歉！商店已关闭</p></div>');
        }       
        
        //获取默认地区id以及信息
        $area       = new Area();                     
        $areaId     = $area->getDefaultCity();     //所在城市id
        $area       = $area->getArea($areaId);
        $this->assign('currArea',$area);
        
        //TODO:这里可以添加是否是搜索引擎蜘蛛访问
        
        //公共模板变量赋值
        $this->assignTemplate();
        
    }
    
    /**
     * 用户信息初始化
     * @access private
     * @return void
     */
    private function initUser()
    {        
        //用户第一次进入网站，统计用户的来源，记录是否为广告投放进入站点，并记录广告的id
        if (!Session::has('user_id')) {           
            //获取投放站点的名称和广告的id
            $site_name = input('?get.from') ? htmlspecialchars(input('get.from')) : '本站';
            $from_ad = input('?get.ad_id') ? input('ad_id/d') : 0;

            Session::set('from_id', $from_ad);   //用户点击的广告ID
            Session::set('referer', $site_name); //用户来源

            unset($site_name);
            
            //统计访问信息
            visit_stats();
        }
        
        //检查Session回话信息中是否有用户信息
        if (empty(Session::get('user_id'))) {
            //TODO:增加请求中存在cookie的情况（用户登录）            
            Session::set('user_id', 0);
            Session::set('user_name', '');
            Session::set('email', '');
            Session::set('user_rank', 0);
            Session::set('discount', 1.00);

            if (!Session::has('login_fail')) {
                Session::set('login_fail', 0);
            }
        }
        //session 不存在，检查cookie 
        if (Cookie::has('user_id') && Cookie::has('password')) {
            $map = array(
                'user_id' => Cookie::get('user_id'),
                'password' => Cookie::get('password')
            );
            $res = Db::table('tp_users')->where($map)->select();
            
            if ($res) {
                Cookie::delete('user_id');
                Cookie::delete('password');
            } else {
                Session::set('user_id',$map['user_id']);
                Session::set('password',$map['password']);
                update_user_info();
            }   
        }             
    }
    
    /**
     * 公共变量的模板赋值
     * @access private
     * @return void
     */
    protected function assignTemplate()
    {       
        //网站配置
        $this->assign('site', Config::get('site'));
        //面包屑导航与页面标题
        $this->assign('ur_here', $this->assignNav()); 
        //商品分类树信息
        $category = new \app\index\model\Category();
        $this->assign('cate_tree', $category->getCategoriesTree());
        //品牌列表信息
        $brand_list = Db::table('tp_brand')
            ->cache(true, WEIDO_CACHE_TIME)
            ->field('brand_id, brand_name, brand_logo')
            ->where("is_show = 1")
            ->select();
        $this->assign('brand_list', $brand_list);
    }
     
     /**
      * 取得当前位置和页面标题
      * @access  public
      * @param   null
      * @return  array
      */
    public function assignNav()
    {
        $request = Request::instance();
        $navigate = include APP_PATH . 'index/navigate/navigate.php';    
        $location = strtolower('Index/' . $request->controller());
        $arr = array(
            '首页'=>'/',
            $navigate[$location]['name'] => Url('/Index/' . $request->controller()),
            $navigate[$location]['action'][$request->action()] => 'javascript:void();',
        );
        return $arr;                                        
     } 
}
