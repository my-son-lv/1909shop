<?php
namespace app\admin\controller;
use app\admin\model\Category as Mod;
use app\admin\model\Goods;
class Category extends Common{
    //分类添加展示的方法
    public function cate_add(){
        $obj=new Mod;
        $res=$obj->select();
        $info=getcateinfo($res);
        //渲染数据 显示试视图
        return view("category/cate_add",['fdata'=>$info]);
    }
    //添加的方法
    public function add_do(){
        $all=input();
        $obj=new Mod;
        $cate_name=$all['cate_name'];
        $find=$obj->where("cate_name='$cate_name'")->find();
        if(!empty($find)){
            $this->error('该标题已被添加',url('category/cate_add'));
            die;   
        }else if(empty($cate_name)){
            $this->error('分类名称不能为空',url('category/cate_add'));
            die;
        }
        $res=$obj->save($all);
        // dump($res);
        if($res==1){
            $this->success('ok',url('category/cate_list'));
        }else{
            $this->error('no',url('category/cate_add'));
        }
    }
    //分类列表展示的方法
    public function cate_list(){
        //先把所有分类数据查询出来
        //实例化对象
        $obj=new Mod;
        //toArray()处理数据
        $res=$obj->select();
        //处理分类数据 函数
        //在application下的common.php中增加该函数 --getcateinfo()
        //这样做的好处 减少控制器方法中代码量 减少代码多余
        $info=getcateinfo($res);
        //渲染数据 显示试视图
        // dump($info);
        return view("category/catelist",['data'=>$info]);
        die;
        //下面都是普通增删查改的代码
        // $uname=empty(input('uname'))?'':input('uname');
        // $where=[];
        // if(!empty($uname)){
        //     $where[]=['cate_name','like',"%$uname%"];
        // }
        // $obj= new Mod;
        // $res=$obj->where($where)->paginate(2,false,['query'=>input()]);
        // $page=$res->render();
        // return view("category/cate_list",['data'=>$res,'page'=>$page]);
    }
    //修改展示的方法
    public function cate_upd(){
        $cate_id=input('cate_id');
        $obj=new Mod();
        $res=$obj->where("cate_id=$cate_id")->find();
        $res2=$obj->select();
        $info=getcateinfo($res2);
        return view("category/cate_upd",['data'=>$res,'fdata'=>$info]);
    }
    //修改的方法
    public function upd_do(){
        $cate_id=input('cate_id');
        $all=input();
        $obj=new Mod;
        $res=$obj->where("cate_id=$cate_id")->update($all);
        // dump($res);
        if($res===1){
            $this->success('修改成功',url('category/cate_list'));
        }else if($res===0){
            $this->success('未作修改',url('category/cate_list'));
        }else{
            $this->error('修改失败',url('category/cate_upd',['cate_id'=>$cate_id]));
        }
    }
    //删除的方法
    public function cate_del(){
        $cate_id=input('cate_id');
        $obj=new Mod();
        $where=[
            ['pid',"=",$cate_id]
        ];
        $res=$obj->where($where)->count();
        // dump($res);
        if($res>0){
            $this->error('该标题下有分类，禁止删除');
            die;
        }

        $goodsobj = new Goods();
        $goods = $goodsobj->where("cate_id=$cate_id")->count();
        // dump($goods);die;
        if($goods>0){
            $this->error('该分类下有商品，不能删除');
            die;
        }


        $res2=$obj->where("cate_id=$cate_id")->delete();
        if($res2==1){
            $this->success('删除成功',url('category/cate_list'));
        }else{
            $this->error('删除失败',url('category/cate_add'));
        }
    }
    //既点既改
    public function checkvalue(){
        $_value=input("_value");
        $_field=input("_field");
        $cate_id=input("cate_id");
        $obj=new Mod();
        $where=[
            ["cate_id","=",$cate_id]
        ];
        $res=$obj->where($where)->update([$_field=>$_value]);
        if($res){
             echo "ok";
        }else{
             echo "no";
        }
    }
    public function checkshow(){
        $cate_id=input('cate_id');
        $field=input("field");
        $value=input('value');
    //    dump($cate_id);
    //    dump($field);
    //    dump($value);
        $obj=new Mod();
        $res=$obj->where("cate_id='$cate_id'")->update([$field=>$value]);
        // dump($res);die;
        if($res){
            echo "ok";
       }else{
            echo "no";
       }
    }
}