<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use think\Paginator;
use think\Db;
use think\db\Query;
use think\Model;

class Order extends Base
{
	public function index()
	{
		$where = [];
		$where_list = [];
		$shop_id = I('shop_id','');
		if ($shop_id) {
            $where['shop_id'] = $shop_id;
            $where_list['o.shop_id'] = $shop_id;
        }
        $pay_memberid = I('pay_memberid','');
		if ($pay_memberid) {
            $where['pay_memberid'] = $pay_memberid;
            $where_list['o.pay_memberid'] = $pay_memberid;
        }
        $pay_orderid = I('pay_orderid','');
		if ($pay_orderid) {
            $where['pay_orderid'] = $pay_orderid;
            $where_list['o.pay_orderid'] = $pay_orderid;
        }
        $out_trade_no = I('out_trade_no','');
		if ($out_trade_no) {
            $where['out_trade_no'] = $out_trade_no;
            $where_list['o.out_trade_no'] = $out_trade_no;
        }
        $order_sn = I('order_sn','');
		if ($order_sn) {
            $where['order_sn'] = $order_sn;
            $where_list['o.order_sn'] = $order_sn;
        }
        $status = I('status','');
        $curZfType = -1;
		if ($status != '') {
			switch ($status){
		        case "0":
		            //未支付
		            $where['status'] = 0;
		            $where_list['o.status'] = 0;
		            $curZfType = 0;
		            break;
		        case "1,2":
		            //成功订单
		        	$where['status'] = 1;
		            $where_list['o.status'] = ['in','1,2'];
		            $curZfType = 3;
		            break;
		        case "1":
		            //支付未回调
		            $where['status'] = 1;
		            $where_list['o.status'] = 1;
		            $curZfType = 1;
		            break;
		        case "2":
		            //支付已回调
		            $where['status'] = 2;
		            $where_list['o.status'] = 2;
		            $curZfType = 2;
		            break;
		        default:
		            //查询所有订单状态
		            break;
		    }
        }
        // 查实已确认收货订单
        $received_time = I('post.received_time','');
        $page_where['received_time'] = I('received_time', '');
        if($received_time == '1') {
            $where['received_time'] = ['gt',1];
            $where_list['received_time'] = ['gt',1];
        } elseif($received_time == '2') {
            $where['received_time'] = ['eq',0];
            $where_list['received_time'] = ['eq',0];
        }

        $this->assign('received_time',$received_time);
        $start_datetime = I('start_datetime', '');
        $end_datetime = I('end_datetime', '');
        $page_where['start_datetime'] = I('start_datetime', '');
        $page_where['end_datetime'] = I('end_datetime', '');

        if( ($start_datetime && !strpos($start_datetime, "ed"))  && ($end_datetime && !strpos($end_datetime, "ed"))  ) {
    		$where['order_time'] = array(array('egt',strtotime($start_datetime)),array('elt',strtotime($end_datetime)));
    		$where_list['o.order_time'] = array(array('egt',strtotime($start_datetime)),array('elt',strtotime($end_datetime)));
		}else{
			$start_datetime = date('Y-m-d')." 00:00:00";
        	$end_datetime = date('Y-m-d')." 23:59:59";
        	// $where['order_time'] = array(array('egt',strtotime($start_datetime)),array('elt',strtotime($end_datetime)));
        	// $where_list['o.order_time'] = array(array('egt',strtotime($start_datetime)),array('elt',strtotime($end_datetime)));
		}

        // 买家手机号搜索
        $mobile = I('account_phone');
        if($mobile) {
            $where_list['u.account'] = $mobile;
            $this->assign('account_phone',$mobile);
        }

 		$order = M('Order');
 		$shop = M('shop');
        // 处理分页传参
        $page_where = array_merge($where, $page_where);
        unset($page_where['order_time']);
        $list = $order
        			->alias('o')
        			->field("o.id,o.uid,o.received_time,o.shop_id,o.pay_memberid,o.pay_orderid,o.out_trade_no,o.pay_productname,o.order_amount,o.pay_poundage,o.pay_actualamount,o.order_time,o.pay_time,o.status,s.name as shop_name,o.order_sn,u.account as account_phone")
        			->join('shop s','o.shop_id = s.id')
                    ->join('buyer u','o.uid = u.id')
        			->where($where_list)
        			->order('o.id desc')
        			->paginate(25,false,['query'=>$page_where]);
        // 店铺
        $shop_list = $shop->select();
        // 计算所有订单的总值
        $order_total = $order->where($where)->field('count(*)as total_order,sum(order_amount)as total_amount,sum(pay_poundage)as total_poundage,sum(pay_actualamount)as total_actualamount')->find();

        $where['status'] = ['in','1,2'];
        $successOrderCount = $order->where($where)->count();
        // 注意 100比 要比100要大所以用101
        //*100.0000
        $zflv = 0;
        if($successOrderCount>0 && $order_total['total_order']>0)
        {
	        $zflv =  fmod(  (floatval($successOrderCount) / floatval($order_total['total_order']) ) ,101.0000)*100.0000;
	        $zflv > 100.0000 ? $zflv= 100.0000:$zflv;
	        $zflv = number_format($zflv, 2);
	    }

        // 统计同条件下，确认收货订单数量
        // $received_order = $order->where($where)->where(['received_time'=>['gt',0]])->count();
        $this->assign('list', $list);
        $this->assign('shop_list', $shop_list);
        $this->assign('shop_id', $shop_id);
        $this->assign('pay_memberid', $pay_memberid);
        $this->assign('pay_orderid', $pay_orderid);
        $this->assign('out_trade_no', $out_trade_no);
        $this->assign('order_sn', $order_sn);
        $this->assign('curZfType', $curZfType);
        $this->assign("start_datetime", $start_datetime);
        $this->assign("end_datetime", $end_datetime);
        $this->assign($order_total);
        // $this->assign("total_poundage", $total_poundage);
        // $this->assign("total_actualamount", $total_actualamount);
        $this->assign("success_order", $successOrderCount);
        // $this->assign('received_order',$received_order);
        $this->assign("zflv", $zflv);
		return $this->fetch();
	}

	// 显示通道统计页面
    public  function count()
    {
        $type = I('type', '', 'trim');
        $shop_id = I('shop_id', '', 'trim');
        if($type == "yesterday") {
            // 查询昨日数据
            $yday =date('Y-m-d',strtotime("-1 day"));
            $start_datetime = $yday." 00:00:00";
            $end_datetime = $yday." 23:59:59";
            $title = "昨日";
        }else{
            // 查询今日数据
            $start_datetime = date('Y-m-d')." 00:00:00";
            $end_datetime = date('Y-m-d')." 23:59:59";
            $title = "今日";
        }
        $where = [];
        $where_shop = [];
        $where_count = [];
        // 所有成功订单数据
        $where['status'] = ['in','1,2'];
        // 日期和时间是用来查询订单的
        if( ($start_datetime && !strpos($start_datetime, "ed"))  && ($end_datetime && !strpos($end_datetime, "ed"))  ) {
            $where['order_time'] = array(array('egt',strtotime($start_datetime)),array('elt',strtotime($end_datetime)));
            $where_count['order_time'] = array(array('egt',strtotime($start_datetime)),array('elt',strtotime($end_datetime)));
        }

        if( !empty($shop_id) ){
            $where_shop['id'] = $shop_id;
            $where['shop_id'] = $shop_id;
        }
        $shop_data = M('Shop')->where($where_shop)->select();
        $list = [];
        $Order = M('Order');
        //时间范围内所有通道订单金额总值
        $total_amount = $Order->where($where)->sum('order_amount');
        //时间范围内所有通道总手续费 
        $total_poundage = $Order->where($where)->sum('pay_poundage');
        // 时间范围内所有通道总实际金额
        $total_actualamount = $Order->where($where)->sum('pay_actualamount');
        //成功订单总数
        $total_order = $Order->where($where)->count();

        if($shop_data)
        {
        	foreach ($shop_data as $val )
        	{
        		// 成功订单总数
        		$where['shop_id'] = $val['id'];
                $successOrderCount =$Order->where($where)->count();
                // 计算该通道订单的总值
                $shop_amount = $Order->where($where)->sum('order_amount');
                // 总手续费
                $shop_poundage= $Order->where($where)->sum('pay_poundage');
                // 实际总额
                $shop_actualamount= $Order->where($where)->sum('pay_actualamount');
                //总订单
                $where_count['shop_id'] = $val['id'];
                $totalOrderCount =$Order->where($where_count)->count();
                // print_r($totalOrderCount);die;
                $zflv = 0;
                if($successOrderCount>0 && $totalOrderCount>0)
                {
                	$zflv = fmod((floatval($successOrderCount) / floatval($totalOrderCount)),101.0000)*100.0000;
        			$zflv > 100.0000 ? $zflv = 100.0000:$zflv;
        			$zflv = number_format($zflv, 2, '.', '');
                }
 				
        		$list[] = array(
                    'shop_id' => $val['id'],
                    'name' => $val['name'],
                    'successOrderCount'=> $successOrderCount,
                    'amount' => $shop_amount ? $shop_amount:0,
                    'poundage' => $shop_poundage? $shop_poundage:0,
                    'actualamount' => $shop_actualamount? $shop_actualamount:0,
                    'zflv' => $zflv,
                );
        	}
        }
        $this->assign("list", $list);
        $this->assign("start_datetime", $start_datetime);
        $this->assign("end_datetime", $end_datetime);
        $this->assign("shop_id",$shop_id);
        $this->assign("total_amount",$total_amount);
        $this->assign("total_order",$total_order);
        $this->assign("total_poundage",$total_poundage);
        $this->assign("total_actualamount",$total_actualamount);
        $this->assign("title",$title);
        return $this->fetch();
    }

}