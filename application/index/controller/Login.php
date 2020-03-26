<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\User;
use app\index\model\History;
use app\index\model\Cart;
use app\index\model\Goods;
class Login extends Common{
    //登录页面
    public function login(){
        //临时关闭当前模板布局
        $this->view->engine->layout(false);
        return view("login/login");
    }
    //登录
    public function loginDo(){
        $user_account=input('user_account');
        $user_pwd=input('user_pwd');
        $remember_me=input('remember_me');
        // dump($user_account);
        // dump($user_pwd);
        // dump($remember_me);
        // if(empty($user_account)){
        //     $this->error('手机号/邮箱不能为空');die;
        // }
        if(substr_count($user_account,'@')>0){
            $where=[
                ["user_email","=",$user_account]
            ];
        }else{
            $where=[
                ["user_tel","=",$user_account]
            ];
        }
        $user_model = new User;
        $userInfo=$user_model->where($where)->find();
        // dump($userInfo);die;
        if(!empty($userInfo)){
            $error_time=$userInfo['error_time'];
            $error_num=$userInfo['error_num'];
            $where=[
                ["user_id","=",$userInfo['user_id']]
            ];
            if($userInfo['user_pwd']==md5($user_pwd)){
                //如果密码正确且账号锁定中则阻止登录
                if($error_num>=3&&(time()-$error_time)<3600){
                    $min=60-floor((time()-$error_time)/60);
                    // dump($min);die;
                    fail('账号锁定中，请于'.$min.'分钟后登录');
                    die;
                }
                //清零
                $res=$user_model->where($where)->update(['error_num'=>0,'error_time'=>null]);
                //记住账号密码10天，取决于是否点击了记住密码
                if($remember_me=='true'){
                    cookie("remInfo",['user_account'=>$user_account,'user_pwd'=>$user_pwd],60*60*24*10);
                }
                session('userInfo',['user_id'=>$userInfo['user_id'],'user_account'=>$user_account]);
                //同步历史浏览记录
                $this->asyncHistory();
                //同步购物车数据
                $this->asyncCart();
                //把用户id 账号存储到session中
                successful('登录成功');
            }else{
                // echo '密码错误';
                if($error_num>=3){
                    if((time()-$error_time)>3600){
                        $res=$user_model->where($where)->update(['error_num'=>1,'error_time'=>time()]);
                        if($res){
                            // echo '密码错误，还有两次机会';
                            fail('账号或密码错误，还有两次机会');
                        }
                    }else{
                        $min=60-floor((time()-$error_time)/60);
                        // dump($min);die;
                        // echo '账号已锁定，请于'.$min.'分钟后登录';
                        fail('账号已锁定，请于'.$min.'分钟后登录');
                    }
                }else{
                    $res=$user_model->where($where)->update(['error_num'=>$error_num+1,'error_time'=>time()]);
                    // dump($res);
                    $num=3-($error_num+1);
                    // echo '密码错误，还有'.$num.'次机会';
                    fail('账号或密码错误，还有'.$num.'次机会');
                }
            }
        }else{
            // echo '账号错误';
            fail('账号或密码错误');
        }
    }
    //退出
    public function quit(){
        session("userInfo",null);
        cookie("remInfo",null);
        $this->success('退出成功',url('login/login'));

    }
    //同步历史浏览记录
    public function asyncHistory(){
        $historyInfo=cookie("historyInfo");
        if(!empty($historyInfo)){
            //cookie数据中缺数据id
            $user_id=$this->getUserId();
            foreach($historyInfo as $k=>$v){
                $historyInfo[$k]['user_id']=$user_id;
            }
            $historyModel = new History;
            $res=$historyModel->saveAll($historyInfo);
            if($res){
                cookie("historyInfo",null);//清楚cookie中的浏览历史记录
            }
        }
    }
    //同步购物车数据
    public function asyncCart(){
        //取出cookie数据
        $cartInfo=cookie('cartInfo');
        // print_r($cartInfo);die;
        $user_id=$this->getUserId();
        $cartModel = new Cart;
        if(!empty($cartInfo)){
             //循环每一条数据 查询在数据库购物车表中是否存在
            foreach($cartInfo as $k=>$v){
                $where=[
                    ['goods_id','=',$v['goods_id']],
                    ['user_id','=',$user_id],
                    ['cart_del','=',1]
                ];
                $info=$cartModel->where($where)->find();
                //查询库存
                $goodsModel = new Goods;
                $goods_num=$goodsModel->where("goods_id",$v['goods_id'])->value('goods_num');
                if(!empty($info)){
                    //库存
                    if(($info['buy_number']+$v['buy_number'])>$goods_num){
                        $num = $goods_num;
                    }else{
                        $num = $info['buy_number']+$v['buy_number'];
                    }
                    //累加
                    $arr=['buy_number'=>$num,'add_time'=>$v['add_time']];
                    $res=$cartModel->where($where)->update($arr);
                }else{
                    //库存
                    if($v['buy_number']>$goods_num){
                        $v['buy_number'] = $goods_num;
                    }
                    //添加
                    $v['user_id']=$user_id;
                    $res=$cartModel->insert($v);   //不能用save
                }
            }
            //清除cookie
            cookie('cartInfo',null);
        }
       
    }
    //注册页面
    public function register(){
        //临时关闭当前模板布局
        $this->view->engine->layout(false);
        return  view("login/register");
    }
    //验证邮箱注册的方法
    public function email(){
        sendEmail('3309449107@qq.com','注册','123');
    }
    //验证手机号注册的方法
    function tell(){
        $res=sendSms('18754039739','sbsbsb');
        dump($res);
    }
    //手机号注册
    public function sendtell(){
        $user_tel=input('user_tel');
        // dump($user_tel);die;
        //对值进行验证 非空 唯一性
        $reg='/^1[0-9]{10}$/';
        if(empty($user_tel)){
            echo '手机号不能为空';
            exit;
        }else if(preg_match($reg,$user_tel)<1){
            echo '手机号格式有误';
            exit;
        }else{
            $obj = new User;
            $where=[
                ['user_tel',"=",$user_tel]
            ];
            $res=$obj->where($where)->find();
            // dump($res);
            if($res){
                echo '手机号已被注册';
                die;
            }
        }
        // echo "啊哈哈哈";die;
        //随机出一个6位数验证码
        $code = rand(100000,999999);
        // dump($code);die;
        //把验证码发给邮箱
        //$body="您的验证码为:$code,5分钟内输入有效，请勿泄露于他人";
        $res=sendSms($user_tel,$code);
        // dump($res);die;

        if($res){
            $telInfo=['send_tel'=>$user_tel,'send_code'=>$code,'send_time'=>time()];
            // dump($telInfo);die;
            cookie('telInfo',$telInfo); 
            successful('发送成功');         
        }else{
            fail('发送失败');
        }
    }
    //手机号注册添加
    public function sendtelDo(){
        $data=input();
        $telInfo=cookie('telInfo');
        // 对$user_email进行验证  非空  邮箱格式 唯一性验证
        $reg="/^\d{11}$/";
        if(empty($data["user_tel"])){
            $this->error("手机号不能为空");die;
        }else if(preg_match($reg,$data["user_tel"])<1){
            $this->error("您的手机号不正确");die;
        }else{
            $user_model = new User;
            $where=[
                ["user_tel","=",$data["user_tel"]]
            ];
            $count = $user_model->where($where)->count();
            // echo $count;die; 
            if($count>0){
                $this->error("您的手机号已注册");die;
            }else if($telInfo["send_tel"]!=$data["user_tel"]){
                $this->error("您验证码手机号和当前手机号不一致");die;
            }
        }
        // 验证验证码
        if(empty($data["user_code"])){
            $this->error("验证码不能为空");die;
        }else if($telInfo["send_code"]!=$data["user_code"]){
            $this->error("验证码有误");die;
        }else if((time()-$telInfo["send_time"])>300){
            $this->error("您的验证码已失效,五分钟内有效");die;
        }
        // 验证密码
        $pwd_reg="/^\w{6,16}$/";
        if(empty($data["user_pwd"])){
            $this->error("密码不能为空");die;
        }else if(!preg_match($pwd_reg,$data["user_pwd"])){
            $this->error("密码必须是由数字,字母,下划线组成6-16位");die;
        }
        //验证确认密码
        if($data["user_pwd1"]!=$data["user_pwd"]){
            $this->error("密码不一致....");
        }
        //密码md5()加密
        $data["user_pwd"]=md5($data["user_pwd"]);
        // 时间处理
        $data["create_time"]=time();
        // 注册
        $user_model = new User;
        $data['user_time']=date("Y-m-d H:i:s",time());
        $ressave = $user_model->save($data);
        if($ressave){
            $this->success("注册成功",url("login/login"));
        }else{
            $this->error("注册失败",url("login/register"));
        }
    }
    //邮箱注册
    public function sendEmail(){
        //控制器获取邮箱的值
        $user_email=input('user_email');
        // dump($user_email);die;
        //对值进行验证 非空 唯一性
        $reg='/^[a-z0-9]{5,15}@[a-z1-9]{2,4}\.com$/';
        if(empty($user_email)){
            echo '邮箱名称不能为空';
            exit;
        }else if(preg_match($reg,$user_email)<1){
            echo '邮箱格式有误';
            exit;
        }else{
            $obj = new User;
            $where=[
                ['user_email',"=",$user_email]
            ];
            $res=$obj->where($where)->find();
            // dump($res);
            if($res){
                echo '邮箱已被注册';
                die;
            }
        }
        //随机出一个6位数验证码
        $code = rand(100000,999999);
        // dump($code);die;
        //把验证码发给邮箱
        $body="您的验证码为:$code,5分钟内输入有效，请勿泄露于他人";
        $res=sendEmail($user_email,'验证码',$body);
        // dump($res);die;

        if($res){
            $emailInfo=['send_email'=>$user_email,'send_code'=>$code,'send_time'=>time()];
            cookie('emailInfo',$emailInfo);
            successful('发送成功');
        }else{
            fail('发送失败');
            $emailInfo=['send_email'=>$user_email,'send_code'=>$code,'send_time'=>time()];
            cookie('emailInfo',$emailInfo);
        }
    }
    //邮箱注册添加
    public function registerDo(){
        $data=input();
        // dump($data);
        //验证邮箱
        cookie("emailInfo",['send_email'=>"123123@qq.com",'send_code'=>"123123",]);
        $emailInfo=cookie("emailInfo");
        $reg='/^[a-z0-9]{5,15}@[a-z1-9]{2,4}\.com$/';
        if(empty($data['user_email'])){
            $this->error('邮箱账号不能为空');
            exit;
        }else if(preg_match($reg,$data['user_email'])<1){
            $this->error('邮箱格式有误');
            exit;
        }else if($emailInfo['send_email']!=$data['user_email']){
            $this->error('邮箱账号不正确');
            die;
        }else{
            $obj = new User;
            $where=[
                ['user_email',"=",$data['user_email']]
            ];
            $res=$obj->where($where)->find();
            // dump($res);
            if($res){
                $this->error('邮箱已被注册');
                die;
            }
        }
        if(empty($data['user_code'])){
            $this->error('验证码不能为空');
            die;
        }else if($emailInfo['send_code']!=$data['user_code']){
            $this->error('验证码不正确');
            die;
        }
        $reg2='/^\w{6,16}$/';
        if(empty($data['user_pwd'])){
            $this->error('密码不能为空');
            die;
        }else if(preg_match($reg2,$data['user_pwd'])<1){
            $this->error('密码应为数字字母或下划线，6-16位');
            die;
        }else if(empty($data['user_pwd1'])){
            $this->error('确认密码不能为空');
            die;
        }else if($data['user_pwd']!=$data['user_pwd1']){
            $this->error('密码和确认密码不一致');
            die;
        }
        $data['user_pwd']=md5($data['user_pwd']);
        $data['user_time']=date("Y-m-d H:i:s",time());
        // echo 'OK';die;
        $obj = new User;
        $res=$obj->save($data);
        dump($res);
    }

   
}




?>