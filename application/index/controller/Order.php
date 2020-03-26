<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\Cart as CartMod;
use app\index\model\Goods;
use app\index\model\User;
use app\index\model\Address;
use app\index\model\Order as orderMod;
use app\index\model\OrderAddress;
use app\index\model\OrderGoods;
class Order extends Common{
    public function index(){
        return view("index");
    }
    /**确认订单 */
    public function comfirmOrder(){
        // 接值
        $goods_id=input('goods_id');
        $address_id=input('address_id');
        $pay_type=input('pay_type');
        $order_talk=input('order_talk');
        // 实例化
        $goodsModel = new Goods;
        $addressModel = new Address;
        $orderModel = new OrderMod;
        $orderAddressModel = new OrderAddress;
        $orderGoodsModel = new OrderGoods;
        $cartModel = new CartMod;
        //获取用户id
        $user_id=$this->getUserId();
        //验证商品id
        if(empty($goods_id)){
            $this->error('至少选择一件商品进行下单');die;
        }else{
            $goodswhere=[
                ['g.goods_id','in',$goods_id],
                ['cart_del','=',1],
                ['user_id','=',$user_id]
            ];
            //商品信息有商品的id 名字 价格 图片。。无购买数量
            //如果查询到数据是个二维的对象，如果没有查询到数据也是个对象
            $goodsInfo=$goodsModel
                        ->field('g.goods_id,goods_name,goods_price,buy_number,goods_img')
                        ->alias('g')
                        ->join('cart c','g.goods_id=c.goods_id')
                        ->where($goodswhere)
                        ->select();
            if(empty($goodsInfo[0])){
                $this->error('商品信息有误');die;
            }
        }
        
        //验证收货地址id
        if(empty($address_id)){
            $this->error('收货地址必填');die;
        }else{
            $addresswhere=[
                ['address_id','=',$address_id],
                ['is_del','=',1]
            ];
            //如果查到数据是对象，没有查到是空
            $addressInfo=$addressModel->where($addresswhere)->find();
            if(empty($addressInfo)){
                $this->error('收货地址有误');die;
            }
        }
        //验证支付方式
        if(empty($pay_type)){
            $this->error('支付方式必选');die;
        }
        // 开启事务
        $orderModel->startTrans();
        // 1、订单信息存入到订单表
        //订单号
        $order_no=time().$pay_type.rand(1000,9999).$user_id;
        //总价
        $order_amount=0;
        foreach($goodsInfo as $k=>$v){
            $order_amount += $v['goods_price']*$v['buy_number'];
        }
        // print_r($order_amount);die;
        //根据规则计算出优惠的价格 满减后的价格
        if($order_amount>=100&&$order_amount<=1000){
            //满100减20
            $less_money = floor($order_amount/100)*20;
        }else if($order_amount>1000){
            $less_money = 300;
        }else{
            $less_money = 0;
        }
        $order_amount=$order_amount-$less_money;
        //根据积分规则 计算应赠送用户多少积分
        if($order_amount>=300){
            $score = $order_amount;
        }else if($order_amount<300){
            $score = 0;
        }
        //save要添加的一维数组里的值
        $orderInfo=[
            'order_no'=>$order_no,
            'order_amount'=>$order_amount,
            'order_score'=>$score,
            'order_time'=>time(),
            'pay_type'=>$pay_type,
            'order_talk'=>$order_talk,
            'user_id'=>$user_id
        ];
        // print_r($orderInfo);
        $res1=$orderModel->save($orderInfo);
        if(empty($res1)){
            $orderModel->rollback();
            fail('订单添加失败');
        }

        // print_r($res1);
        //获取刚刚添加的订单的id
        $order_id=$orderModel->order_id;
        // 2、订单收货地址信息存储到订单的收货信息表
        $addressInfo=$addressInfo->toArray();
        $addressInfo['order_id']=$order_id;
        $res2=$orderAddressModel->save($addressInfo);
        if(empty($res2)){
            $orderModel->rollback();
            fail('订单收货地址添加失败');
        }
        // dump($res2);
        // 3、订单收货信息 存储到订单的商品表
        foreach($goodsInfo as $k=>$v){
            $goodsInfo[$k]['user_id']=$user_id;
            $goodsInfo[$k]['order_id']=$order_id;
        }
        $goodsInfo=$goodsInfo->toArray();
        $res3=$orderGoodsModel->saveAll($goodsInfo);
        if(empty($res3)){
            $orderModel->rollback();
            fail('订单收货信息添加失败');
        }
        // print_r($goodsInfo);die;
        // dump($res3);
        // 4、清楚购物车表
        $cartWhere=[
            ['user_id','=',$user_id],
            ['goods_id','in',$goods_id]
        ];
        $res4=$cartModel->where($cartWhere)->update(['cart_del'=>2]);
        if(empty($res4)){
            $orderModel->rollback();
            fail('购物车列表清除失败');
        }
        // dump($cartInfo);
        // 5、修改商品表的库存
        foreach($goodsInfo as $k=>$v){
            $res5=$goodsModel->where('goods_id',$v['goods_id'])->setDec('goods_num',$v['buy_number']);
            if(empty($res5)){
                $orderModel->rollback();
                fail('商品库存修改失败');
            }
        }
        //成功 提交
            $orderModel->commit();
            // successful('下单成功');
            $arr=['font'=>'下单成功','code'=>1,'order_id'=>$order_id];
            echo json_encode($arr);
    }
    /**下单成功 */
    public function orderSuccess(){
        $order_id=input('order_id');
        if(empty($order_id)){
            $this->error('非法操作');
        }
        $orderModel = new OrderMod;
        $where=[
            ['order_id','=',$order_id]
        ];
        $orderInfo=$orderModel->where($where)->find();
        if(empty($orderInfo)){
            $this->error('非法操作');
        }
        $this->getcateInfo();
        return view('order/order_success',['orderInfo'=>$orderInfo]);
    }
    /**立即支付 */
    public function payMoney1(){
        //得到订单id
        $order_id=input('order_id');
        if(empty($order_id)){
            $this->error('非法操作');
        }
        $orderModel = new OrderMod;
        $where=[
            ['order_id','=',$order_id]
        ];
        $orderInfo=$orderModel->where($where)->find();
        if(empty($orderInfo)){
            $this->error('非法操作');
        }
        //根据id得到订单的信息
        $config=config('alipay.');
        require_once '../extend/alipay/pagepay/service/AlipayTradeService.php';
        require_once '../extend/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';
        
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $orderInfo['order_no'];

        //订单名称，必填
        $subject = '电商支付';

        //付款金额，必填
        $total_amount = $orderInfo['order_amount'];

        //商品描述，可空
        $body = $orderInfo['order_talk'];

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);

        $aop = new \AlipayTradeService($config);

       $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);

        //输出表单
        var_dump($response);
    }
    /**同步跳转 */
    public function returnUrl(){
        //接值
        $data=input();
        //验证签名--确保通知是支付宝通知
        $config=config('alipay.');
        require_once '../extend/alipay/pagepay/service/AlipayTradeService.php';
        $alipayService = new \AlipayTradeService($config);
        $result = $alipayService->check($data);
        if(empty($result)){
            $this->error('签名验证失败',url('index/index'));
        }
        //验证应用id
        if($config['app_id']!=$data['app_id']){
            $this->error('应用id错误',url('index/index'));
        }
        //验证订单号
        $orderModel = new OrderMod;
        $where=[
            ['order_no','=',$data['out_trade_no']]
        ];
        $orderInfo = $orderModel->where($where)->find();
        if(empty($orderInfo)){
            $this->error('订单号错误',url('index/index'));
        }
        //验证订单总金额
        if($data['total_amount']!=$orderInfo['order_amount']){
            $this->error('订单总金额错误',url('index/index'));
        }
        //提示支付成功
        $this->getCateInfo();
        return view('order/return_url');
    }
    /**异步通知 */
    public function notiryUrl(){
        
    }
}


?>