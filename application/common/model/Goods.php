<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use think\Request;
use think\Db;

class Goods extends Model
{
    protected $resultSetType = 'collection';
    /**
     * 获得商品列表
     * @access  public
     * @params  integer $isdelete
     * @params  integer $real_goods
     * @params  integer $conditions
     * @return  array
     */
    public function GoodsList($is_delete, $real_goods=1, $conditions = '')
    {
        //获取上次的过滤条件(在Cookie中)
        $param_str = '-' . $is_delete . '-' . $real_goods;
        $result = get_filter($param_str);
        //如果不存在上次过滤条件，重新设置
        if ($result === false) {
            
            $day = getdate();
            $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
            
            //处理extra_search数据
            $request = Request::instance();
            parse_str($request->param('extra_search/s'), $extra_search);
            $filter['cat_id']           = isset($extra_search['cat_id']) ? $request->param('cat_id/d') : 0;
            $filter['intro_type']       = isset($extra_search['intro_type']) ? $request->param('intro_type/s') : '';
            $filter['is_promote']       = isset($extra_search['is_promote']) ? $request->param('is_promote/d') : 0;
            $filter['stock_warning']    = isset($extra_search['stock_warning']) ? $request->param('stock_warning/s') : 0;
            $filter['brand_id']         = isset($extra_search['brand_id']) ? $request->param('brand_id/d') : 0;
            $filter['keyword']          = isset($extra_search['keyword']) ? $request->param('keyword/s') : '';
            $filter['suppliers_id']     = isset($extra_search['suppliers_id']) ? $request->param('suppliers_id/s') : '';
            $filter['is_on_sale']       = isset($extra_search['is_on_sale']) ? $request->param('suppliers_id/d') : '';
            $filter['sort_by']          = $request->has('sort_by') ? $request->param('sort_by/s') : 'goods_id';
            $filter['sort_order']       = $request->has('sort_order') ? $request->param('sort_order/s') : 'DESC';
            $filter['extension_code']   = $request->has('extension_code') ? $request->param('sort_order/s') : '';
            $filter['is_delete']        = $is_delete;
            $filter['real_goods']       = $real_goods;
            //获取cate_id下的分类
            $category = new Category();
            $cate_id = array_keys($category->CateList($filter['cat_id'], 0, false));
            //cate_id查询数组
            $map['cat_id'] = ['IN', $cate_id];
            $map_or = array();
            //推荐类型intro_type查询数组
            switch ($filter['intro_type']) {
                case 'is_best':
                    //$where .= " AND is_best=1";
                    $map['is_best'] = 1;
                    break;
                case 'is_hot':
                    //$where .= ' AND is_hot=1';
                    $map['is_hot'] = 1;
                    break;
                case 'is_new':
                    //$where .= ' AND is_new=1';
                    $map['is_new'] = 1;
                    break;
                case 'is_promote':
                    //$where .= " AND is_promote = 1 AND promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today'";
                    $map['is_promote'] = 1;
                    $map['promote'] = ['>', 0];
                    $map['promote_start_date'] = ['<=', $today];
                    $map['promote_end_date'] =['>=', $today];
                    break;
                case 'all_type';
                    $map['is_best'] = 1;
                    $map['is_hot'] = 1;
                    $map['is_new'] = 1;
                    $map['is_promote'] = 1;
                    $map['promote'] = ['>', 0];
                    $map['promote_start_date'] = ['<=', $today];
                    $map['promote_end_date'] =['>=', $today];
                //$where .= " AND (is_best=1 OR is_hot=1 OR is_new=1 OR (is_promote = 1 AND promote_price > 0 AND promote_start_date <= '" . $today . "' AND promote_end_date >= '" . $today . "'))";
            }
        
            //库存警告
            if ($filter['stock_warning']) {
                //$where .= ' AND goods_number <= warn_number ';
                $map['goods_number'] = ['<=', 'warn_number'];
            }
            //品牌
            if ($filter['brand_id']) {
                //$where .= " AND brand_id='$filter[brand_id]'";
                $map['brand_id'] = $filter['brand_id'];
            }
            //扩展
            if ($filter['extension_code']) {
                //$where .= " AND extension_code='$filter[extension_code]'";
                $map['extension_code'] = $filter['extension_code'];
            }        
            //关键字
            if (!empty($filter['keyword'])) {
                //$where .= " AND (goods_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%' OR goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
                $map['goods_sn'] = ['like', $filter['keyword']];
                $map_or['goods_name'] = ['like', $filter['keyword']];
            }
        
            if ($real_goods > -1) {
                //$where .= " AND is_real='$real_goods'";
                $map['is_real'] = $real_goods;
            }
            //上架
            if ($filter['is_on_sale'] !== '') {
                //$where .= " AND (is_on_sale = '" . $filter['is_on_sale'] . "')";
                $map['is_on_sale'] = $filter['is_on_sale'];
            }        
            //供货商
            if (!empty($filter['suppliers_id'])) {
                //$where .= " AND (suppliers_id = '" . $filter['suppliers_id'] . "')";
                $map['suppliers_id'] = $filter['suppliers_id'];
            }
            
            $map['is_delete'] = $is_delete;
            //记录总数
            $filter['record_count'] = Db::table('tp_goods')->where($map)->whereOr($map_or)->field('COUNT(goods_id)')->select();
        
            /* 分页大小 */
            //$filter = page_and_size($filter);
            //查询记录
            $field = ['goods_id', 'goods_name', 'goods_type', 'goods_sn', 'virtual_sales', 'shop_price', 'is_on_sale', 'is_best', 'is_new', 'is_hot', 'sort_order', 'goods_number', 'integral'];
            $res = Db::table('tp_goods')->where($map)->whereOr($map_or)->field($field)->order($filter['sort_by'],$filter['sort_order'])->paginate(20);
//             $sql = "SELECT goods_id, goods_name, goods_type, goods_sn, virtual_sales, shop_price, is_on_sale, is_best, is_new, is_hot, sort_order, goods_number, integral, " .
//                 " (promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today') AS is_promote ".
//                 " FROM " . $GLOBALS['ecs']->table('goods') . " AS g WHERE is_delete='$is_delete' $where" .
//                 " ORDER BY $filter[sort_by] $filter[sort_order] ".
//                 " LIMIT " . $filter['start'] . ",$filter[page_size]";
        
//             $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $map, $param_str);
        } else {
            $map = $result['map'];
            $filter = $result['filter'];
        }
        $res = Db::table('tp_goods')->where($map)->whereOr($map_or)->field($field)->order($filter['sort_by'],$filter['sort_order'])->paginate(20);
        
        return array('goods' => $res, 'filter' => $filter, 'record_count' => $filter['record_count']);
    }
    /**
     * 获取商品列表
     * @access public
     * @param array $map|default:空数组   where查询条件
     * @param array $map_or|default:空数组   whereOr查询条件
     * @param array $field|default:空数组  field查询条件
     * @param array $order|default:空数组  order排序条件
     * @param integer $start|default：0 分页起始记录
     * @param integer $start|default：20 分页记录数
     * @return object  think\Goods对象
     */
    public function GoodsList1($map = array(), $map_or = array(), $field = array(), $order = array(), $start = 0, $length = 20)
    {
        //$res = Db::table('tp_goods')->where($map)->whereOr($map_or)->field($field)->order($order)->limit($start, $length)->select();
        $res = $this->where($map)->whereOr($map_or)->order($order)->field($field)->limit($start, $length)->select();
        return $res;
    }
}
