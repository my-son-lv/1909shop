<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\Category;
use app\index\model\Brand;
use app\index\model\Goods as GoodsMod;
use app\index\model\History;
class Goods extends Common{
    //商品展示
    public function goods_list(){
        //接受分类id 做成where条件
        $cate_id=input('cate_id');
        if(!empty($cate_id)){
            //获取cate_id每一级的子类id
            $cateModel = new Category;
            $cateInfo=$cateModel->select();
            // dump($cateInfo);
            $cate_id=getCateId($cateInfo,$cate_id);
            $where=[
                ["cate_id","in",$cate_id]
            ];
            cookie("c_id",$cate_id);
        }else{
            $where=[];
            cookie("c_id",null);
        }
        //1、品牌数据--带条件
        //得到当前分类下的商品所属于的品牌ID
        $GoodsModel = new GoodsMod;
        $brand_id=$GoodsModel->where($where)->column("brand_id");
        $brand_id=array_unique($brand_id);
        // print_r($brand_id);
        $brandWhere=[
            ['brand_id','in',$brand_id]
        ];
        $brandModel = new Brand;
        $brandInfo=$brandModel->where($brandWhere)->select();
        // dump($brandInfo);

        //2、价格区间--带条件
        $max_price=$GoodsModel->where($where)->value("max(goods_price)");   //获取商品价格最高的那个值
        // echo $GoodsModel->getLastSql();
        $priceInfo = $this->geiPriceSection($max_price);
        // dump($priceInfo);

        //3、品牌数据+分页--带条件
        $p=1;
        $PageNum=4;
        $goodsInfo = $GoodsModel->where($where)->page($p,$PageNum)->select();

        $count = $GoodsModel->where($where)->count();
        $page = ceil($count/$PageNum);
        // dump($page);
        $str="";
        for($i=1;$i<=$page;$i++){
            if($p==$i){
                $str.="<a href='javascript:;' class='page cur'>$i</a>";
            }else{
                $str.="<a href='javascript:;' class='page'>$i</a>";
            }
        }
        //得到浏览历史记录
        if($this->checkLogin()){    //判断session是否取值(判断是否登录)
            //获取浏览历史记录--数据库
            $historyInfo=$this->getHistoryInfoDb();
        }else{
            //获取浏览历史记录--cookie
            $historyInfo=$this->getHistoryCookie();
        }
        $this->getcateInfo();
        $this->assign("brandInfo",$brandInfo);
        $this->assign("historyInfo",$historyInfo);
        $this->assign("priceInfo",$priceInfo);
        $this->assign("goodsInfo",$goodsInfo);
        $this->assign("str",$str);
        return view("goods/goods_list");
    }
    //获取浏览历史记录--数据库
    public function getHistoryInfoDb(){
        $historyModel = new History;    //实例化对象
        //查询浏览历史表，得到当前用户浏览的商品id（根据时间排序）
        $user_id=$this->getUserId();
        $where=[
            ["user_id",'=',$user_id]
        ];
        $goods_id=$historyModel->where($where)->order("look_time desc")->column('goods_id');
        if(!empty($goods_id)){
            $goods_id=array_unique($goods_id);  //去重
            $goods_id=array_slice($goods_id,0,3);   //从数组中取出一段  数组截取
            //根据浏览的商品id，得到浏览过的商品信息
            $goodsModel = new GoodsMod;
            $goodsWhere=[
                ["goods_id","in",$goods_id]
            ];
            //按照指定的商品id顺序查询数据
            $goods_id=implode(',',$goods_id);   //将$goods_id数组连接成字符串，使其符合$exp条件
            $exp=new \think\db\Expression("field(goods_id,$goods_id)"); //tp框架自带
            $historyInfo=$goodsModel->where($goodsWhere)->order($exp)->select();
            return $historyInfo;
        }
        
    }
    //获取浏览历史记录--cookie
    public function getHistoryCookie(){
        $historyInfo=cookie('historyInfo');
        if(!empty($historyInfo)){
            //cookie中取出浏览的商品id
            $historyInfo=array_reverse($historyInfo);   //数组反转
            $goods_id=array_column($historyInfo,'goods_id');    //从数组中取出goods_id一列
            $goods_id=array_unique($goods_id);  //数组去重
            $goods_id=array_slice($goods_id,0,3);   //取出商品id前三位
             //根据浏览的商品id，得到浏览过的商品信息
             $goodsModel = new GoodsMod;
             $goodsWhere=[
                 ["goods_id","in",$goods_id]
             ];
             //按照指定的商品id顺序查询数据
             $goods_id=implode(',',$goods_id);   //将$goods_id数组连接成字符串，使其符合$exp条件
             $exp=new \think\db\Expression("field(goods_id,$goods_id)"); //tp框架自带
             $historyInfo=$goodsModel->where($goodsWhere)->order($exp)->select();
             return $historyInfo;
        }
    }
    //重新获取商品数据和页码
    public function getGoodsInfo(){
        //接值
        $brand_id=input('brand_id');
        $goods_price=input('goods_price');
        $field=input('field');
        $p=input('p');
        $c_id=cookie("c_id"); //取cookie(分类id)
        //处理条件
        $where=[];
        if(!empty($c_id)){
            $where[]=["cate_id","in",$c_id];
        }
        if(!empty($brand_id)){
            $where[]=["brand_id","=",$brand_id];
        }
        if(!empty($field)){  //$field分为两种形式 **-**或**及以上
            if(substr_count($goods_price,"-")>0){//判断形式是否为"-"
                $goods_price=explode('-',$goods_price);//区间 **-**形式
                $goods_price[0]=str_replace(',','',$goods_price[0]);
                $goods_price[1]=str_replace(',','',$goods_price[1]);
                // print_r($goods_price);
                $where[]=["goods_price","between",$goods_price];
            }else{
                $goods_price=(int)$goods_price;//**及以上形式  (int)提取开头数字
                $where[]=["goods_price",">=",$goods_price];
            }
        }
        if(!empty($field)){
            $where[]=[$field,"=",'是'];
        }
        //新数据
        $GoodsModel = new GoodsMod;
        $PageNum=4;
        $goodsInfo = $GoodsModel->where($where)->page($p,$PageNum)->select();

        $count = $GoodsModel->where($where)->count();
        $page = ceil($count/$PageNum);
        // dump($page);
        $str="";
        for($i=1;$i<=$page;$i++){
            if($p==$i){
                $str.="<a href='javascript:;' class='page cur'>$i</a>";
            }else{
                $str.="<a href='javascript:;' class='page'>$i</a>";
            }
        }
        $this->view->engine->layout(false);//临时关闭layout模板布局
        return view("goods/div",['goodsInfo'=>$goodsInfo,'str'=>$str]);
    }
    //重新获取价格区间
    public function getPrice(){
        $c_id=cookie("c_id");
        $brand_id=input('brand_id');
        //处理条件
        $where=[];
        if(!empty($c_id)){
            $where[]=["cate_id","in",$c_id];
        }
        if(!empty($brand_id)){
            $where[]=["brand_id","=",$brand_id];
        }
        $GoodsModel = new GoodsMod;
        $max_price=$GoodsModel->where($where)->value("max(goods_price)");//获取商品价格最高的那个值
        // echo $GoodsModel->getLastSql();
        $priceInfo = $this->geiPriceSection($max_price);
        $str="";
        foreach($priceInfo as $k=>$v){
            $str.="<a href='javascript:;' class='goods_price'>".$v."</a>";
        }
        echo $str;
        // dump($priceInfo);
    }
    //价格分成7分的价格区间
    public function geiPriceSection($max_price){
        $price = $max_price/7;
        for($i=0;$i<=6;$i++){
            $left = $price*$i;
            $right = $price*($i+1)-0.01;
            // dump($right);
            $priceInfo[]=number_format($left,2).'-'.number_format($right,2);
        }
        $priceInfo[]=$max_price.'及以上';
        return $priceInfo;
    }
    //商品详情
    public function product(){
        $goods_id=input('goods_id');
        if(empty($goods_id)){
            $this->error('非法操作',url('goods/goods_list'));
            die;
        }
        $GoodsModel = new GoodsMod;
        $where=[
            ["goods_id","=",$goods_id]
        ];
        $goodsInfo=$GoodsModel->where($where)->find();
        $goodsInfo['goods_imgs']=explode("|",$goodsInfo['goods_imgs']);

        // 存储历史浏览记录
        if($this->checkLogin()){
            //已登录 商品id 浏览时间 用户id存到cookie
            $this->saveHistoryDb($goods_id);
        }else{
            //未登录 商品id 浏览时间存到cookie
            $this->saveHistoryCookie($goods_id);
        }

        $this->assign("goodsInfo",$goodsInfo);
        $this->getcateInfo();
        return view("goods/product");
    }
    //存储浏览历史记录--数据库
    public function saveHistoryDb($goods_id){
        $user_id=$this->getUserId();
        $historyInfo=['goods_id'=>$goods_id,'look_time'=>time(),'user_id'=>$user_id];
        // dump($historyInfo);
        $historyModel = new History;
        $res=$historyModel->save($historyInfo);
        // dump($res);
    }
    //存储浏览历史记录--cookie中
    public function saveHistoryCookie($goods_id){
        $historyInfo=cookie("historyInfo");
        $historyInfo[]=['goods_id'=>$goods_id,'look_time'=>time()];
        cookie("historyInfo",$historyInfo);
    }
    public function test(){
        $aa=cookie("historyInfo");
        dump($aa);
    }
}
?>