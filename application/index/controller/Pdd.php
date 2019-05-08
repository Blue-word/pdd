<?php
namespace app\index\controller;
use think\Controller;
use fast\Http;

class Pdd extends Controller{

	public function shop_list()
	{
		$model = M('shop');
		$list = $model->select();
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
    	$list = $model->select();
    	$this->assign('list',$list);
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
	    $address_id = $this->addAddress($post);
	    if($address_id == false) {
			$this->error('生成拼多多收货地址失败!');
		}
		$post['address_id'] = $address_id;
    	if($Model_Data->where('id',$post['id'])->save($post)){
        	$this->success('编辑成功!',U('user'));
	    }else{
	        $this->error('编辑失败!');
	    }
    	return $this->fetch();
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

    	$list = $model->select();

    	$this->assign('list',$list);
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
		return $this->fetch();
    }

    public function group_update()
    {
    	$id = I('get.id');
    	$info = M('buyer_group')->where('id',$id)->find();
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


	public function group_handle(){
        $data = I('post.');
        $res = M('buyer_group')->where('id',$data['id'])->save(['status'=>$data['status']]);
        if($res){
            $this->success("操作成功",U('shop_list'));
        }else{
            $this->error("操作失败",U('shop_list'));
        }
    }

}