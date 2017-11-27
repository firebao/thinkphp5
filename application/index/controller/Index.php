<?php
// +----------------------------------------------------------------------
// | WeiDo Index控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;


use think\Config;
use \app\index\model\Category;
use app\index\model\Area;

class Index extends Bace
{
    
    public function Index()
    {
       
        //模板变量赋值        
        $this->assign('flash_theme',     Config::get('site.flash_theme'));  // Flash轮播图片模板    
        $this->assign('feed_url',        (Config::get('site.rewrite') == 1) ? 'feed.xml' : 'feed.php'); //RSS URL
    
        $category = new Category();

        $this->assign('categories',      $category->get_categories_tree()); // 分类树
//        $this->assign('helps',           get_shop_help());       // 网店帮助
//         $this->assign('top_goods',       get_top10());           // 销售排行
    
//         $this->assign('best_goods',      get_recommend_goods('best'));    // 推荐商品
//         $this->assign('new_goods',       get_recommend_goods('new'));     // 最新商品
//         $this->assign('hot_goods',       get_recommend_goods('hot'));     // 热点文章
//         $this->assign('promotion_goods', get_promote_goods());            // 特价商品
//         $this->assign('brand_list',      get_brands());
//         $this->assign('promotion_info',  get_promotion_info());  // 增加一个动态显示所有促销信息的标签栏
    
//         $this->assign('invoice_list',    index_get_invoice_query());  // 发货查询
//         $this->assign('new_articles',    index_get_new_articles());   // 最新文章
//         $this->assign('group_buy_goods', index_get_group_buy());      // 团购商品
//         $this->assign('auction_list',    index_get_auction());        // 拍卖活动
//         $this->assign('shop_notice',     $_CFG['shop_notice']);       // 商店公告
        
        return $this->fetch();
    }
    /**
     * 切换城市
     *
     * @access public
     * @return void
     */
    public function changeCity()
    {
        $area = new Area();
        $areaId2 = $area->getDefaultCity();
        $provinceList = $area->getProvinceList();
        $cityList = $area->getCityGroupByKey();
        $areas = $area->getArea($areaId2);

    	$this->assign('provinceList', $provinceList);
    	$this->assign('cityList', $cityList);
    	$this->assign('area', $areas);
    	$this->assign('areaId2',$areaId2);
    	return $this->fetch();
    }
    /**
     * 修改切换城市ID
     * 
     * @access public
     * @return 
     */
    public function reChangeCity()
    {
        
        $this->getDefaultCity();
        
    }
    /**
     * 定位所在城市
     * 
     * @access public
     * @return array
     */
    public function getDefaultCity()
    {
        
        $areas= new Area();
        return $areas->getDefaultCity();
        
    }
    
}