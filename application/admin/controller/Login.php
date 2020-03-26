<?php
namespace app\admin\controller;
use \think\Controller;
use app\admin\model\Admin;
class Login extends Controller{
    public function login(){
        $this->view->engine->layout(false);
        return view("admin/login");
    }
    public function login_do(){
        $admin_account=input('admin_account');
        $admin_pwd=input('admin_pwd');
        $obj=new Admin;
        $res=$obj->where("admin_account='$admin_account' and  admin_pwd='$admin_pwd'")->find();
        // dump($res);
        if(!empty($res)){
            session("uname",["admin_id"=>$res['admin_id'],"admin_account"=>$res['admin_account']]);
            cookie("remember",["admin_account"=>$admin_account,"admin_pwd"=>$admin_pwd],60*60*24*7);
            $this->success('ok',url('index/index'));
        }else{
            $this->error('no',url('login/login'));
        }
    }
    // public function test(){
    //     $arr=cookie("remember");
    //     dump($arr);
    // }
    public function logout(){
        session("uname",null);
        cookie("remember",null);
        $this->success("退出成功",url('login/login'));
    }
}


?>