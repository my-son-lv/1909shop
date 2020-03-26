<?php
namespace app\admin\controller;
use \think\Controller;
use app\admin\model\Category;
use app\admin\model\Brand;
use app\admin\model\Goods as Goodsmod;
class Goods extends Common{
    //添加展示的方法
    public function goods_add(){
        $obj1 = new Category;
        $res1=$obj1->select();
        $info=getcateinfo($res1);
        $obj2 = new Brand;
        $res2=$obj2->select();
        return view("goods/goods_add",['catedata'=>$info,'branddata'=>$res2]);             
    }
    //添加的方法
    public function add_do(){
        $all=input();
        // dump($all);
        $goods_name=$all['goods_name'];
        $obj = new Goodsmod;
        $findone=$obj->where("goods_name='$goods_name'")->find();
        // dump($findone);die;
        if(empty($goods_name)){
            $this->error('商品名称不能为空',url('goods/goods_add'));
            die;
        }else if(!empty($findone)){
            $this->error('该商品名称已存在',url('goods/goods_add'));
            die;
        }
        if(empty($all['goods_price'])){
            $this->error('商品价格不能为空',url('goods/goods_add'));
            die;
        }
        if(empty($all['goods_desc'])){
            $this->error('商品介绍不能为空',url('goods/goods_add'));
            die;
        }
        if(empty($all['goods_num'])){
            $this->error('商品库存不能为空',url('goods/goods_add'));
            die;
        }
        if(empty($all['goods_score'])){
            $this->error('商品积分不能为空',url('goods/goods_add'));
            die;
        }
        $files=request()->file('goods_imgs');
        $goods_imgs="";
        foreach($files as $filevalue){
            $info=$filevalue->move("./img");
            if($info){
                $goods_imgs.=$info->getSaveName()."|";
            }else{
                echo $file->getError();
            }
        }
        $goods_imgs=rtrim($goods_imgs,"|");
        $all['goods_imgs']=$goods_imgs;
        // dump($goods_imgs);die;
        if($_FILES['goods_img']['error']==4){
            $this->error('文件上传失败',url('goods/goods_add'));
            die;
        }
        $filea=request()->file('goods_img');
        $infoa=$filea->move('./img');
        $all['goods_img']=$infoa->getSaveName();
        // dump($all);die;
        $res=$obj->save($all);
        // dump($res);
        if($res){
            $this->success('添加成功',url('goods/goods_list'));
        }else{
            $this->error('添加失败',url('goods/goods_add'));
        }

    }
    //展示列表的方法
    public function goods_list(){
        $all=input();
        // dump($all);
        $where=[];
        if(!empty($all['uname'])){
            $where[]=['goods_name',"like","%".$all['uname']."%"];
        }
        if(!empty($all['sel'])){
            $where[]=['brand_name','like',"%".$all['sel']."%"];
        }
        if(!empty($all['min_price'])&&!empty($all['max_price'])){
            $where[]=["goods_price",'between',[$all['min_price'],$all['max_price']]];
        }
        $obj2 = new Brand;
        $res2=$obj2->select();
        $obj = new Goodsmod;
        //三表联查 商品表 分类表 品牌表
        $res=$obj
            ->alias("g")
            ->field("g.*,brand_name,cate_name")
            ->leftjoin("brand b","g.brand_id=b.brand_id")
            ->leftjoin("category c","g.cate_id=c.cate_id")
            ->where($where)
            ->paginate(2,false,['query'=>$all]);
        // dump($res);die;
        foreach($res as $k=>$v){
            $res[$k]['goods_imgs']=explode("|",$v['goods_imgs']);
        }
        // dump($res);die;
        return view("goods/goods_list",['data'=>$res,'branddata'=>$res2]);
    }
    //删除的方法
    public function goods_del(){
        $goods_id=input('goods_id');
        $obj = new Goodsmod;
        $res=$obj->where("goods_id=$goods_id")->delete();
        if($res){
            $this->success('删除成功',url('goods/goods_list'));
        }else{
            $this->error('删除失败',url('goods/goods_list'));
        }
    }
    public function goods_upd(){
        $goods_id=input('goods_id');
        // dump($goods_id);
        $obj = new Goodsmod;
        $res=$obj->where("goods_id=$goods_id")->find();
        $res['goods_imgs']=explode("|",$res['goods_imgs']);
        // dump($res);
        $obj1 = new Category;
        $res1=$obj1->select();
        $info=getcateinfo($res1);
        $obj2 = new Brand;
        $res2=$obj2->select();
        return view("goods/goods_upd",['goodsdata'=>$res,'catedata'=>$info,'branddata'=>$res2]);   
    }
    public function upd_do(){
        $goods_id=input('goods_id');
        $all=input();
        //相册上传
        $files=request()->file('goods_imgs');
        $goods_imgs="";
        foreach($files as $filevalue){
            $info=$filevalue->move("./img");
            if($info){
                $goods_imgs.=$info->getSaveName()."|";
            }else{
                echo $file->getError();
            }
        }
        $goods_imgs=rtrim($goods_imgs,"|");
        $all['goods_imgs']=$goods_imgs;   
        // dump($goods_imgs);die;
        //单个文件上传
        if($_FILES['goods_img']['error']!=4){
            $filea=request()->file('goods_img');
            $infoa=$filea->move('./img');
            if(empty($infoa)){
                $this->error('文件上传失败',url('goods/goods_upd'));
                die;
            }
            $all['goods_img']=$infoa->getSaveName();
            // $this->error('文件上传失败',url('goods/goods_add'));
            // die;
        }
        $obj = new Goodsmod;
        $res=$obj->where("goods_id=$goods_id")->update($all);
        // dump($res);
        if($res===1){
            $this->success("修改成功",url('goods/goods_list'));
        }else if($res===0){
            $this->error("未作修改",url('goods/goods_list'));
        }else{
            $this->error("修改失败",url('goods/goods_upd',['goods_id'=>$goods_id]));
        }
    }
}



?>