<?php
namespace app\index\model;
use think\Model;
class Order extends Model{
    //默认主键id为id  取表中主键id需代码手动实现
    protected $pk = 'order_id';
}


?>