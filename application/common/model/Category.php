<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;
use think\Db;

class Category extends Model
{
    /**
     * 获得指定分类下的子分类的数组
     * @access public
     * @param   int     $cat_id     分类的ID
     * @param   int     $selected   当前选中分类的ID
     * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param   int     $level      限定返回的级数。为0时返回所有级数
     * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
     * @return mixed
     */
    public static function CateList($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true)
    {
        $res1 = cache('cate_list');
        
        if ($res1 === FALSE) {
            //TODO:是否可以用DB::view()视图查询或者Model::hasone()关联的方式重写方法
            //查询category数据表全部记录以及记录含有子分类的个数        
            $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.is_show, c.show_in_nav, c.grade, c.sort_order, COUNT(s.cat_id) AS has_children ".
                'FROM ' . 'tp_category' . " AS c ".
                "LEFT JOIN " . 'tp_category' . " AS s ON s.parent_id=c.cat_id ".
                "GROUP BY c.cat_id ".
                'ORDER BY c.parent_id, c.sort_order ASC';
            $res1 = Db::query($sql);
            //查询goods数据表每个分类下的商品个数
            $sql = "SELECT cat_id, COUNT(*) AS goods_num " .
                " FROM " . 'tp_goods' .
                " WHERE is_delete = 0 AND is_on_sale = 1 " .
                " GROUP BY cat_id";
            $res2 = DB::query($sql);
            //查询goods_cat数据表每个分类下的商品个数
            $sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " .
                " FROM " . 'tp_goods_cat' . " AS gc , " . 'tp_goods' . " AS g " .
                " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 " .
                " GROUP BY gc.cat_id";
            $res3 = DB::query($sql);
            
            //合并并整理$res2和$res3查询结果
            $newres = array();
            foreach($res2 as $k=>$v) {
                $newres[$v['cat_id']] = $v['goods_num'];
                foreach($res3 as $ks=>$vs) {
                    if($v['cat_id'] == $vs['cat_id']) {
                        $newres[$v['cat_id']] = $v['goods_num'] + $vs['goods_num'];
                    }
                }
            }
            //在$res1中添加同类商品的数量
            foreach($res1 as $k=>$v) {
                $res1[$k]['goods_num'] = !empty($newres[$v['cat_id']]) ? $newres[$v['cat_id']] : 0;
            }
            cache('category_list', $res1);
        }
        
        //如果结果为空 根据返回类型参数返回结果
        if (empty($res1) == true) {
            return $re_type ? '' : array();
        }
        $options = self::cat_options($cat_id, $res1);

        
        $children_level = 99999; //大于这个分类的将被删除
        
        //隐藏不可见分类
        if ($is_show_all == false) {
            foreach ($options as $key => $val) {
                //分类层级大于99999，删除分类
                if ($val['level'] > $children_level) {
                    unset($options[$key]);
                } else {
                    //分类隐藏标志为0，删除分类
                    if ($val['is_show'] == 0) {
                        unset($options[$key]);
                        //标记一下，这样子分类也能删除
                        if ($children_level > $val['level']) $children_level = $val['level']; 
                    } else {
                        //恢复初始值
                        $children_level = 99999; 
                    }
                }
            }
        }
        
        //截取到指定的缩减级别
        if ($level > 0) {
            
            //$level表示限定返回的级数，为0表示返回所有级数
            if ($cat_id == 0) {
                $end_level = $level;
            } else {
                $first_item = reset($options); // 获取第一个元素
                $end_level  = $first_item['level'] + $level;
            }
        
            //保留level小于end_level的部分
            foreach ($options AS $key => $val) {
                if ($val['level'] >= $end_level) {
                    unset($options[$key]);
                }
            }
        }
        
        //$re_type表示返回类型，为true是返回下拉列表，为false时返回数组
        if ($re_type == true) {
            $select = '';
            foreach ($options AS $var) {
                $select .= '<option value="' . $var['cat_id'] . '" ';
                $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
                $select .= '>';
                //根据层级对下拉列表进行缩进
                if ($var['level'] > 0){
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
            }
            return $select;
        } else {
            foreach ($options AS $key => $value) {
                $options[$key]['url'] = url('category', array('cid' => $value['cat_id']), $value['cat_name']);
            }       
            return $options;
        }
    }
    /**
     * 过滤和排序所有分类，返回一个带有缩进级别的数组
     * @access  private
     * @param   int     $cat_id     上级分类ID
     * @param   array   $arr        含有所有分类的数组
     * @return  void
     */
    private static function cat_options($spec_cat_id, $arr)
    {
        static $cat_options = array();
    
        if (isset($cat_options[$spec_cat_id])) {
            return $cat_options[$spec_cat_id];
        }
    
        if (!isset($cat_options[0])) {
            
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = array();
            $data = cache('cat_option_static');
            
            if ($data === false) {
                while (!empty($arr)) {
                    foreach ($arr AS $key => $value) {
                        $cat_id = $value['cat_id'];
                        if ($level == 0 && $last_cat_id == 0) {
                            if ($value['parent_id'] > 0) {
                                break;
                            }
    
                            $options[$cat_id]          = $value;
                            $options[$cat_id]['level'] = $level;
                            $options[$cat_id]['id']    = $cat_id;
                            $options[$cat_id]['name']  = $value['cat_name'];
                            unset($arr[$key]);
    
                            if ($value['has_children'] == 0) {
                                continue;
                            }
                            
                            $last_cat_id  = $cat_id;
                            $cat_id_array = array($cat_id);
                            $level_array[$last_cat_id] = ++$level;
                            continue;
                        }
    
                        if ($value['parent_id'] == $last_cat_id) {
                            
                            $options[$cat_id]          = $value;
                            $options[$cat_id]['level'] = $level;
                            $options[$cat_id]['id']    = $cat_id;
                            $options[$cat_id]['name']  = $value['cat_name'];
                            unset($arr[$key]);
    
                            if ($value['has_children'] > 0) {
                                if (end($cat_id_array) != $last_cat_id) {
                                    $cat_id_array[] = $last_cat_id;
                                }
                                $last_cat_id    = $cat_id;
                                $cat_id_array[] = $cat_id;
                                $level_array[$last_cat_id] = ++$level;
                            }
                        } elseif ($value['parent_id'] > $last_cat_id) {
                            break;
                        }
                    }
    
                    $count = count($cat_id_array);
                    if ($count > 1) {
                        $last_cat_id = array_pop($cat_id_array);
                    } elseif ($count == 1) {
                        if ($last_cat_id != end($cat_id_array)) {
                            $last_cat_id = end($cat_id_array);
                        } else {
                            $level = 0;
                            $last_cat_id = 0;
                            $cat_id_array = array();
                            continue;
                        }
                    }
    
                    if ($last_cat_id && isset($level_array[$last_cat_id])) {
                        $level = $level_array[$last_cat_id];
                    } else {
                        $level = 0;
                    }
                }
                //写缓存
                cache('cat_option_static', $options);
                
            } else {
                $options = $data;
            }
            
            $cat_options[0] = $options;
            
        } else {
            $options = $cat_options[0];
        }
    
        if (!$spec_cat_id) {
            return $options;
        } else {
            if (empty($options[$spec_cat_id])) {
                return array();
            }
    
            $spec_cat_id_level = $options[$spec_cat_id]['level'];
    
            foreach ($options AS $key => $value) {
                if ($key != $spec_cat_id) {
                    unset($options[$key]);
                } else {
                    break;
                }
            }
    
            $spec_cat_id_array = array();
            foreach ($options AS $key => $value) {
                if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level'])) {
                    break;
                } else {
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;
    
            return $spec_cat_id_array;
        }
    }
}