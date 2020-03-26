<?php
namespace app\admin\controller;
use \think\Controller;
class Common extends Controller{
    public function initialize(){
        // parent::__construct();
        if(!session('?uname')){
            $this->error("请先登录再访问",url('login/login'));
            exit;
        }
    }
}
?>