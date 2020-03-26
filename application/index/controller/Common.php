<?php
namespace app\index\controller;
use app\index\model\Category;
use think\Controller;
use app\index\model\Cart;
use app\index\model\Area;
use app\index\model\Address;
class Common extends Controller{
    //查询分类顶级分类（导航）
    public function getcateInfo(){
        $obj = new Category;
        $where=[
            ["pid","=",0],
            ["cate_nav_show","=",1]
        ];
        $res=$obj->where($where)->select();
        $this->assign("cateInfo",$res);
        
        $leftInfo=$obj->select();
        $aa=getleftInfo($leftInfo);
        $this->assign("leftInfo",$aa);

        //取出购物车数据--数据库
        $user_id=$this->getUserId();
        //两表联查
        // $goodsModel = new Goods;
        $cartModel = new Cart;
        $where=[
            ['user_id','=',$user_id],
            ['cart_del','=',1]
        ];
        $navInfo=$cartModel
            ->field('c.goods_id,buy_number,goods_name,goods_price,goods_img')
            ->alias('c')
            ->join('goods g','c.goods_id=g.goods_id')
            ->where($where)
            ->order('add_time','desc')
            ->limit("3")
            ->select();
        $this->assign('navInfo',$navInfo);
    }  
    //检测是否登录
    public function checkLogin(){
        //session取值 取到true 没取到false
        return session("?userInfo");
    }
    //获取用户的id
    public function getUserId(){
        return session("userInfo.user_id");
    }
    //获取当前登录用户的收货地址
    public function getAddressInfo(){
         //查询所有收货地址作为列表的数据
         $addressModel = new Address;
         //获取用户id
         $user_id=$this->getUserId();
         $where=[
             ['user_id','=',$user_id],
             ['is_del','=',1],
         ];
         $addressInfo=$addressModel->where($where)->select();
         $areaModel = new Area;
         foreach($addressInfo as $k=>$v){
             $addressInfo[$k]['province']=$areaModel->where("id",$v['province'])->value('name');
             $addressInfo[$k]['city']=$areaModel->where("id",$v['city'])->value('name');
             $addressInfo[$k]['area']=$areaModel->where("id",$v['area'])->value('name');
         }
         return $addressInfo;
    }
  
}
?>