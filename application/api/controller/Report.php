<?php
namespace app\api\controller;
use think\Page;

class Report extends Common{

	public function _initialize(){
        //定义子类_initialize，不调用父类构造方法
    } 

	public function recommend_code_list(){	//推荐工号列表
		$code = input('post.code','000051036821');   //推荐工号
		$name = M('data_source')->where('salesman_code',$code)->getField('salesman_name');
		$this->apiReturn('查询成功','200',$name);
	}

	public function total_table_list(){		//总表，从mo服务器api请求
		$sort = input('post.sort',2);		//排序，1昨日、2今日、4本月、5本年
		$level = input('post.level',2);		//层级level：1、2
		$name = input('post.name','南京');		//关键字搜索词
		$post_data['type'] = $sort;
		$post_data['level'] = $level;
		$post_data['name'] = $name;
		$url = 'http://106.15.199.8:8083/jforphp/report/api/list.do';
		$res = $this->http_request($url, $post_data);
		$asd = json_decode($res,true);
		$this->apiReturn('查询成功','200',$asd['result']);
	}

	public function total_table_list1(){		//总表，从mo服务器api请求
		$sort = input('post.sort',3);		//排序，1昨日、2今日、3本月、4本年
		if ($sort == 1) {
			$sort_time = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;//昨日
			$begin_time = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
			$end_time = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
		}elseif ($sort == 2) {
			$sort_time = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;//今天
		 	$begin_time=mktime(0,0,0,date('m'),date('d'),date('Y'));
	 	 	$end_time=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		}elseif ($sort == 3) {
			$sort_time = mktime(23,59,59,date('m'),date('t'),date('Y'));//本月
	 		$begin_time = mktime(0,0,0,date('m'),1,date('Y'));
		 	$end_time = mktime(23,59,59,date('m'),date('t'),date('Y'));
		}elseif ($sort == 4) {
			$sort_time = mktime(23,59,59,date('m'),date('t'),date('Y'));//本年
			$begin_time=mktime(0,0,0,date('m'),1,date('Y'));
		 	$end_time=mktime(23,59,59,date('m'),date('t'),date('Y'));
		}
		$list_data = array(
			'0' => array(
				'inst' => '常州',
				'totalEmpNum' => M('data_source')->where('zhongzhi','常州')->count(),
				'addEmpNum' => M('detail_list')->where('organization','常州')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','常州')->where('sign_time','<',$sort_time)->count(),
				), 
			'1' => array(
				'inst' => '淮安',
				'totalEmpNum' => M('data_source')->where('zhongzhi','淮安')->count(),
				'addEmpNum' => M('detail_list')->where('organization','淮安')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','淮安')->where('sign_time','<',$sort_time)->count(),
				), 
			'2' => array(
				'inst' => '连云港',
				'totalEmpNum' => M('data_source')->where('zhongzhi','连云港')->count(),
				'addEmpNum' => M('detail_list')->where('organization','连云港')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','连云港')->where('sign_time','<',$sort_time)->count(),
				), 
			'3' => array(
				'inst' => '南京',
				'totalEmpNum' => M('data_source')->where('zhongzhi','南京')->count(),
				'addEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','<',$sort_time)->count(),
				), 
			'4' => array(
				'inst' => '南通',
				'totalEmpNum' => M('data_source')->where('zhongzhi','南通')->count(),
				'addEmpNum' => M('detail_list')->where('organization','南通')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','南通')->where('sign_time','<',$sort_time)->count(),
				), 
			'5' => array(
				'inst' => '泰州',
				'totalEmpNum' => M('data_source')->where('zhongzhi','泰州')->count(),
				'addEmpNum' => M('detail_list')->where('organization','泰州')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','泰州')->where('sign_time','<',$sort_time)->count(),
				), 
			'6' => array(
				'inst' => '无锡',
				'totalEmpNum' => M('data_source')->where('zhongzhi','无锡')->count(),
				'addEmpNum' => M('detail_list')->where('organization','无锡')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','无锡')->where('sign_time','<',$sort_time)->count(),
				), 
			'7' => array(
				'inst' => '宿迁',
				'totalEmpNum' => M('data_source')->where('zhongzhi','宿迁')->count(),
				'addEmpNum' => M('detail_list')->where('organization','宿迁')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','宿迁')->where('sign_time','<',$sort_time)->count(),
				), 
			'8' => array(
				'inst' => '徐州',
				'totalEmpNum' => M('data_source')->where('zhongzhi','徐州')->count(),
				'addEmpNum' => M('detail_list')->where('organization','徐州')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','徐州')->where('sign_time','<',$sort_time)->count(),
				), 
			'9' => array(
				'inst' => '盐城',
				'totalEmpNum' => M('data_source')->where('zhongzhi','盐城')->count(),
				'addEmpNum' => M('detail_list')->where('organization','盐城')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','盐城')->where('sign_time','<',$sort_time)->count(),
				), 
			'10' => array(
				'inst' => '扬州',
				'totalEmpNum' => M('data_source')->where('zhongzhi','扬州')->count(),
				'addEmpNum' => M('detail_list')->where('organization','扬州')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','扬州')->where('sign_time','<',$sort_time)->count(),
				), 
			'11' => array(
				'inst' => '镇江',
				'totalEmpNum' => M('data_source')->where('zhongzhi','镇江')->count(),
				'addEmpNum' => M('detail_list')->where('organization','镇江')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','镇江')->where('sign_time','<',$sort_time)->count(),
				),
			'12' => array(
				'inst' => '总计',
				'totalEmpNum' => M('data_source')->count(),
				'addEmpNum' => M('detail_list')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('sign_time','<',$sort_time)->count(),
				),
			'13' => array(
				'inst' => '南京金陵',
				'totalEmpNum' => M('data_source')->where('zhongzhi','南京')->count(),
				'addEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','<',$sort_time)->count(),
				), 
			'14' => array(
				'inst' => '中心一区',
				'totalEmpNum' => M('data_source')->where('zhongzhi','南京')->count(),
				'addEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','<',$sort_time)->count(),
				), 
			'15' => array(
				'inst' => '南京鼓楼',
				'totalEmpNum' => M('data_source')->where('zhongzhi','南京')->count(),
				'addEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','between',[$begin_time,$end_time])->count(),
				'totalAddEmpNum' => M('detail_list')->where('organization','南京')->where('sign_time','<',$sort_time)->count(),
				), 
		);
		// dump($list_data);
		$this->apiReturn('查询成功','200',$list_data);
	}

	public function total_table_list2(){
		$sort = input('post.sort',3);		//排序，1昨日、2今日、3本月、4本年
		if ($sort == 1) {
			$sort_time = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;//昨日
			$begin_time = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
			$end_time = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
		}elseif ($sort == 2) {
			$sort_time = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;//今天
		 	$begin_time=mktime(0,0,0,date('m'),date('d'),date('Y'));
	 	 	$end_time=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		}elseif ($sort == 3) {
			$sort_time = mktime(23,59,59,date('m'),date('t'),date('Y'));//本月
	 		$begin_time = mktime(0,0,0,date('m'),1,date('Y'));
		 	$end_time = mktime(23,59,59,date('m'),date('t'),date('Y'));
		}elseif ($sort == 4) {
			$sort_time = mktime(23,59,59,date('m'),date('t'),date('Y'));//本年
			$begin_time=mktime(0,0,0,date('m'),1,date('Y'));
		 	$end_time=mktime(23,59,59,date('m'),date('t'),date('Y'));
		}
		$keywords = input('post.keywords','南京');		//post过来的关键字参数
		$level = input('post.level',2);		//post过来的层级参数
		if ($level == 2) {
			# code...
		}
		switch ($level) {
			case '2':
				$list = M('detail_list')->where('level4_organization',$keywords)->select();
				break;
			
			default:
				# code...
				break;
		}
		

	}

}