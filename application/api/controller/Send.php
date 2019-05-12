<?php
namespace app\api\controller;

use fast\Http;
use kbw\DJYClient;
use kbw\OrderSubmitRequest;
use kbw\rOrder;
use kbw\Order;
use app\api\controller\PddLogistics;

class Send
{
	protected $appKey = '23653137271';
	protected $appSecret = 'aa123123';
	protected $ceshi_url = 'http://ceshi.kongbao100.com/OrderSubmit/index.asp';
	protected $url = 'http://www.kongbao100.com/OrderSubmit/index.asp';
	protected $pdd_url = 'http://gw-api.pinduoduo.com/api/router';

	// 获取空包余额
	public function getBalance()
	{
		$client = new DJYClient($this->appKey,$this->appSecret,$this->url);
		// 测试环境
		$client->isDebug=false;

		$request = new OrderSubmitRequest();
		$request->setLogiType("getuseramount"); // api名称

		$result = $client->execute($request);
		$result_arr = json_decode($result,true);
		if($result_arr['code'] == 0) {
			return $result_arr['Data'];
		} else {
			return false;
		}
	}

	// 获取发件人地址
	public function getShippingAddress()
	{
		$secret = '7956b0c2c8ef9132d50382d0052f513e9e72d841';
		$param = [];
		$param = $this->markSign($param,$secret);
		$result = Http::post($this->pdd_url.'?'.http_build_query($param),$param);
		$result = json_decode($result,true);
		if(isset($result['error_code'])) {

		} else {
			$addressList = $result['refund_address_list_get_response']['refund_address_list'];
			$shippingAddress = reset($addressList);
			return $shippingAddress;
		}
	}

	public function markSign($ortherParam = [],$secret)
	{
		$param = [
			'type'	=> 'pdd.refund.address.list.get',
			'client_id' => 'a45c23e10b4246588130bae460d20b0b',
			'access_token' => '4161dcaa131d40f598eb5dde72708ebf532479e8',
			'timestamp' => time(),
		];
		// 如果有其他参数 array_merger($commonParam,$ortherParam);
		if(!empty($ortherParam)) {
			$param = array_merge($param,$ortherParam);
		}
		ksort($param);
		$str = $secret;
		foreach ($param as $key => $value) {
			$str .= $key.$value;
		}
		$sign = strtoupper(MD5($str.$secret));
		$param['sign'] = $sign;
		return $param;
	}

	public function sendOrder()
	{
		$model = M('order');
		$group_id = I('group_id','1');
		// 获取需要发货的订单
		$list = $model->alias('o')->field('o.status,o.shop_id,o.group,o.is_deliver_goods,o.received_time,o.uid,o.order_sn,u.account,u.name,u.province,u.city,u.county,u.address')
			->join('buyer u','o.uid = u.id')
			->where([
				'o.group'  => $group_id,
				'o.status' =>  2,
				'o.is_deliver_goods' => 0, // 未发货
				'o.received_time' => ['eq',0]
			])->where('o.id', 42)->select();

		$shop_id = reset($list)['shop_id'];
		$array_list = array_chunk($list,'10');
		// 获取发货人信息
		$shippingAddress = $this->getShippingAddress();
		// 发货接口类
		$PddLogistics = new PddLogistics();
		$order_list_success = []; // 发货成功订单
		$order_list_error = [];
		// 拼接收货人信息
		foreach ($array_list as $key => $value) {
			// 10件快递信息和成一条发单号
			$invoice = $this->createShippingNumber($value,$shippingAddress);
			if($invoice != false) {
				foreach ($invoice as $k => $v) {
					// 将每个小组10件物品发件
					$pddParam = [
						'shop_id' => $shop_id,
						'order_sn' => $v['OrderNo'],
						'Trackingno' => $v['Trackingno'],
					];
					$result = $PddLogistics->pddLogisticsOnlineCreate($pddParam);
					if($result['code'] == 0) {
						$order_list_success[] = $pddParam['order_sn'];
					} else {
						$order_list_error[] = $pddParam['order_sn'];
					}
				}
			}
		}

		// 修改执行成功的订单号，状态改为已发货
		if(!empty($order_list_success)) {
			M('order')->where(['order_sn'=>'in',$order_list_success])->save(['is_deliver_goods'=>'1']);
		}
	}

	public function pddShipping($array,$shippingNumber)
	{

	}

	public function test01()
	{
		new OrderSubmitRequest();
		$rorder = new rOrder();
		var_dump($rorder);
	}

	public function createShippingNumber($array,$shippingAddress)
	{
		$client = new DJYClient($this->appKey,$this->appSecret,$this->url);
		// 测试环境
		$client->isDebug=false;
		// 收货人订单组
		$orders = [];
		new OrderSubmitRequest();
		foreach ($array as $key => $value) {

			$rorder = new rOrder();
			$rorder->OrderNo = $value['order_sn'];//订单号 只能包含大小写字母， 数字和-（减号），且字符串长度不能大于35
			$rorder->Contact = $value['name'];//收件人名称 字符串长度不能大于30
			$rorder->OfficePhone= $value['account'];//收件人电话 字符串长度不能大于20
			$rorder->CellPhone= $value['account'];//收件人手机 有效的11位手机号码 收件人手机和电话至少填一项
			$rorder->State=$value['province'];//收件人所在省 字符串长度不能大于20
			$rorder->City=$value['city'];//收件人所在市 字符串长度不能大于20
			$rorder->District= $value['county'];//收件人所在县/区 字符串长度不能大于20
			$rorder->Address=$value['address'];//收件人详细地址 字符串长度不能大于100
			array_push($orders,$rorder);
		}

		// 发货人信息
			$order=new Order();
			$order->Platform="拼多多国通"; //订单来源 可选值：圆通  天天
			$order->SendContact= $shippingAddress['refund_name'];//发件人名称 字符串长度不能大于30 
			$order->SendOfficePhone= $shippingAddress['refund_phone'];//发件人电话 字符串长度不能大于20
			$order->SendCellPhone= $shippingAddress['refund_phone'];//发件人手机 有效的11位手机号码 收件人手机和电话至少填一项
			$order->UserId = $this->appKey;//网站的用户id号，必须每个用户只有一个id且唯一，这个是为了快递帮我们排查 骗子
			$order->CellPhone = $shippingAddress['refund_phone'];//发件人手机
			$order->SendState= $shippingAddress['province_name'];//发件人所在省 字符串长度不能大于20
			$order->SendCity= $shippingAddress['city_name'];//发件人所在市 字符串长度不能大于20
			$order->SendDistrict= $shippingAddress['district_name'];//发件人所在县/区 字符串长度不能大于20
			$order->SendAddress= $shippingAddress['refund_address'];//发件人详细地址 字符串长度不能大于100
			$order->ProductTitle= '私密';//商品名称 字符串长度不能大于100
			$order->Weight="1.0";//包裹重量 两位小数, 0.05kg-40kg之间
			$order->Raddress=$orders;


		$request = new OrderSubmitRequest();
		$request->setLogiType("buykongbao"); // API名称
		$request->setOrders($order);
		$result = $client->execute($request);
		$result_arr = json_decode($result,true);
		if($result_arr['code'] == '0') {
			return $result_arr['Data'];
		} else {
			return false;
		}
	}
} 