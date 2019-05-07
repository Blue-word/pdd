<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\db\Query;
use think\Hook;

class Test extends Base{

    public function _initialize(){
    }

    public function test02(){
        $res = $this->exportExcel_2();
        dump($res);
    }

    
    public function test01(){
        $user = M('new_survey_copy')->field($field)->select();
        $subject = "创说会新人签到表导出";
        $title = array("新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID","新人openID");
        $asd = $this->exportExcel($user,$title,$subject); 
        // dump($asd);
    }

	public function test(){
		$user = M('new_survey_copy')->select();
        $this->assign('user',$user);
		return $this->fetch();
	}

	public function test1(){
		$user = M('new_survey_copy')->select();
        $this->assign('user',$user);
        // $this->display("/index");
		return $this->fetch();
	}

    /**
     * 导出
     */
    public function export(){
    	$field = 'id,name,id_number,phone,recommend_number,class,sex,other_content';
        $user = M('new_survey_copy')->field($field)->select();
        // dump($data);
        $subject = "Excel导出测试";
        $title = array("id","姓名","身份证","手机号","推荐工号","班次","性别","其他内容");
        $asd = $this->exportExcel($user,$title,$subject); 
        dump($asd);
    }
    /**
     * 导入
     */
    public function import(){
        $tableName = "new_survey_copy";
        $title = array("id","name","id_number","phone","recommend_number","class","sex","other_content");
        $result = $this->importExcel($tableName,$title);
        Db::startTrans();
        $model = model('NewSurveyCopy');
        if ($result['status'] == true) {   //success
        	// dump($result['data']);
        	$res = $model->saveAll($result['data']);
        	if ($res) {
        		Db::commit(); 
        		$this->success('导入成功');
        	}else{
        		Db::rollback();
        		$this->success('导入成功');
        	}
        }else{
        	$this->error($result['data']);
        }
        // dump($result);
        // $this->success($result);
    }

    public function test03()
    {
        // Hook::add('action_begin','app\index\behavior\Test');
        // Hook::add('app_init','app\\index\\behavior\\Test');
        // Hook::add('app_begin','app\index\behavior\Test');
        $params = 'asd';
        // Hook::listen('app_init',$params);   //添加行为侦听
        
        // Hook::listen('module_init',$params);   //添加行为侦听
        
        // Hook::listen('action_begin',$params);   //添加行为侦听
        // Hook::listen('app_begin',$params);   //添加行为侦听

        echo "<br/>";
        echo "end";
    }

    /**
     * 拼多多公共请求参数
     */
    public function test04($value='')
    {
    	$url_param = array (
            'access_token' => 'asd78172s8ds9a921j9qqwda12312w1w21211',
            'client_id' =>1,//客户端ID
            'data_type' => 'JSON',
            'type'=> '',	//接口名称
            'timestamp' => time(),
            'version' => '',
            'sign' => ''	//签名
        );
        return $url_param;
    }
    
    /**
     * 商品列表
     */
    public function test05()
    {
    	$url = 'http://gw-api.pinduoduo.com/api/router?';
    	$url = $url.http_build_query($this->test04());
    	$data = httpPost($url, []);
    	$this->test07($data);
    	if ($this->error) {
    		return false;
    	}
    	$res = $data['goods_list_get_response'];
    	$num = ceil($res['total_count']/100);
    	for ($i=0; $i < $num; $i++) { 
    		$this->test06();
    	}
    	var_dump($url);
    }

    public function test06($num=1)
    {
    	$url = 'http://gw-api.pinduoduo.com/api/router?';
    	$url = $url.http_build_query($this->test04());
    	$data = httpPost($url, []);
    	$this->test07($data);
    	if ($this->error) {
    		return false;
    	}
    	$res = $data['goods_list_get_response']['goods_list'];
    	foreach ($res as $key => $value) {
    		$goods_data[] = [
	    		'thumb_url' => $value['thumb_url'],
	    		'goods_id' => $value['goods_id'],
	    		'goods_name' => $value['goods_name'],
	    		'goods_quantity' => $value['goods_quantity'],
	    		'is_onsale' => $value['is_onsale'],
	    		'sku_list' => $value['sku_list']
	    	];
    	}
    	
    	
    	var_dump($url);
    }

    /**
     * 校验
     */
    public function test07($data)
    {
    	$this->error = '';
		$this->errorcode = 0;
    	if (!$data['goods_list_get_response']) {
    		$this->error = $data['error_response']['error_msg'];
    		$this->errorcode = $data['error_response']['error_code'];
    	}
    	if ($data['goods_list_get_response']['total_count'] <= 0) {
    		$this->error = '暂无商品';
    		$this->errorcode = 101;
    	}
    }


}