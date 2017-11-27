<?php
// +----------------------------------------------------------------------
// | WeiDo Category分类模型
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;

class Category extends Model
{
    protected $table = "tp_category";
    
     /**
      * 获得指定分类同级的所有分类以及该分类下的子分类
      * @access  public
      * @param   integer   $cat_id     分类编号
      * @return  array
      */
     public function get_categories_tree($cat_id = 0)
     {
         if ($cat_id > 0) {
             
             //如果指定了分类编号，从数据库查询此分类编号的父级编号
             $parent_id = $this->where('cat_id', $cat_id)->value('parent_id');
             
         } else {
             
             //如果未指定分类编号，设定分类编号的父级编号为0
             $parent_id = 0;
         }
     
         //判断当前分类中全是是否是底级分类，如果是取出底级分类上级分类， 如果不是取当前分类及其下的子分类
         $map['parent_id'] = $parent_id;
         $map['is_show'] = 1;         
         $count = $this->where($map)->count();

         if ($count || $parent_id == 0) {
             
             //获取当前分类及其子分类 
             $res = $this->where($map)->field('cat_id,cat_name,parent_id,is_show')->order('sort_order,cat_id')->select();     
     
             //构造返回数组
             foreach ($res AS $row) {
                 
                 //判断该分类是否显示
                 if ($row['is_show']) {
                     
                     $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
                     $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                     //TODO: 需要完善路由机制
                     $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
                     //获取子分类
                     if (isset($row['cat_id']) != NULL) {                         
                         $cat_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);                         
                     }
                 }
             }
         }
         
         if(isset($cat_arr)) {
             
             return $cat_arr;
         }
     }
     
     /**
      * 获得指定分类下的子分类
      * @access  public
      * @param   integer   $tree_id   分类编号
      * @return  array
      */
     public function get_child_tree($tree_id = 0)
     {
         $three_arr = array();
         $map['parent_id'] = $tree_id;
         $map['is_show'] = 1;
         
         $count = $this->where($map)->count();

         //判断当前分类中全是是否是底级分类，如果是取出底级分类上级分类， 如果不是取当前分类及其下的子分类
         if ($count || $tree_id == 0) {
             
             $res = $this->where($map)->field('cat_id,cat_name,parent_id,is_show')->order('sort_order,cat_id')->select();         

             foreach ($res AS $row) {
                 
                 if ($row['is_show']) {
     
                    $three_arr[$row['cat_id']]['id']   = $row['cat_id'];
                    $three_arr[$row['cat_id']]['name'] = $row['cat_name'];
                    $three_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
     
                     if (isset($row['cat_id']) != NULL) {
                         
                         //递归方法继续获取本分类下的子分类
                         $three_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);
         
                     }
                }
            }
        }
        return $three_arr;
    }
}
