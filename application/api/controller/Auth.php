<?php
/**
 * Created by PhpStorm.
 * User: L丶lin
 * Date: 2019/5/6
 * Time: 14:35
 */
namespace app\api\controller;
use think\Cache;
use fast\Http;

class Auth 
{
    public function login($mobile,$code)
    {
        // 判断AccessToken 是否过期
        if(!Cache::get($mobile)) {
            $url = 'https://mobile.yangkeduo.com/proxy/api/login';
            $param = [
                'app_id' => '5',
                'mobile' => $mobile,
                'code'   => $code,
            ];
            $param = json_encode($param);
            $result = Http::post($url,$param);
            Cache::set($mobile,$result);
            halt($result);
        } else {
            return Cache::get($mobile);
        }
    }

    public function addAddress($mobile,$accessToken)
    {
        if(Cache::get($mobile.'_'.'address_id')) {
           return  Cache::get($mobile.'_'.'address_id');
        }
        $url = 'https://mobile.yangkeduo.com/proxy/api/api/origenes/address';
        $param = [
            'name'  => '李琳',
            'mobile' => $mobile,
            'province_id' => 16,
            'city_id'     => 220,
            'district_id' => 1834,
            'address'     => '雨花台区安德门四季阳光花园',
            'is_default'  => "0",
        ];
        $param = json_encode($param);
        $result = Http::post($url,$param,[],$accessToken);
        $array = json_decode($result,true);
        Cache::set($mobile.'_'.'address_id',$array['address_id']);
        return $array['address_id'];
    }

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
        if(isset($result['error_code'])) {
            return false;
        }
        return $result;
    }

    public function auth_pay()
    {
        // 判断请求该某组某用户执行
        $user_info = $this->getNextBuyer();
        if($user_info == false) {
            return json_encode([
                'code' => '401',
                'msg'  => '分组用户已轮询完毕，需重新开启!',
            ]);die;
        }
        $user = $user_info['user'];
        $group_id = $user_info['group_id'];
        // 获取商品详情
        $price = 100;
        $store = $this->getStore($price,$group_id);
        if($store == false) {
            return json_encode([
                'code' => '401',
                'msg'  => '商品金额参数不合规范! --'. $pirce,
            ]);
        }
        // 创建订单
        $order = $this->order($user['address_id'],$store,$user['access_token']);
        if($order == false) {
            M('buyer')->where('id',$user['id'])->save([
                    'status' => '0',
                    'status_msg' => '用户异常创建订单!',
                ]);
            return json_encode([
                'code' => '401',
                'msg'  => '用户异常创建订单!',
            ]);
        }
        // 获取order支付地址
        // 支付宝 app_id = 9  微信 app_id = 38
        $app_id = 38;
        $pay_path = $this->prepay($order['order_sn'],$app_id,$accessToken); // 获取支付信息
        return $pay_path;
    }

    // 获取当前请求执行用户
    public function getNextBuyer()
    {
        $model = M('config');
        // 查询目前轮次分组
        $info = $model->where('name','group')->find();
        if($info == null) { // 第一次轮询没有轮次 需创建
            $group = db('buyer_group')->where('status','1')->find();
            if($group == null) {
                return false;
            }
            $param = [
                'name'  => 'group',
                'value' => $group['id'],
                'info'  => (int)$group['aim_amount'],
                'cate'  => 0,

            ];
            $model->insert($param);
            $info = $param;
        }
        
        // 判断目标金额是否大于实际金额
        if(($info['info'] > $info['cate']))  { 
            // 找出同组下一位执行用户
            $last_user = $model->where('name','last_user')->find(); 
            $where['group'] = $info['value'];
            $where['id'] = ['gt',$last_user['value']];
        } else {
            // 找出下一组。替换 config 表
            $group = db('buyer_group')->where('id','>',$info['value'])->where('status','1')->find();
            $model->where('name','group')->save([
                'value' => $group['id'],
                'info'  => $group['aim_amount'],
                'cate'  => 0,
            ]);
            $where['group'] = $group['id'];
        }
        // 查询出满足条件的用户
        $user = db('buyer')->where($where)->where('status','1')->find();
        if($user == null && isset($where['id'])) {
            unset($where['id']);
            $user = db('buyer')->where($where)->where('status','1')->find();
        } 

        if($user == null) {
            return false;
        } else {
            if($last_user['value'] != $user['id']) {
                $model->where('name','last_user')->save(['value'=>$user['id']]);
            }
            return [
                'user' => $user,
                'group_id' => $where['group'],
            ];
        }

    }

    public function getStore($price,$group_id)
    {
        $model = M('goods_sku');
        $group = M('buyer_group')->where('id',$group_id)->find();
        $goods_ids = M('goods')->where('shop_id',$group['shop_id'])->getField('goods_id',true);
        $info = $model->where('amount',$price)->where('goods_id','in',$goods_ids)->find();
        $num = 1;
        if($info == null) {
            $remainder = $price % 1000;
            if($remainder > 0) {
                // 有余数使用500取余
                $remainder = $price % 500;
                if($remainder > 0 ) {
                    return false;
                } else {
                    $num = $price / 500;
                    $info = $model->where('amount',500)->find();
                }
            } else {
                    $num = $price / 1000;
                    $info = $model->where('amount',1000)->find();
            }
        }
        
        if($info == null) {
            return false;
        }

        return compact('num','info');
    }



    public function getStoreA()
    {
        $model = M('goods_sku');
        $price = I('post.price');
        echo $price % 1000;
        echo "<br/>";
        echo $price & 500;die;
        return $info;
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