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
// | @Desp: User Validate模块
// +----------------------------------------------------------------------
namespace app\common\validate;

use think\Validate;
use think\Db;

class User extends Validate
{
    protected $rule = [
        'username'          => 'require|length:4,20|isRegister|alphaDash',
        'phone'             => ['require', 'regex'=> '/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/', 'phoneIsResgister'],
        'password'          => 'require|length:8,16',
        'confirm_password'  => 'require|confirm:password'
    ];
    protected $message  = [
        'username.require'      => '用户名必须',
        'username.length'       => '用户名长度在4-20位之间',
        'username.isRegister'   => '用户名已注册',
        'username.alphaDash'    => '仅支持字母、数字、"_"、"-"的组合',
        'phone.require'         => '手机号码必须',
        'phone.regex'           => '手机号码格式不正确',
        'phone.phoneIsRegister' => '手机号码已被注册',
        'password.require'      => '密码必须',
        'password.length'       => '密码长度在8-16位之间',
        'confirm_password.require'  => '密码确认必须',
        'confirm_password.confirm'  => '密码确认需要与密码一致'
    ];
    protected function isRegister($value, $rule, $data)
    {        
        $result = Db::table('tp_users')->where('user_name', $value)->find();
        if (!$result) {
            return true;
        } else {
            return false;
        }            
    }
    protected function phoneIsResgister($value, $rule, $data)
    {
        $result = Db::table('tp_users')->where('mobile_phone', $value)->find();
        if (!$result) {
            return true;
        } else {
            return false;
        }
    }
}
