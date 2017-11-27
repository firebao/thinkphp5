<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Request;
use app\admin\model\Category;
use app\admin\model\Brand;
use app\admin\model\Goods as GoodsModel;
use think\Db;

class Goods extends Admin
{
    /**
     * 添加商品界面
     * @access public
     * @return
     */
    public function GoodsAdd()
    {
        return $this->fetch();
    }
    /**
     * 商品列表界面
     * @access public
     * @param Request
     * @return
     */
    public function GoodsList(Request $request)
    {
        //TODO:判断管理员是否有进行此操作的权限
        if ($request->isAjax()) {
            
            //获取Datatables发送的参数，这个值作者会直接返回给前台
            $draw = $request->param('draw');
            
            //解析extra_search数据，获取并初始化extra_search查询数据
            $extra_search = array();
            parse_str($request->param('extra_search/s'), $extra_search);
            $cate_id = isset($extra_search['cat_id']) ? intval($extra_search['cat_id']) : 0;
            $brand_id = isset($extra_search['brand_id']) ? intval($extra_search['brand_id']) : 0;
            $suppliers_id = isset($extra_search['suppliers_id']) ? intval($extra_search['suppliers_id']) : 0;
            $intro_type = isset($extra_search['intro_type']) ? trim($extra_search['intro_type']) : 0;
            $is_on_sale = isset($extra_search['is_on_sale']) ? trim($extra_search['is_on_sale']) : '';
            $keyword = isset($extra_search['keyword']) ? trim($extra_search['keyword']) : '';
            
            //构建数据库where,whereOr查询条件
            $map = array();
            $map_or = array();
            //cate_id查询条件
            $category = new Category();
            $cate_id_list = array_keys($category->CateList($cate_id, 0, false));
            $map['cat_id'] = ['IN', $cate_id_list];
            //brand_id查询条件
            if ($brand_id) {
                $map['brand_id'] = $brand_id;
            }
            //suppliers_id查询条件
            if ($suppliers_id) {
                $map['suppliers_id'] = $suppliers_id;
            }
            //推荐类型intro_type查询数组
            switch ($intro_type) {
                case 'is_best':
                    $map['is_best'] = 1;
                    break;
                case 'is_hot':
                    $map['is_hot'] = 1;
                    break;
                case 'is_new':
                    $map['is_new'] = 1;
                    break;
                case 'is_promote':
                    $day = getdate();
                    $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
                    $map['is_promote'] = 1;
                    $map['promote'] = ['>', 0];
                    $map['promote_start_date'] = ['<=', $today];
                    $map['promote_end_date'] =['>=', $today];
                    break;
                case 'all_type';                 
            }
            //上下架is_on_sale查询条件
            if ($is_on_sale) {
                $map['is_on_sale'] = $is_on_sale;
            }
            //keyword查询条件
            if ($keyword) {
                $map['goods_sn'] = ['like', $keyword];
                $map_or['goods_name'] = ['like', $keyword];
            }

            //构建field查询条件
            $field = ['goods_id', 
                    'goods_name', 
                    'goods_sn', 
                    'shop_price', 
                    'is_on_sale', 
                    'is_best', 
                    'is_new', 
                    'is_hot', 
                    'sort_order', 
                    'goods_number',
                    'virtual_sales',
                    'integral'];
            
            //构建order排序条件
            $order = array();
            $order_column = $_POST['order']['0']['column'];//那一列排序，从0开始
            $order_dir = $_POST['order']['0']['dir'];//ase desc 升序或者降序
            if(isset($order_column)){
                $i = intval($order_column);
                switch($i){
                    case 0:
                        $order['goods_id'] = $order_dir;
                        break;
                    case 1:
                        $order['goods_name'] = $order_dir;
                        break;
                    case 2:
                        $order['goods_sn'] = $order_dir;
                        break;
                    case 3:
                        $order['shop_price'] = $order_dir;
                        break;
                    case 8:
                        $order['sort_order'] = $order_dir;
                        break;
                    case 9:
                        $order['goods_number'] = $order_dir;
                        break;
                    case 10:
                        $order['virtual_sales'] = $order_dir;
                        break;
                    default;
                }
            }
            
            //分页
            $start = $_POST['start'];//从多少开始
            $length = $_POST['length'];//数据长度
            
            //查询模型数据
            $goods = new GoodsModel();
            $infos = $goods->GoodsList1($map, $map_or, $field, $order, $start, $length)->toArray();

            //数据表的总记录数
            $records_total = $goods->count();
            
            //符合查询条件的记录数
            $records_filtered = $goods->where($map)->whereOr($map_or)->count();
            //返回dataTable需要的数据
            return array("draw" => intval($draw),
                               "recordsTotal" => intval($records_total),
                               "recordsFiltered" => intval($records_filtered),
                               "data" => $infos
                               );
        }
        //获取url中的参数
        //$cate_id = $request->has('cate_id', 'param', true) ? $request->param('cate_id/d') : 0;
        //$code = $request->has('extension_code', 'param', true) ? $request->param('extension_code/s') : '';
        $suppliers_id = $request->has('suppliers_id', 'param', true) ? $request->param('suppliers_id/s') : '';
        $is_on_sale = ($request->has('is_on_sale', 'param', true) && ($request->param('is_on_sale') !== 0)) ? $request->param('is_on_sale') : ''; 
        
        //获取供货商列表信息
        $suppliers_list_name = Db::table('tp_suppliers')->where('is_check', 1)->field('suppliers_id,suppliers_name')->select();
        $suppliers_exists = empty($suppliers_list_name) ? 0 : 1;
        //模板赋值
        $this->assign('is_on_sale', $is_on_sale);
        $this->assign('suppliers_id', $suppliers_id);
        $this->assign('suppliers_exists', $suppliers_exists);
        $this->assign('suppliers_list_name', $suppliers_list_name);
        
        $this->assign('ur_here','商品列表');
        $this->assign('action_link',url('Goods/GoodsAdd'));
        $category = new Category();
        $brand = new Brand();
        $this->assign('cat_list',     $category->CateList()); //分类
        $this->assign('brand_list',   $brand->BrandList());   //品牌
        $this->assign('list_type',    $request->param('act/s') == 'list' ? 'goods' : 'trash');
        $this->assign('use_storage',  empty(config('use_storage')) ? 0 : 1);
        
        $suppliers_list = Db::table('tp_suppliers')->where('is_check', 1)->select();
        $suppliers_list_count = count($suppliers_list);
        $this->assign('suppliers_list', ($suppliers_list_count == 0 ? 0 : $suppliers_list)); // 取供货商列表
        
        $goods = new GoodsModel();

        $goods_list = $goods->GoodsList(0);
        $this->assign('goods_list', $goods_list['goods']);

        $this->assign('filter', $goods_list['filter']);
        $this->assign('record_count', $goods_list['record_count']);
        //dump($goods->GoodsList1());
        if ($request->isAjax()){
//             return json_encode(array("draw" => intval($draw), 
//                 "recordsTotal" => intval($recordsTotal),
//                 "recordsFiltered" => intval($recordsFiltered),
//                 "data" => $infos
//                 ),JSON_UNESCAPED_UNICODE);
        }
        return $this->fetch();
         
    }
}