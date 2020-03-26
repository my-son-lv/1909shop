<?php

namespace app\admin\controller;

use think\Controller;

use app\admin\model\Order as OrderMod;

class Order extends Controller{
    public function order_list(){
        $orderModel = new OrderMod;
        $orderInfo=$orderModel->select();
        // dump($res);die;
        return view('order/order_list',['orderInfo'=>$orderInfo]);
    }
}
?>