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
use think\Session;

class AdminUser extends Model
{
    protected $table = "tp_admin_user";
    /**
     * 登录指定用户
     * @param string  $email      管理员登录邮箱
     * @param string  $password   管理员登录密码
     * @return boolean ture-登录成功，false-登录失败
     */
    public function adminLogin($email, $password)
    {
        //根据email取得管理员记录
        $map['email'] = $email;
        $admin = $this->get($map);
        //dump(md5(md5('dabao5235hu7').'6748'));
        //检查密码是否正确
        if (!empty($admin->tp_salt)) {
            if ($admin->password !== md5(md5($password) . $admin->tp_salt)) return false;
        } else {
            if ($admin->password !== md5(md5($password) . "+" )) return false;
        }
        
        //登录成功，写session
        Session::set('admin_id', $admin->user_id);
        Session::set('admin_name', $admin->user_name);
        
        //更新随机数，最后登录时间，ip
        if (empty($admin->tp_salt)) {
            $tp_salt = rand(1, 9999);
            $new_password = md5(md5($password) . $tp_salt);
            $admin->tp_salt = $tp_salt;
            $admin->password = $new_password;
        }
        $admin->last_login = time();
        $admin->last_ip = request()->ip();
        $admin->save();
        
        return true;
    }
}