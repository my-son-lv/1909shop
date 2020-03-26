<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\Area;
use app\index\model\Address;
class UserAddress extends Common{
    /**用户收货地址 */
    public function index(){
        $addressInfo=$this->getAddressInfo();
        // print_r($addressInfo);die;
        //查询所有的省份 作为一个下拉菜单的值 pid=0
        $provinceInfo=$this->getAreaInfo(0);
        return view("index",['provinceInfo'=>$provinceInfo,'addressInfo'=>$addressInfo]);
    }
    /**获取区域信息 */
    public function getAreaInfo($pid){
        $areaModel = new Area;
        $where=[
            ['pid','=',$pid]
        ];
        $info=$areaModel->where($where)->select();
        // print_r($info);
        return $info;
    }
    /**获取地区 */
    public function getArea(){
        $id=input('id');
        $info=$this->getAreaInfo($id);  //二维数组
        echo json_encode($info);
    }
    /**执行添加 */
    public function save(){
        //接收表单传过来的数据
        $data=input();
        //验证  非空 手机号格式
        //正则验证手机号
        $regtel='/^1[3568]\d{9}$/';
        //正则验证邮政编码
        $regmail='/^\d{6}$/';
        //省非空
        if(empty($data['province'])){
            $this->error('省份必填');
        }
        //城市非空
        if(empty($data['city'])){
            $this->error('城市必填');
        }
        //县区非空
        if(empty($data['area'])){
            $this->error('县区必填');
        }
        //收货人姓名非空
        if(empty($data['address_name'])){
            $this->error('收货人姓名不能为空');
        }
        //手机号非空 正则
        if(empty($data['address_tel'])){
            $this->error('手机号不能为空');
        }else if(!preg_match($regtel,$data['address_tel'])){
            $this->error('手机号格式不正确');
        }
        //地址非空
        if(empty($data['address_detail'])){
            $this->error('详细地址不能为空');
        }
        //邮政编码非空 正则
        if(empty($data['address_mail'])){
            $this->error('邮政编码不能为空');
        }else if(!preg_match($regmail,$data['address_mail'])){
            $this->error('邮政编码格式不正确');
        }
        $addressModel = new Address;
        $user_id = $this->getUserId();
        //判断是否选中‘设置为默认’
        if(!empty($data['is_default'])){
            $where=[
                ['user_id','=',$user_id],
                ['is_del','=',1]
            ];
            $addressModel->where($where)->update(['is_default'=>2]);
        }
        //入库
        $data['user_id'] = $user_id;
        // print_r($data);die;
        $res=$addressModel->save($data);
        // dump($res);
        if($res){
            $this->success('ok',url('index'));
        }else{
            $this->error('no',url('index'));
        }
    }
    /**移除收货地址 */
    public function addressDel(){
        $address_id=input('address_id');
        $addressModel = new Address;
        $res=$addressModel->where("address_id=$address_id")->update(['is_del'=>2]);
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    /**设置为默认 */
    public function setDefault(){
        //接收id
        $address_id=input('address_id');
        //获取用户id
        $user_id=$this->getUserId();
        //把这个用户的其他收货地址的default改为2
        $addressModel = new Address;
        $where=[
            ['user_id','=',$user_id],
            ['is_del','=',1]
        ];
        $addressModel->where($where)->update(['is_default'=>2]);
        //根据收货地址id把id_default改为1
        $res = $addressModel->where("address_id",$address_id)->update(['is_default'=>1]);
        if($res){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }
    /**编辑收货地址 */
    public function exit(){
        $address_id=input('address_id');
        //根据收货地址id 查询出一条要修改的数据--作为表单的默认值
        $addressModel = new Address;
        $addressInfo=$addressModel->where("address_id",$address_id)->find();
        // 查询所有省份 作为第一个下拉菜单的值
        $provinceInfo=$this->getAreaInfo(0);
        // 查询市 作为第二个下拉菜单的值
        $cityInfo=$this->getAreaInfo($addressInfo['province']);
        // 查询县区 作为第三个下拉菜单的值
        $areaInfo=$this->getAreaInfo($addressInfo['city']);
        return view('edit',['addressInfo'=>$addressInfo,'provinceInfo'=>$provinceInfo,'cityInfo'=>$cityInfo,'areaInfo'=>$areaInfo]);
    }
    /**执行编辑 */
    public function addressUpd(){
        //接收表单传过来的数据
        $data=input();
        //验证  非空 手机号格式
        //正则验证手机号
        $regtel='/^1[3568]\d{9}$/';
        //正则验证邮政编码
        $regmail='/^\d{6}$/';
        //省非空
        if(empty($data['province'])){
            $this->error('省份必填');
        }
        //城市非空
        if(empty($data['city'])){
            $this->error('城市必填');
        }
        //县区非空
        if(empty($data['area'])){
            $this->error('县区必填');
        }
        //收货人姓名非空
        if(empty($data['address_name'])){
            $this->error('收货人姓名不能为空');
        }
        //手机号非空 正则
        if(empty($data['address_tel'])){
            $this->error('手机号不能为空');
        }else if(!preg_match($regtel,$data['address_tel'])){
            $this->error('手机号格式不正确');
        }
        //地址非空
        if(empty($data['address_detail'])){
            $this->error('详细地址不能为空');
        }
        //邮政编码非空 正则
        if(empty($data['address_mail'])){
            $this->error('邮政编码不能为空');
        }else if(!preg_match($regmail,$data['address_mail'])){
            $this->error('邮政编码格式不正确');
        }
        $addressModel = new Address;
        $user_id = $this->getUserId();
        //判断是否选中‘设置为默认’
        if(!empty($data['is_default'])){
            $where=[
                ['user_id','=',$user_id],
                ['is_del','=',1]
            ];
            $addressModel->where($where)->update(['is_default'=>2]);
        }
        //执行修改
        $res=$addressModel->where("address_id",$data['address_id'])->update($data);
        if($res){
            $this->success('修改成功',url('index'));
        }else{
            $this->error('修改失败');
        }
    }
}


?>