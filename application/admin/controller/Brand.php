<?php
namespace app\admin\controller;
use app\admin\model\Brand as Mod;
class Brand extends Common{
    //添加展示的方法
    public function brand_add(){
        return view("brand/brand_add");
    }
    //添加的方法
    public function add_do(){
        $obj=new Mod;
        $all=input();
        $brand_name=$all['brand_name'];     
        $find1=$obj->where("brand_name='$brand_name'")->find();       
        if(empty($brand_name)){
            $this->error('品牌名称不能为空',url('brand/brand_add'));
            die;
        }else if(!empty($find1)){
            $this->error('该品牌已被注册',url('brand/brand_add'));
            die;
        }else if(strlen($brand_name)<5){
            $this->error('品牌名称不能小于五位',url('brand/brand_add'));
            die;
        }
        // }else if(!preg_match($regs,$brand_name)){
        //     $this->error('品牌名称格式不对',url('brand/brand_add'));
        //     die;
        // }
        $brand_url=$all['brand_url'];
        $find2=$obj->where("brand_url='$brand_url'")->find();  //验证唯一性公式
        $reg="/^www\.[a-zA-Z0-9]{3,}\.com$/";   //正则公式
        if(empty($brand_url)){
            $this->error('品牌网址不能为空',url('brand/brand_add'));
            die;
        }else if(!empty($find2)){
            $this->error('该品牌网址已存在',url('brand/brand_add'));
            die;
        }else if(!preg_match($reg,$brand_url)){
            $this->error('网址格式不对',url('brand/brand_add'));
            die;
        }
        $file=request()->file('brand_logo');
        $info=$file->move('./img');
        $all['brand_logo']=$info->getSaveName();
        $all['brand_time']=date("Y-m-d H:i:s",time());
       
        $res=$obj->save($all);
        // dump($res);
        if($res==true){
            $this->success('ok',url('brand/brand_list'));
        }else{
            $this->error('no',url('brand/brand_add'));
        }
    }
    //列表展示的方法
    public function brand_list(){
        $uname=empty(input('uname'))?'':input('uname');
        $url=empty(input('url'))?'':input('url');
        $where=[];
        if(!empty($uname)){
            $where[]=['brand_name','like',"%$uname%"];
        }
        if(!empty($url)){
            $where[]=['brand_url','like',"%$url%"];
        }
        $obj=new Mod;
        $res=$obj->where($where)->paginate(2,false,['query'=>input()]);
        $page=$res->render();
        return view("brand/brand_list",['data'=>$res,'page'=>$page]);
    }
    //删除的方法
    public function brand_del(){
        $brand_id=input('brand_id');
        $obj = new Mod();
        $res=$obj->where("brand_id=$brand_id")->delete();
        if($res==1){
            $this->success('ok',url('brand/brand_list'));
        }else{
            $this->error('no',url('brand/brand_list'));
        }
    }
    //修改展示的方法
    public function brand_upd(){
        $brand_id=input('brand_id');
        $obj = new Mod();
        $where=[
            ["brand_id","=","$brand_id"]
        ];
        $res=$obj->where($where)->find();
        return view("brand/brand_upd",['data'=>$res]);
    }
    //修改的方法
    public function upd_do(){
        $brand_id=input('brand_id');        
        $all=input();
        $obj=new Mod;
        $brand_name=$all['brand_name'];     
        $find1=$obj->where("brand_name='$brand_name'")->find();       
        if(empty($brand_name)){
            $this->error('品牌名称不能为空',url('brand/brand_upd',['brand_id'=>$brand_id]));
            die;
        }else if(!empty($find1)){
            $this->error('该品牌已被注册',url('brand/brand_upd',['brand_id'=>$brand_id]));
            die;
        }else if(strlen($brand_name)<5){
            $this->error('品牌名称不能小于五位',url('brand/brand_upd',['brand_id'=>$brand_id]));
            die;
        }
        $brand_url=$all['brand_url'];
        $find2=$obj->where("brand_url='$brand_url'")->find();  //验证唯一性公式
        $reg="/^www\.[a-zA-Z0-9]{3,}\.com$/";   //正则公式
        if(empty($brand_url)){
            $this->error('品牌网址不能为空',url('brand/brand_upd',['brand_id'=>$brand_id]));
            die;
        }else if(!empty($find2)){
            $this->error('该品牌网址已存在',url('brand/brand_upd',['brand_id'=>$brand_id]));
            die;
        }else if(!preg_match($reg,$brand_url)){
            $this->error('网址格式不对',url('brand/brand_upd',['brand_id'=>$brand_id]));
            die;
        }
        
        if($_FILES['brand_logo']['error']!=4){
            $file=request()->file('brand_logo');
            $info=$file->move('./img');
            $all['brand_logo']=$info->getSaveName();    
        }
        
        $all['brand_time']=date("Y-m-d H:i:s",time());
       
        $res=$obj->where("brand_id=$brand_id")->update($all);
        // dump($res);
        if($res==1){
            $this->success('ok',url('brand/brand_list'));
        }else{
            $this->error('no',url('brand/brand_add'));
        }
    }
    public function checkvalue(){
        $value=input('value');
        // dump($value);die;
        $brand_name=input('brand_name');
        $brand_id=input('brand_id');
        $obj = new Mod;
        $res=$obj->where("brand_id=$brand_id")->update([$brand_name=>$value]);
        // dump($res);
        if($res==1){
            echo 'ok';
        }else{
            echo 'no';
        }

    }
}



?>