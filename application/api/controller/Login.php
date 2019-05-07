<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\Cache;
use think\db\Query;
use JMessage\JMessage;
use JMessage\IM\User;

class Login extends Common{
    //模拟地址 http://localhost/llb/index.php/api/login/login
	//public $uid = 1;//测试用
    //测试时关闭token验证
    public function _initialize(){}  //关闭时调用父类检验token
    /*
    登录
     */
    public function login(){
    	if (IS_POST) {
    		$type = I('post.type');
    		if (!$type) {
    			$this->apiReturn("无登录类型",'402',"无登录类型");
    		}
    		switch ($type) {
    			case 'sms':  //短信验证码登录
    				$account = I('post.account');
                    $field = 'password,create_time';//隐藏字段
    				$res = M('user')->where('account',$account)->field($field,true)->find();
    				if ($res) {
    					$uid = $res['uid'];
    					$token = $this->createToken($account,$uid);//创建加密token，身份令牌
    					$data['uid'] = $uid;
    					$data['token'] = $token;
    					if ($this->clearToken($uid)) {//更新Token，保证用户无法多处登录
    						M('token')->add($data);//新token添加到数据库
    					}
                        $res['token'] = $token;
    					Cache::set($use_token,$res);//将用户信息存入以token为key的缓存中
                        // dump(Cache::get($token));
    					$result['token'] = $token;
                		$result['uid'] = $uid;
                		$result['status'] = $res['status'];
    					$this->apiReturn("登录成功",'200',$result);
    				}else{
    					$this->apiReturn("用户不存在",'400',"用户不存在");
    				}
    				break;

				case 'pas':  //密码登录
    				$account  = I('post.account');
    				$pwd      = I('post.password');
            		$password = md5($pwd.C('AUTH_CODE'));//加密,撒盐
    				$where['account']  = $account;
    				$where['password'] = $password;
    				$res = M('user')->field('uid,status')->where($where)->find();
    				if ($res) {
    					$uid = $res['uid'];
    					$token = $this->createToken($account,$uid);//创建加密token，身份令牌
    					$data['uid'] = $uid;
    					$data['token'] = $token;
    					if ($this->clearToken($uid)) {//更新Token，保证用户无法多处登录
    						M('token')->add($data);//新token添加到数据库
    					}
    					// $token = $token;f7c94578f8e9d7863ecd962273f5cd3e
                        $user['token'] = $token;
                        $user['uid'] = $res['uid'];
                        // Cache::set($use_token,$user);//将用户信息存入以token为key的缓存中
                        Cache::set('token',$user,3600);
                        // $qwe = Cache::get('token');
                        // dump($qwe);
    					$result['token'] = $token;
                		$result['uid'] = $uid;
                		$result['status'] = $res['status'];
    					$this->apiReturn("登录成功",'200',$result);
    				}else{
    					$this->apiReturn("用户不存在",'400',"用户不存在");
    				}
    				break;
    		}
    	}else{
    		$this->apiReturn("请求类型错误",'415',"请求类型错误");
    	}
    }
    /*
    注册
     */
    public function register(){
        if(IS_POST){
            $account = I('post.account');//账号
            $password = I('post.password');//密码
            $invite_code = I('post.invite_code');//邀请码
            $user = M('user');
            $count = $user->where('account',$account)->count();
            if($count){
                $this->apiReturn("该账号已注册",'400',"该账号已注册");
            }else{
                $head_pic = '/llb/public/upload/user/head_pic/boy.jpg';
                $data = array(
                    'account' => $account,
                    'password' => md5($password.C('AUTH_CODE')),
                    'time' => time(),
                    'head_pic' => $head_pic,
                );
                $result = $user->data($data)->add();
                if($result){
                    $pwd = md5($password.C('AUTH_CODE'));//加密,撒盐
                    $where['account']  = $account;
                    $where['password'] = $pwd;
                    $res = M('user')->field('uid,status')->where($where)->find();
                    $uid = $res['uid'];
                    $token = $this->createToken($account,$uid);//创建加密token，身份令牌
                    $res_data['uid'] = $uid;
                    $res_data['token'] = $token;
                    if ($this->clearToken($uid)) {//更新Token，保证用户无法多处登录
                        M('token')->add($res_data);//新token添加到数据库
                    }
                    //判断邀请码
                    if ($invite_code) {
                        $code_where['code'] = $invite_code;
                        $code_where['status'] = 1;
                        $code_count = M('invite_code')->where($code_where)->find();
                        if ($code_count) {
                            $record_data = array(
                                'uid' => $code_count['uid'],
                                'record' => '推荐社员注册成功获得1000功分',
                                'increase' => 1000,
                                'time' => time(),
                                'status' => 0
                            );
                            M('integral_record')->add($record_data);
                            // M('invite_code')->where('id',$code_count['id'])->save(['status'=>0]);
                            M('user')->where('uid',$code_count['uid'])->setInc('integral',1000);
                        }else{
                            $this->apiReturn("邀请码不可用",'402',"邀请码不可用");
                        }
                    }
                    $user_info['token'] = $token;
                    $user_info['uid'] = $res['uid'];
                    Cache::set('token',$user_info,3600);
                    $result_1['token'] = $token;
                    $result_1['uid'] = $uid;
                    $result_1['status'] = $res['status'];
                    $client = new JMessage(C('JIGUANG.APPKEY'),C('JIGUANG.MASTERSECRET'));
                    $user1 = new User($client);
                    $response = $user1->register($account, 'llb2580.');
                    $response_1 = $user1->update($account, ['address' => $uid]);
                    if ($response['http_code'] == 201 && $response_1['http_code'] == 204) {
                        $this->apiReturn("注册成功",'200',$result_1);
                    }
                    else{
                        $this->apiReturn("注册失败",'401',"注册失败");
                    }
                }else{
                    $this->apiReturn("注册失败",'401',"注册失败");
                }
            }
        }else{
            $this->apiReturn("错误的请求类型",'415',"错误的请求类型");
        }
    }
    /*
    忘记密码
     */
    public function forgetPwd(){
        if(IS_POST){
            $account = I('post.account');//账号
            $newpwd  = I('post.newpwd');//新密码
            $exist = M('user')->where("account=".$account)->find();//查找该账号是否存在
            if($exist == Null){
                $this->apiReturn("账号不存在",'400',"账号不存在");
            }
            // $password = md5($pwd.C('AUTH_CODE'));//加密,撒盐
            $data['password'] = md5($newpwd.C('AUTH_CODE'));//新密码加密
            $result = M('user')->where("uid=".$exist['uid'])->save($data);//保存至数据库
            if($result){
                $this->apiReturn("密码重置成功",'200',"密码重置成功");
            }else{
                $this->apiReturn("密码重置失败",'401',"密码重置失败");
            }
        }else{
            $this->apiReturn("错误的请求类型",'415',"错误的请求类型");
        }
    }

}