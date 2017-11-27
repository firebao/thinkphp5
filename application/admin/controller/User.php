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
use think\Request;
use app\admin\model\AdminUser;

class User extends Admin
{
    /**
     * 用户登录界面
     * @access public
     * @param Request  $request
     * @return
     */
    public function login(Request $request)
    {
        if ($request->isPost()) {
            
            //判断验证码、登录邮箱、登录密码是否为空
            if (empty($request->post('email')) || 
                empty($request->post('password')) || 
                empty($request->post('verify'))) {
                $this->error('请完善登入信息！');               
            }
            
            //判断验证码是否正确
            if (!captcha_check($request->post('verify'))) {               
                $this->error('验证码错误!');
            }
                        
            $email = trim(input('post.email/s'));
            $password = trim(input('post.password/s'));
            
            //判断用户名与密码是否正确
            $admin = new AdminUser();
            $result = $admin->adminLogin($email, $password);
            
            //登录成功，跳转到主页面
            if ($result) {
                $this->success('登录成功', 'Index/main');
            } else {
                $this->error('密码错误');
            }
        } else {
            
            //显示登录页面
            return $this->fetch();
        }
        
    }
}