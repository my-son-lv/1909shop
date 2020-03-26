<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\Cart as CartMod;
use app\index\model\Goods;
use app\index\model\Address;
use app\index\model\Order;
use app\index\model\OrderAddress;
use app\index\model\OrderGoods;
class Cart extends Common{
    /**购物车数据添加到数据库 */
    public function addCart(){
        //1、接值
        $buy_number=input('buy_number');    //接购买数量的值
        $goods_id=input('goods_id');    //接商品id的·值
        //验证非法操作
        if(empty($goods_id)){
            fail("非法操作");
        }
        $goodsModel = new Goods;
        $count=$goodsModel->where("goods_id=$goods_id")->count();
        if($count<1){
            fail("非法操作");
        }
        if(empty($buy_number)){
            fail("购买数量至少一件");
        }

        //2、判断用户是否登录
        if($this->checkLogin()){
            //加入购物车--数据库  已登录
            $CartInfo=$this->addCartDb($buy_number,$goods_id);
        }else{
            //加入购物车--cookie   未登录
            $this->addCartCookie($buy_number,$goods_id);
        }
    }
    /**加入购物卡--数据库*/
    public function addCartDb($buy_number,$goods_id){
        $user_id=$this->getUserId();  //获取user_id
        $cartModel = new CartMod;
        //查询商品是否加入过购物车
        $where=[
            ["goods_id","=",$goods_id],
            ["user_id","=",$user_id],
            ["cart_del","=",1]
        ];
        $cartInfo=$cartModel->where($where)->find();
        //查询库存
        $goodsModel = new Goods;
        $goods_num=$goodsModel->where("goods_id=$goods_id")->value('goods_num');
        //判断商品是否加入过购物车
        if(!empty($cartInfo)){  //如果商品加入过购物车
            //检查库存
            if(($cartInfo['buy_number']+$buy_number)>$goods_num){
                //如果购物车中的商品数量+新加入购物车的商品数量 大于商品库存 则购物车的商品数量显示商品库存
                $num = $goods_num;
            }else{  
                //反之则正常相加
                $num=$cartInfo['buy_number']+$buy_number;
            }
            //购买数量累加
            $data=['buy_number'=>$num,'add_time'=>time()];
            //加入购物车后 购物车中的商品随之更改
            $res=$cartModel->where($where)->update($data);
            // dump($res);
            if($res){
                successful("");
            }else{
                fail("");
            }
        }else{  //如果该商品没有加入过购物车
            //检测库存
            if($buy_number>$goods_num){
                //加入购物车的商品数量不能超过库存
                $buy_number = $goods_num;
            }
            //添加数据
            //把商品id、购买数量、加入时间、用户id存入数据库
            $info=['goods_id'=>$goods_id,'buy_number'=>$buy_number,'add_time'=>time(),'user_id'=>$user_id];
            //正常存入数据库
            $res=$cartModel->save($info);
            // dump($res);
            if($res){
                successful("");
            }else{
                fail("");
            }
        } 
    }
    /**加入购物车--cookie*/
    public function addCartCookie($buy_number,$goods_id){
        $cartInfo = cookie("cartInfo");
        if(empty($cartInfo)){
            $cartInfo=[];
        }
         //查询库存
         $goodsModel = new Goods;
         $goods_num=$goodsModel->where("goods_id=$goods_id")->value('goods_num');
        if(array_key_exists($goods_id,$cartInfo)){
            //检查库存
            if(($cartInfo[$goods_id]['buy_number']+$buy_number)>$goods_num){
                //如果购物车中的商品数量+新加入购物车的商品数量 大于商品库存 则购物车的商品数量显示商品库存
                $num = $goods_num;
            }else{  
                //反之则正常相加
                $num=$cartInfo[$goods_id]['buy_number']+$buy_number;
            }
            $cartInfo[$goods_id]['buy_number']=$num;
            $cartInfo[$goods_id]['add_time']=time();
        }else{
            //检测库存
            if($buy_number>$goods_num){
                $buy_number = $goods_num;
            }
            $cartInfo[$goods_id]=['goods_id'=>$goods_id,'buy_number'=>$buy_number,'add_time'=>time()];
        }
        // $cartInfo[$goods_id]=['goods_id'=>$goods_id,'buy_number'=>$buy_number,'add_time'=>time()];
        cookie("cartInfo",$cartInfo);
        successful("");
    }
    /**购物车列表展示 */
    public function cart_list(){
        //判断是否登录
        if($this->checkLogin()){
            //取出购物车数据--数据库
            $cartInfo=$this->getCartDb();
        }else{
            //取出购物车数据--cookie
            $cartInfo=$this->getCartCookie();
        }
        $this->getcateInfo();
        $this->assign("cartInfo",$cartInfo);
        return view("cart/cart_list");
    }
    /**取出购物车数据--数据库*/
    public function getCartDb(){
        $user_id=$this->getUserId();
        //两表联查
        // $goodsModel = new Goods;
        $cartModel = new CartMod;
        $where=[
            ['user_id','=',$user_id],
            ['cart_del','=',1]
        ];
        $cartInfo=$cartModel
            ->field('c.goods_id,buy_number,goods_name,goods_price,goods_num,goods_img')
            ->alias('c')
            ->join('goods g','c.goods_id=g.goods_id')
            ->where($where)
            ->order('add_time','desc')
            ->select();
            // dump($cartInfo);
            return $cartInfo;
    }
    /**取出购物车数据--cookie*/
    public function getCartCookie(){
        //取出cookie数据
        $cartInfo=cookie('cartInfo');
        // dump($cartInfo);
        if(!empty($cartInfo)){
            //按照时间倒序排序
            $add_time=array_column($cartInfo,'add_time');
            array_multisort($add_time,SORT_DESC,$cartInfo);
            $goodsModel = new Goods;
            //循环处理
            foreach($cartInfo as $k=>$v){
                //根据商品id查商品表
                $info=$goodsModel->field("goods_name,goods_price,goods_num,goods_img")->where("goods_id",$v['goods_id'])->find();
                $info=$info->toArray();
                $cartInfo[$k] = array_merge($v,$info);

            }
            // print_r($cartInfo);die;
            return $cartInfo;
        }
    }
    /**更改购买数量 */
    public function checkNumber(){
        $goods_id = input('goods_id');
        $buy_number = input('buy_number');
        //判断用户是否登录
        if($this->checkLogin()){
            //更改购买数量--数据库
            $res=$this->changeNumberDb($goods_id,$buy_number);
        }else{
            //更改购买数量--cookie
            $res=$this->changeNumberCookie($goods_id,$buy_number);
        }
        if($res!==false){
            successful('');
        }else{
            fail('操作失败');
        }
    }
    /**更改购买数量--数据库 */
    public function changeNumberDb($goods_id,$buy_number){
        $cartModel = new CartMod;
        $user_id = $this->getUserId();
        $where=[
            ['goods_id','=',$goods_id],
            ['user_id','=',$user_id],
            ['cart_del','=',1]
        ];
        $res=$cartModel->where($where)->update(['buy_number'=>$buy_number]);
        return $res;
    }
     /**更改购买数量--Cookie */
    public function changeNumberCookie($goods_id,$buy_number){
        //取出cookie的值
        $cartInfo=cookie('cartInfo');
        if(!empty($cartInfo)){
            $cartInfo[$goods_id]['buy_number']=$buy_number;
            cookie("cartInfo",$cartInfo);
        }
        return $cartInfo;
    }
    /**获取小计 */
    public function getTotal(){
        $goods_id=input('goods_id');
        //根据商品id获取单价
        $goodsModel = new Goods;
        $goods_price=$goodsModel->where("goods_id=$goods_id")->value('goods_price');
        // dump($goods_price);
        //判断是否登录
        if($this->checkLogin()){
            //获取购买数量--数据库
            $buy_number=$this->getNumberDb($goods_id);
        }else{
            //获取购买数量--cookie
            $buy_number=$this->getNumberCookie($goods_id);
        }
        echo $goods_price*$buy_number;
    }
    /**获取购买数量（小计）--数据库 */
    public function getNumberDb($goods_id){
        $user_id=$this->getUserId();
        $where=[
            ['goods_id','=',$goods_id],
            ['user_id','=',$user_id],
            ['cart_del','=',1]
        ];
        $cartModel = new CartMod;
        $buy_number=$cartModel->where($where)->value('buy_number');
        return $buy_number;
    }
    /**获取购买数量（小计）--Cookie */
    public function getNumberCookie($goods_id){
        //取出cookie数据
        $cartInfo=cookie('cartInfo');
        if(!empty($cartInfo)){
            // print_r($cartInfo);exit;
            return $cartInfo[$goods_id]['buy_number'];
        }
    }
    /**获取总价 */
    public function getMoney(){
        //接受ajax传过来的复选框的商品id
        $goods_id=input('goods_id');
        //判断是否登录
        if($this->checkLogin()){
            //获取总价--数据库
            $money = $this->getMoneyDb($goods_id);
        }else{
            //获取总价--cookie
            $money = $this->getMoneyCookie($goods_id);
        }
        echo $money;
    }
    /**获取总价--数据库 */
    public function getMoneyDb($goods_id){
        //根据商品id得到单价和数量
        $user_id=$this->getUserId();
        // $goodsModel = new Goods;
        $cartModel = new CartMod;
        $where=[
            ['c.goods_id','in',$goods_id],
            ['user_id','=',$user_id],
            ['cart_del','=',1]
        ];
        $info=$cartModel
            ->field('goods_price,buy_number')
            ->alias('c')
            ->join('goods g','c.goods_id=g.goods_id')
            ->where($where)
            ->select();
            // print_r($info);
        //获取总价
        $money=0;
        foreach($info as $k=>$v){
            $money+=$v['goods_price']*$v['buy_number'];
        }
        return $money;
    }
    /**获取总价--cookie */
    public function getMoneyCookie($goods_id){
        //获取cookie的数据
        $cartInfo=cookie('cartInfo');
        if(!empty($cartInfo)){
            //根据商品id得到单价和数量
            //explode将字符串分割成数组
            $goods_id=explode(',',$goods_id);
            //实例化对象
            $goodsModel = new Goods;
            //定义一个money 用来求总价
            $money=0;
            foreach($cartInfo as $k=>$v){
                //判断此商品是否存在于cookie中
                if(in_array($v['goods_id'],$goods_id)){
                    //得到单价于数量
                    $goods_price=$goodsModel->where("goods_id",$v['goods_id'])->value('goods_price');
                    $money += $goods_price*$v['buy_number'];  //求总价
                }
            }
            return $money;
        }
    }
    /**删除 */
    public function cartDel(){
        $goods_id=input('goods_id');
        //判断用户是否登录
        if($this->checkLogin()){
            //删除购物车数据--数据库
            $res=$this->cartDelDb($goods_id);
        }else{
            //删除购物车数据--cookie
            $res=$this->cartDelCookie($goods_id);
        }
        //判断删除是否成功
        if($res){
            successful("");
        }else{
            fail("删除失败");
        }
    }
    /**删除购物车数据--数据库*/
    public function cartDelDb($goods_id){
        $user_id=$this->getUserId();
        // dump($goods_id);
        $cartModel = new CartMod;
        $where=[
            ['goods_id',"in",$goods_id],
            ['user_id',"=",$user_id],
            ['cart_del','=',1]
        ];
        $res=$cartModel->where($where)->update(['cart_del'=>2]);
        return $res;
    }
    /**删除购物车数据--cookie*/
    public function cartDelCookie($goods_id){
        //取出cookie数据
        $cartInfo=cookie('cartInfo');
        //判断cookie是否有值
        if(!empty($cartInfo)){
            //从cookie中删除此商品id的数据
            if(substr_count($goods_id,',')>0){
                //批量删除
                $goods_id=explode(',',$goods_id);  //先切割成数组
                // dump($goods_id);exit;
                foreach($cartInfo as $k=>$v){
                    if(in_array($v['goods_id'],$goods_id)){
                        unset($cartInfo[$k]);
                    }
                }
            }else{
                //单删
                unset($cartInfo[$goods_id]);
            }
            cookie('cartInfo',$cartInfo);
            return true;
        }
    }
    /**确认结算 */
    public function confirmSettlement(){
        //判断是否登录
        if(!$this->checkLogin()){
            $this->error('请先登录',url('login/login'));
            die;
        }
        $goods_id=input('goods_id');
        if(empty($goods_id)){
            $this->error('请至少选择一件商品进行结算',url('cart_list'));
        }
        //根据商品id查询商品数据
        $user_id=$this->getUserId();
        //两表联查
        // $goodsModel = new Goods;
        $cartModel = new CartMod;
        $where=[
            ['c.goods_id','in',$goods_id],
            ['user_id','=',$user_id],
            ['cart_del','=',1]
        ];
        $goodsInfo=$cartModel
            ->field('c.goods_id,buy_number,goods_name,goods_price,goods_num,goods_img')
            ->alias('c')
            ->join('goods g','c.goods_id=g.goods_id')
            ->where($where)
            ->order('add_time','desc')
            ->select();
        //求总价
        $money=$this->getMoneyDb($goods_id);
        //根据规则计算出优惠的价格 满减后的价格
        if($money>=100&&$money<=1000){
            //满100减20
            $less_money = floor($money/100)*20;
        }else if($money>1000){
            $less_money = 300;
        }else{
            $less_money = 0;
        }
        $pay_money = $money-$less_money;
        //根据积分规则 计算应赠送用户多少积分
        if($money>=300){
            $score = $money;
        }else if($money<300){
            $score = 0;
        }
        //获取收货地址
        $addressInfo = $this->getaddressInfo();
        $empty="<h2><a href='".url('UserAddress/index')."'></a></h2>";
        $this->getcateInfo();
        return view("cart/confirmSettlement",['goodsInfo'=>$goodsInfo,'money'=>$money,'addressInfo'=>$addressInfo,'empty'=>$empty,'score'=>$score,'pay_money'=>$pay_money,'less_money'=>$less_money]);
    }
    /**验证cookie*/
    public function test(){
        $aa=cookie("cartInfo");
        dump($aa);
    }
}
?>