<?php
namespace app\admin\controller;
use app\admin\model\Admin as Mod;
class Admin extends Common{
    public function admin_add(){
        $obj=new Mod;
        return view("admin/admin_add");
    }
    public function add_do(){
        $all=input();
        $obj= new Mod;
        $admin_account=$all['admin_account'];
        $find1=$obj->where("admin_account='$admin_account'")->find();
        if(empty($admin_account)){
            $this->error('管理员账号不能为空',url('admin/admin_add'));
            die;
        }else if(!empty($find1)){
            $this->error('该账号已被注册',url('admin/admin_add'));
            die;
        }
        $file=request()->file('admin_img');
        $info=$file->move('./img');
        if(!empty($info)){
            $this->error('上传文件失败',url('admin/admin_upd',['admin_id'=>$admin_id]));
        }
        $all['admin_img']=$info->getSaveName();
       
        $res=$obj->save($all);
        // dump($res);
        if($res=true){
            $this->success('ok',url('admin/admin_list'));
        }else{
            $this->error('no',url('admin/admin_add'));
        }
    }
    public function admin_list(){
        $uname=empty(input('uname'))?'':input('uname');
        $where=[];
        if(!empty($uname)){
            $where[]=['admin_account','like',"%$uname%"];
        }
        $obj=new Mod();
        $res=$obj->where($where)->paginate(2,false,['query'=>input()]);
        $page=$res->render();
        // dump($res);die;
        return view("admin/admin_list",['data'=>$res,'page'=>$page]);
    }
    public function admin_upd(){
        $admin_id=input('admin_id');
        $obj=new Mod;
        $where=[
            ['admin_id',"=","$admin_id"]
        ];
        $res=$obj->where($where)->find();
        return view("admin/admin_upd",['data'=>$res]);
    }
    public function upd_do(){
        $admin_id=input('admin_id');
        $obj=new Mod;
        $all=input();
        $admin_account=$all['admin_account'];
        $find1=$obj->where("admin_account='$admin_account'")->find();
        if(empty($admin_account)){
            $this->error('管理员账号不能为空',url('admin/admin_upd'));
            die;
        }else if(!empty($find1)){
            $this->error('该账号已被注册',url('admin/admin_upd'));
            die;
        }
        if($_FILES['admin_img']['error']!=4){
            $file=request()->file('admin_img');
            $info=$file->move('./img');
            // dump($info);die;
            // if(!empty($info)){
            //     $this->error('上传文件失败',url('admin/admin_upd',['admin_id'=>$admin_id]));
            // }
            $all['admin_img']=$info->getSaveName();
        }
        
        $where=[
            ['admin_id',"=","$admin_id"]
        ];
        $res=$obj->where($where)->update($all);
        if($res===1){
            $this->success('修改成功',url('admin/admin_list'));
        }else if($res===0){
            $this->success('未作修改',url('admin/admin_list'));
        }else{
            $this->error('修改失败',url('admin/admin_upd'));
        }
    }
    public  function admin_del(){
        $admin_id=input('admin_id');
        $where=[
            ['admin_id',"=","$admin_id"]
        ];
        $obj=new Mod;
        $res=$obj->where($where)->delete();
        if($res==1){
            $this->success('ok',url('admin/admin_list'));
        }else{
            $this->error('no',url('admin/admin_list'));
        }
    }
    public function checkvalue(){
        $value=input('value');
        $field=input('field');
        $admin_id=input('admin_id');
        // dump($value);
        // dump($field);
        // dump($admin_id);
        $obj=new Mod;
        $res=$obj->where("admin_id=$admin_id")->update([$field=>$value]);
        // dump($res);die;
        if($res===false){
            echo 'no';
        }else{
            echo 'ok';
        }
    }

}


?>