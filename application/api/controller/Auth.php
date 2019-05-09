<?php
/**
 * Created by PhpStorm.
 * User: L丶lin
 * Date: 2019/5/6
 * Time: 14:35
 */
namespace app\api\controller;
use fast\Http;

class Auth 
{
    public function prepay($order_sn,$app_id,$accessToken)
    {
        $url = 'https://mobile.yangkeduo.com/proxy/api/order/prepay';

        $param = [
            'order_sn' => $order_sn,
            'version'  => 3,
            'attribute_fields'      => [
                    'paid_times' => 0,
                    'forbid_contractcode' => '1',
                    'forbid_pappay'   => '1',
            ],
            'return_url' => 'http://mobile.yangkeduo.com/transac_wappay_callback.html?order_sn=190507-070101238553922&prepay_type=',
            'app_id' => $app_id
        ];
        $param = json_encode($param);
        $result = Http::post($url,$param,[],$accessToken);
        return $result;
    }


    public function order($address_id,$goods,$accessToken)
    {
        $param = [
            'address_id'    =>  $address_id,  //"7213131344",
            'goods' => [
                [
                    'sku_id' => $goods['info']['sku_id'],
                    'sku_number' => $goods['num'],
                    'goods_id' => $goods['info']['goods_id'],
                ]
            ],
            'group_id' => $goods['info']['group_id'],//$group_id, //"16886516",
            'pay_app_id' => 0

        ];

        $url = 'https://mobile.yangkeduo.com/proxy/api/order';
        $param = json_encode($param);
        $result = Http::post($url,$param,[],$accessToken);
        $result = json_decode($result,true);
        return $result;
    }

   // 获取当前请求执行用户
    public function getNextBuyer()
    {
        $config = M('config');
        $group = M('buyer_group');
        $buyer = M('buyer');
        // 查询目前轮次分组
        $info = $config->where(['name'=>'group'])->find();
        $group_id = $info['value'];
        if($info == null) { // 第一次轮询没有轮次 需创建
            $new_group = $group->where(['status'=>'1'])->find();
            if($new_group == null) { // 没有正常状态的分组
                $msg = [
                    'msg'  => '没有可轮询的分组!',
                    'code' => '0', 
                ];
                return $msg;
            }
            $param = [
                'name'  => 'group',
                'value' => $new_group['id'],
                'info'  => (int)$new_group['aim_amount'],
                'cate'  => 0,

            ];
            $config->insert($param); // 第一次轮询，记录当前分组
            $info = $param;
            $group_id = $new_group['id'];
        }
        
        // 判断目标金额是否大于实际金额
        if(($info['info'] > $info['cate']))  { 
            // 找出同组下一位执行用户
            $last_user = $config->where(['name'=>'last_user'])->find(); 
            if($last_user == null) {
                // 第一次分组用户轮询
                $user = $buyer->where(['status'=>'1'])->where(['group'=>$info['value']])->find();
                if($user != null) {
                    $config->insert([
                        'name'  => 'last_user',
                        'value' => $user['id'],
                    ]);
                } else {
                    $where['group'] = $info['value'];
                }
            } else {
                $where['group'] = $info['value'];
                $where['id'] = ['gt',$last_user['value']];
            }
        } else {
            // 找出下一组。替换 config 表
            $new_group = $group->where(['id'=>['>',$info['value']]])->where(['status'=>'1'])->find();
            $group_id = $new_group['id'];
            $config->where(['name'=>'group'])->save([
                'value' => $new_group['id'],
                'info'  => $new_group['aim_amount'],
                'cate'  => 0,
            ]);
            $where['group'] = $new_group['id'];
        }
        // 查询出满足条件的用户
        if(!isset($user)) {
            $user = $buyer->where($where)->where(['status'=>'1'])->find();
        }

        
        if($user == null && isset($where['id'])) {
            unset($where['id']);
            $user = $buyer->where($where)->where(['status'=>'1'])->order('id asc')->find();
        } 

        if($user == null) {
            // 该组没有正常用户
            // 将改组状态修改为false 并且删除上一次执行的用户
            // halt($group_id);
            $group->where(['id'=>$group_id])->save(['status'=>'0']);
            // 查询下个正常组
            $success['status'] = '1';
            $success['id'] = ['gt',$group_id];
            $success_group = $group->where($success)->find();
            if($success_group == null) {
                // 没有正常的组
                $config->where(['name'=>'group'])->delete();
                $config->where(['name'=>'last_user'])->delete();
                $msg = [
                    'msg' => '无正常分组!',
                    'code'=> '0',
                ];
                return $msg;
            } else {
                // 修改config 文件
                $config->where(['id'=>$info['id']])->save([
                     'value' => $success_group['id'],
                     'info'  => $success_group['aim_amount'],
                     'cate'  => 0,   
                ]);
                // 直接删除 config 下 last_name;
                $config->where(['name'=>'last_name'])->delete();
                // 重新执行本方法找出user
                return $this->getNextBuyer();
            }
        } else {
            if($last_user['value'] != $user['id']) {
                $config->where(['name'=>'last_user'])->save(['value'=>$user['id']]);
            }
            $user['group_id'] = $group_id;
            return $user;
        }

    }

    public function getStore($price,$group_id)
    {
        $model = M('goods_sku');
        $group = M('buyer_group')->where(['id'=>$group_id])->find();
        $goods_ids = M('goods')->where(['shop_id'=>$group['shop_id']])->getField('goods_id',true);
        $shop_id = $group['shop_id'];
        $info = $model->where(['amount'=>$price])->where(['goods_id'=>['in',$goods_ids]])->find();
        $num = 1;

        if($info == null) {
            $price_arr = [2500,2000,1000,500];
            foreach ($$price_arr as $key => $value) {
                $remainder = $price % $value;
                if($remainder == 0) {
                    $info = $model->where(['amount'=>$value])->find();
                    $num = $price / $value;
                    break;
                }
            }
        }

        if( !isset($info) || $info == null) {
            return false;
        }

        return compact('num','info','shop_id');
    }


    public function getParam($price = '200')
    {
        $user = $this->getNextBuyer();
        // halt($user);
        $goods = $this->getStore($price,$user['group_id']);
        $order = [
            'uid' => $user['id'],
            'group' => $user['group_id'],
            'shop_id' => $goods['shop_id'],
            'goods_id' => $goods['info']['goods_id'],
            'order_time' => time(),
        ];
        $array = compact('user','goods','order');
        $msg = ['code' => '200','msg'=>'ok','data'=>$array];
        // halt($msg);
        return $msg;
    }

    public function orderAdd($param)
    {
    	// param = getParam[data][order]
    	$model = M('order');
    	$order_id = $model->insert($param);
    }

    public function send($user,$goods)
    {
    	// $param = $this->getParam('200');
    	// $user = $param['data']['user'];
    	// $goods = $param['data']['goods'];
        // 创建订单
        $order = $this->order($user['address_id'],$goods,$user['access_token']);
        if(isset($order['error_code'])) {
            M('buyer')->where('id',$user['id'])->save([
                    'status' => '0',
                    'status_msg' => $order['error_code'] == 40001 ? 'AccessToken 失效' :'用户创建订单失败!',
                ]);

            return [
                'code' => 40001,
                'msg'  => '用户创建订单失败',
                'data' => [],
            ];
        }
        // 获取order支付地址
        // 支付宝 app_id = 9  微信 app_id = 38
        $app_id = 9;
        $pay_path = $this->prepay($order['order_sn'],$app_id,$user['access_token']); // 获取支付信息

        $result = json_decode($pay_path,true);
        if(isset($result['error_code'])) {
            return [
                'code' => $result['error_code'],
                'msg'  => '用户获取支付信息失败',
                'data' => [],
            ];
        }
        $url = http_build_query($result['query']);
        $url = $result['gateway_url'] . '?' .$url;
        $res = [
            'code'  => '200',
            'msg'   => 'ok',
            'data'  => [
            	'result' => $result,
            	'url'    => $url
            ]
            
        ];
        return $res;
    }



    public function getRegions()
    {
        $id = I('get.id');
        $id = empty($id) ? '1' : $id;
        $url = 'http://mobile.yangkeduo.com/proxy/api/api/galen/v2/regions/'.$id;
        $token = I('token');
        // $token = 'A7SXZ22RY2KJTV7HVKX6DK2S7DE3C2WOVNPJXISMUNH3EDGPZ3RQ101a883'; 
        $result = Http::get($url,'','',$token);
        $array = json_decode($result,true);

        $data = [
            'result' => $array,
            'msg'    => '',
            'code'   => '1',
        ];
        if(isset($array['error_code'])) {
            $data['msg'] = 'AccessToken 失效';
            $data['code'] = '2';
            return json_encode($data);
        } else {
            return json_encode($data);
        }

    }
}