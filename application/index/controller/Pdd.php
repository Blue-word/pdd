<?php
namespace app\index\controller;
use think\Controller;
use fast\Http;

class Pdd extends Base{

	public function shop_list()
	{
		$model = M('shop');
		$list = $model->where(['status'=>'1'])->paginate(25);
		$this->assign('list',$list);
		return $this->fetch();
	}

    // 新增
    public function shop_insert(){
        // $this->display('form');
        return $this->fetch();
    }

    public function shop_add(){
	    $Model_Data = M('shop');
	    $post = I('post.');
	    if(isset($post['id'])) {
	    	if($Model_Data->where('id',$post['id'])->save($post)){
	        	$this->success('编辑成功!',U('shop_list'));
		    }else{
		        $this->error($Model_Data->getError());
		    }
	    }
	    if($Model_Data->insert($post)){
	        $this->success('新增成功!',U('shop_list'));
	    }else{
	        $this->error($Model_Data->getError());
	    }
    }

    public function shop_update()
    {
    	$id = I('get.id');
    	$info = M('Shop')->where('id',$id)->find();
    	$this->assign('info',$info);
    	return $this->fetch();
    }

	public function shop_handle(){
        $data = I('post.');
        $res = M('shop')->where('id',$data['id'])->save(['status'=>$data['status']]);
        if($res){
            $this->success("操作成功",U('shop_list'));
        }else{
            $this->error("操作失败",U('shop_list'));
        }
    }

    public function shop_del()
    {
    	$id = I('get.id');
    	if(M('Shop')->where('id',$id)->delete()) {
    		$this->success('删除成功!');
    	} else {
    		$this->error('删除失败');
    	}
    }


    public function user()
    {
    	$model = M('buyer');
        $status = I('post.status');
        if($status == '1') {
            $where['a.status'] = '1';
        } elseif($status == '2') {
            $where['a.status'] = '0';
        } else {
            $where = [];
        }
        $mobile = I('post.mobile');
        if($mobile) {
            $where['a.account'] = $mobile;
        }
    	$list = $model->alias('a')->field('a.*,b.name')->join('buyer_group b','a.group = b.id')->where($where)->paginate(25,false,['query'=>request()->param()]);
    	$this->assign('list',$list);
        $this->assign('mobile',$mobile);
        $this->assign('status',$status);
    	return $this->fetch();
    }

    public function addAddress($param)
    {
        $url = 'https://mobile.yangkeduo.com/proxy/api/api/origenes/address';
        $data = [
            'name'  => $param['name'],
            'mobile' => $param['account'],
            'province_id' => $param['province_id'],
            'city_id'     => $param['city_id'],
            'district_id' => $param['county_id'],
            'address'     => $param['address'],
            'is_default'  => "0",
        ];
        $data = json_encode($data);
        $result = Http::post($url,$data,[],$param['access_token']);
        $array = json_decode($result,true);
        if(isset($array['error_code'])) {
        	return false;
        } else {
        	return $array['address_id'];
        }
        
    }

    public function delAddress($id,$access_token)
    {
        $url = 'https://mobile.yangkeduo.com/proxy/api/api/origenes/address/delete/'.$id;
        $result = Http::POST($url,'',[],$access_token);
    }

    public function user_add()
    {
    	$Model_Data = M('buyer');
	    $post = I('post.');
	    if($post['status'] == '1') {
		    $post['status_msg'] = '正常';
		}
		$address_id = $this->addAddress($post);
		if($address_id == false) {
			$this->error('生成拼多多收货地址失败!');
		}
		$post['address_id'] = $address_id;
	    if($Model_Data->insert($post)){
	        $this->success('新增成功!',U('user'));
	    }else{
	        $this->error('新增失败!');
	    }
    	return $this->fetch();
    }

    public function user_insert()
    {
    	$model = M('buyer_group');

    	$list = $model->select();
    	$this->assign('list',$list);
		return $this->fetch();
    }

    public function user_update()
    {
    	$id = I('get.id');
    	$info = M('buyer')->where('id',$id)->find();
    	$model = M('buyer_group');
    	$list = $model->select();
    	$this->assign('list',$list);
    	$this->assign('info',$info);
		return $this->fetch();
    }

    public function user_save()
    {
    	$Model_Data = M('buyer');
	    $post = I('post.');
        $old_address_id = $Model_Data->where('id',$post['id'])->getField('address_id');
	    $address_id = $this->addAddress($post);
	    if($address_id == false) {
			$this->error('生成拼多多收货地址失败!');
		} elseif(!empty($old_address_id)) {
            $this->delAddress($old_address_id,$post['access_token']);
        }
		$post['address_id'] = $address_id;
    	if($Model_Data->where('id',$post['id'])->save($post)){
        	$this->success('编辑成功!',U('user'));
	    }else{
	        $this->error('编辑失败!');
	    }
    	return $this->fetch();
    }

    public function checkMobile()
    {
        $mobile = I('post.mobile');
        $is_exist = M('buyer')->where('account',$mobile)->find();
        if($is_exist) {
            return false;
        } else {
            return true;
        }
    }

    public function user_del()
    {
    	$id = I('get.id');
    	if(M('buyer')->where('id',$id)->delete()) {
    		$this->success('删除成功!');
    	} else {
    		$this->error('删除失败');
    	}
    }




	public function user_handle(){
        $data = I('post.');
        $res = M('buyer')->where('id',$data['id'])->save(['status'=>$data['status']]);
        if($res){
            $this->success("操作成功",U('shop_list'));
        }else{
            $this->error("操作失败",U('shop_list'));
        }
    }

    public function group()
    {
    	$model = M('buyer_group');
        $list = $model->paginate(25)->each(function($item,$key) {
            $is_received = M('Order')->where(['status'=>['gt','0']])->where(['received_time'=>'0'])->where(['group'=>$item['id']])->count();

            $item['is_received'] = $is_received;
            return $item;
        });
        $result = M('config')->where('name','group')->getField('value');
        $this->assign('value',$result);
    	$this->assign('list',$list);
        $is_received = M('order')->where(['status'=>['gt',0],'received_time'=>'0'])->count('id');
        $this->assign('is_received',$is_received); 
    	return $this->fetch();
    }

    public function group_add()
    {
    	$model = M('buyer_group');
    	$data = I('post.');
    	if($model->insert($data)){
	        $this->success('新增成功!',U('group'));
	    }else{
	        $this->error('新增失败!');
	    }
    }

    public function group_insert()
    {
        $shop_list = M('shop')->select();
        $this->assign('shop_list',$shop_list);
		return $this->fetch();
    }

    public function group_update()
    {
    	$id = I('get.id');
    	$info = M('buyer_group')->where('id',$id)->find();
        $shop_list = M('shop')->select();
        $this->assign('shop_list',$shop_list);
    	$this->assign('info',$info);
    	return $this->fetch();
    }

    public function group_save()
    {
    	$model = M('buyer_group');
    	$data = I('post.');
    	if($model->where('id',$data['id'])->save($data)){
	        $this->success('编辑成功!',U('group_update',['id'=>$data['id']]));
	    }else{
	        $this->error('编辑失败!');
	    }
    }

    public function buyer_group_info(){
        $id = I('get.id');
        if ($id) {
            $where['group'] = $id;
        } else {
            $where['group'] = 0;
        }
        $where['status'] = 1;
        $user = M('buyer')->where($where)->getField('id,account');
        // var_dump($user);
        $buyer_group = M('buyer_group')->where($this->status_where)->getField('id,name');
        $this->assign('user',$user);
        $this->assign('buyer_group',$buyer_group);
        $this->assign('id',$id);
        return $this->fetch();
    }

	public function group_handle(){
        $data = I('post.');
        $res = M('buyer_group')->where('id',$data['id'])->save(['status'=>$data['status']]);
        if($res){
            $this->success("操作成功",U('shop_list'));
        }else{
            $this->error("操作失败",U('shop_list'));
        }
    }

	public function buyer_group_handle()
    {
        $data = I('post.');
        // var_dump($data['user']);
        if($data['id']){
            $buyer_group = M('buyer_group')->where(['id'=>$data['id']])->find();
            // 找出被取消的用户
            $buyer_list = M('buyer')->where(['group'=>$data['id']])->getField('id' ,true);
            $buyer_diff = array_diff($buyer_list, $data['user']?:[]);
            foreach ($buyer_diff as $key => $value) {
                // 取消的用户的分组被重置为0
                $res = M('buyer')->where(['id'=>$value])->save(['group'=>0]);
            }
        } else {
            foreach ($data['user'] as $key => $value) {
                // 变更用户分组
                $res = M('buyer')->where(['id'=>$value])->save(['group'=>$data['group_id']]);
            }
        }
        if($res){
            $this->success("操作成功",U('index/pdd/buyer_group_info',array('id'=>$data['id'])));
        }else{
            $this->error("操作失败",U('index/pdd/buyer_group_info',array('id'=>$data['id'])));
        }
    }

    public function receivingGoods($uid,$order_sn,$access_token)
    {
        $url = 'http://apiv3.yangkeduo.com/order/'.$order_sn.'/received';
        // $access_token = 'MBA7UHBS6OBH6OCHRCLUDIROOSCVRSNLBNNLIO2EBNNEJVENVI7A100168a';
        $result = Http::get($url,'','',$access_token);
        $result_array = json_decode($result,true);
        $model = M('order');
        if(!isset($result_array['error_code']) && isset($result_array['server_time'])) {
            $model->where(['order_sn'=>$order_sn])->save([
                'received_time' => $result_array['server_time']
            ]);
            return true;
        } else {
            return false;
        }

    }

    // 批量收货
    public function batchReceiving()
    {
        $buyer = M('buyer');
        // 找出该组用户列表
        $order = M('order');
        $group = I('post.group');
        if($group != 0) {
            $order_list = $order->where(['status'=>['gt','0']])->where(['received_time'=>'0'])->where(['group'=>$group])->select();
        } else {
            // 获取已付款的订单 还需判断是否已确认收货
            $order_list = $order->where(['status'=>['gt','0']])->where(['received_time'=>'0'])->select();
        }
        $count = count($order_list);
        // 提取订单列表满足条件的uid
        if(empty($order_list)) {
            return false;
        } 
        // $uids = ['2','4','5','6'];
        $num = 0;
        $uids = array_column($order_list, 'uid');
        $user_list = $buyer->where(['id'=>['in',$uids]])->getField('id,access_token',true);
        foreach ($order_list as $key => $value) {
            $result = $this->receivingGoods($value['uid'],$value['order_sn'],$user_list[$value['uid']]);
            if($result == true) $num ++;
        }

        if($num > 0 && $num == $count) {
            $num = '成功收货'.$num.'单,其余订单未发货!';
        } elseif($num == 0) {
            $num = '订单未发货!';
        } else {
            $num = '成功收货'.$num.'单';
        }
        return json_encode([
            'code' => '200',
            'msg'  => '执行成功! 如果为0 则未发货!',
            'num'  => $num,
        ]);
    }

    public function checkToken($url,$param,$option,$access_token)
    {
        $result = Http::get($url,$param,$option,$access_token);
        $array = json_decode($result,true);
        if(isset($array['error_code'])) {
            return false;
        } else {
            return true;
        }
    }


    public function check()
    {
        $url = 'https://mobile.yangkeduo.com/proxy/api/api/flow/user/me/ext?check_is_active=1';

        $model = M('buyer');
        $group_id = I('get.group_id');
        // halt($group_id);
        $list = $model->where(['group'=>$group_id])->select();
        $ids = [];
        foreach ($list as $key => $value) {
            $result = $this->checkToken($url,'','',$value['access_token']);
            if($result == false) {
                $ids[] = $value['id'];
            }
            if($value['status'] == 0 && $result) {
                $id_array[] = $value['id'];
            }
        }

        // 批量修改用户状态
        if(!empty($ids)) {
            $res = $model->where(['id'=>['in',$ids]])->save([
                    'status' => 0,
                    'status_msg' => 'Access_token 失效'
            ]);
        }

        if(!empty($id_array)) {
             $res = $model->where(['id'=>['in',$id_array]])->save([
                    'status' => 1,
                    'status_msg' => ''
            ]);
        }


        $this->success('执行成功! 检测异常用户'.count($ids).'位');
        
    }

}