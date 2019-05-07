<?php
namespace app\api\controller;
use think\Db;

class User extends Common{

	public function _initialize(){
		// parent::_initialize();  //关闭时不调用父类检验token
	}

	public function judge_new_survey(){   	//判断是否填写新人调查表
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');
		$data = input('post.');
		$new_id = M('userinfo')->where('openid',$openid)->getField('new_id');
		if ($new_id == null) {   	//未填过
			$res['info'] = null;
			$res['status'] = false;
		}else{			//已填过
			$res['info']  = M('new_survey')->where('id',$new_id)->find();
			$res['status'] = true;
		}
		$this->apiReturn('查询成功','200',$res);
	}

	public function new_survey_submit(){		//新人调查表提交
		$data = input('post.');
      	$longitude = input('post.longitude/f','118.8815612793');		//经度
		$latitude  = input('post.latitude/f','31.941440582275');		//纬度
      	$data['coordinate'] = $longitude.$latitude;
		if ($data['activity_id']) {   //增员调查表
			$id  = input('post.activity_id',3);		//课程id
			$openid = input('post.openid');	//用户openid
			$radius = M('new_add')->where('id',$id)->getField('radius');
			$course_name = M('new_add')->where('id',$id)->getField('title');
			if ($radius == 0) {   //0为不设置签到范围
				$model = model('NewSurvey');
				$data['time'] = time();
				$res = $model->allowField(true)->save($data);
				if ($res) {
					M('userinfo')->where('openid',$data['openid'])->save(['new_id'=>$model->id]);
					newLog('提交'.$course_name.'成功，提交经度：'.$longitude.' | 纬度：'.$latitude,$openid);
					$this->apiReturn('恭喜您','200','提交成功');
				}else{
					newLog($course_name.'提交异常，提交经度：'.$longitude.' | 纬度：'.$latitude,$openid);
					$this->apiReturn('很抱歉','401','提交异常！');
				}
			}else{		//不为0则设置了签到范围
				$key = '4n5f9nGoP7R54lVrqEjAD8502ZvLRA78';  //百度密钥
				$url = "http://api.map.baidu.com/geoconv/v1/?coords=$longitude,$latitude&from=3&to=5&ak=$key"; //GET请求
				$content = $this->http_request($url);    //将源坐标转换为百度经纬度坐标
				$result = json_decode($content);
				$longitude_1 = $result->result[0]->x; 		//经度
				$latitude_1  = $result->result[0]->y;		//纬度
				$coordinate = M('new_add')->where('id',$id)->getField('coordinate');
				$coordinate = explode(',',$coordinate);
				$longitude_2 = $coordinate[0];				//经度
				$latitude_2  = $coordinate[1];				//纬度
				$distance = $this->distance($latitude_1,$longitude_1,$latitude_2,$longitude_2,false);
				$distance_1 = sprintf("%.1f",$distance);
				if ($distance_1 <= $radius) {    //当前距离小于签到半径
					$data = input('post.');
					$model = model('NewSurvey');
					$data['time'] = time();
					$res = $model->allowField(true)->save($data);
					if ($res) {
						M('userinfo')->where('openid',$data['openid'])->save(['new_id'=>$model->id]);
						newLog('提交'.$course_name.'成功，提交经度：'.$longitude.' | 纬度：'.$latitude,$openid);
						$this->apiReturn('恭喜您','200','提交成功');
					}else{
						newLog($course_name.'提交异常，提交经度：'.$longitude.' | 纬度：'.$latitude,$openid);
						$this->apiReturn('很抱歉','401','提交异常！');
					}
				}else{
					newLog('未进入'.$course_name.'提交范围，提交失败，提交经度：'.$longitude.' | 纬度：'.$latitude,$openid);
					$this->apiReturn('提交失败','400','请在目的地'.$radius.'范围内提交！');
				}
			}
		}else{
			Db::startTrans();
			$model = model('NewSurvey');
			$data['time'] = time();
			$res = $model->allowField(true)->save($data);
			$new_survey_id = $model->id;
			$salesman_where['salesman_code'] = $data['recommend_number'];
			$salesman_where['salesman_name'] = $data['recommend_name'];
			$source = M('data_source')->where($salesman_where)->find();
			$detail_data = array(
				'organization' => $source['zhongzhi'],
				'level4_organization' => $source['market_server_unit'],
				'business_unit' => $source['business_unit_name'],
				'business_group' => $source['business_group_name'],
				'new_name' => $data['name'],
				'id_number' => $data['id_number'],
				'recommend_code' => $source['salesman_code'],
				'recommend_name' => $source['salesman_name'],
				'recommend_rank' => $source['rank_name'],
				'survey_status' => 1,
				'openid' => $data['openid'],
				'sign_time' => time(),
				'is_insurance_zige' => $data['is_insurance_zige'],
			);
			//签到时间
			// $sign_info = M('activity_apply')->where('openid',$data['openid'])->order('time desc')->limit(1)->find();
			// if ($sign_info) {		//已签到
			// 	$detail_data['sign_time'] = $sign_info['time'];
			// 	$detail_data['sign_status'] = $sign_info['status'];
			// }else{		//未签到则签到无效，从用户签到的时候再进行判断
			// 	$detail_data['sign_time'] = time();
			// }	
			
			$res_1 = M('detail_list')->save($detail_data);
			if ($res && $res_1) {
				M('userinfo')->where('openid',$data['openid'])->save(['new_id'=>$new_survey_id]);
				newLog('提交新人调查表，提交经度：'.$longitude.' | 纬度：'.$latitude,$data['openid']);
				Db::commit();  
				$this->apiReturn('提交成功','200','提交成功');
			}else{
	          	Db::rollback();
	          	newLog('提交新人调查表异常，提交经度：'.$longitude.' | 纬度：'.$latitude,$data['openid']);
				$this->apiReturn('提交失败','401','提交失败');
			}
		}
		
		
	}

	public function new_survey_submit1(){		//新人调查表提交
      	$longitude = input('post.longitude/f','118.88156890869');		//经度
		$latitude  = input('post.latitude/f','31.941381454468');		//纬度
		$id  = input('post.id',3);		//课程id
		$key = '4n5f9nGoP7R54lVrqEjAD8502ZvLRA78';  //百度密钥
		$url = "http://api.map.baidu.com/geoconv/v1/?coords=$longitude,$latitude&from=3&to=5&ak=$key"; //GET请求
		$content = $this->http_request($url);    //将源坐标转换为百度经纬度坐标
		$result = json_decode($content);
		$longitude_1 = $result->result[0]->x; 		//经度
		$latitude_1  = $result->result[0]->y;		//纬度
		$coordinate = M('new_course')->where('id',$id)->getField('coordinate');
		$radius = M('new_course')->where('id',$id)->getField('radius');
		$coordinate = explode(',',$coordinate);
		$longitude_2 = $coordinate[0];				//经度
		$latitude_2  = $coordinate[1];				//纬度
		$distance = $this->distance($latitude_1,$longitude_1,$latitude_2,$longitude_2,false);
		$distance_1 = sprintf("%.1f",$distance);
      	$course_name = M('new_course')->where('id',$id)->getField('title');
      	dump($longitude);
      	dump($latitude);
      	dump($longitude_1);
      	dump($latitude_1);
      	dump($longitude_2);
      	dump($latitude_2);
      	dump($distance_1);
      	dump($radius);
      	dump($url);
	}

	public function course_det(){			//课程详情(需加事务操作)
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');
		$id = input('post.id','3');    //课程id
		$field = 'time,radius,uid,audit_uid,status';
		$course_info = M('new_course')->where('id',$id)->field($field,true)->find();
		if ($course_info) {		
			$course_info['picture'] = img_nullchange($course_info['picture']);
			$course_time = date('Y-m-d H:i',$course_info['start_time']);
    		if (!empty($course_time)) {     //结束时间不为空，拼接时间
    			$course_time = $course_time.' — '.date('Y-m-d H:i',$course_info['end_time']);
    		}
    		$course_info['course_time'] = $course_time;
			$is_apply = M('course_apply')->where(['course_id'=>$id,'openid'=>$openid])->getField('status');  //1已签到0未签到
			if ($is_apply) {	//已签到
				$res['status'] = 2;
			}else{		//未签到
				$res['status'] = 1;
			}
			$res['course_info'] = $course_info;
			$this->apiReturn('查询成功','200',$res);
		}else{			
			$this->apiReturn('查询失败','400','查询失败');
		}
		// dump($res);
	}

	public function reply_course(){			//课程报名
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');
		$id = input('post.id','3');    //课程id
		$new_id = M('userinfo')->where('openid',$openid)->getField('new_id');
		if ($new_id) {		//填过新人调查表
			$data['course_id'] = $id;
			$data['new_id'] = $new_id;
			$data['time'] = time();
			$data['status'] = 1;
			$res = M('new_apply')->save($data);
			$res_1 = M('new_course')->where('id',$id)->setInc('apply_number',1);
			if ($res  && $res_1) {
				$course_name = M('new_course')->where('id',$id)->getField('title');
				newLog('报名'.$course_name.'课程',$openid);
				$this->apiReturn('恭喜您','200','报名成功！');
			}else{
				$this->apiReturn('很抱歉','401','报名失败！');
			}
		}else{		//未填过新人调查表
			$this->apiReturn('报名失败','400','请先填写新人调查表再进行报名！');
		}
	}

	public function position_sign(){		//用户定位签到
		$longitude = input('post.longitude/f','118.8812610000');		//经度
		$latitude  = input('post.latitude/f','31.9414520000');		//纬度
		$id  = input('post.id',3);		//课程id
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');	//用户openid
		$post_coordinate = $this->coordinate_change($longitude,$latitude);    //POST过来的经纬度转换为百度经纬度坐标
		$coordinate = M('new_course')->where('id',$id)->getField('coordinate');
		$radius = M('new_course')->where('id',$id)->getField('radius');
		$coordinate = explode(',',$coordinate);
		$longitude_2 = $coordinate[0];				//经度
		$latitude_2  = $coordinate[1];				//纬度
		$distance = $this->distance($post_coordinate['latitude'],$post_coordinate['longitude'],$latitude_2,$longitude_2,false);
		$distance_1 = sprintf("%.1f",$distance);
		$course_name = M('new_course')->where('id',$id)->getField('title');
		if ($distance_1 <= $radius) {    //当前距离大于签到半径
			$apply_data = array(
				'longitude' => $longitude,
				'latitude' => $latitude,
				'time' => time(),
				'status' => 1
			);
			$res = M('course_apply')->where(['course_id'=>$id,'openid'=>$openid])->save($apply_data);   //状态改为已签到
			if ($res) {
				$course_cate = M('new_course')->where('id',$id)->getField('course_cate');
				if ($course_cate == '培训班') {   //将签到信息记录到明细表
					M('detail_list')->where('openid',$openid)->save(['sign_time'=>time(),'sign_status'=>'是']);    //处理明细报表，先填调查问卷后签到问题
				}
				newLog('完成'.$course_name.'课程签到',$openid);
				$this->apiReturn('恭喜您','200','签到成功！');
			}else{
				newLog($course_name.'课程签到异常',$openid);
				$this->apiReturn('很抱歉','401','签到状态未改变！');
			}
		}else{
			newLog('未进入'.$course_name.'课程签到范围，签到失败，签到经度：'.$longitude.' | 纬度：'.$latitude,$openid);
			$this->apiReturn('签到失败','400','请在目的地'.$radius.'范围内签到！');
		}
	}

	public function new_add_submit(){		//增员活动调查表提交
		$longitude = input('post.longitude/f','118.8812610000');		//经度
		$latitude  = input('post.latitude/f','31.9414520000');		//纬度
		$id  = input('post.id',3);		//课程id
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');	//用户openid
		$radius = M('new_add')->where('id',$id)->getField('radius');
		$data['coordinate'] = $longitude.$latitude;
		if ($radius == 0) {   //0为不设置签到范围
			$data = input('post.');
			$model = model('NewAddSurvey');
			$data['time'] = time();
			$res = $model->allowField(true)->isUpdate(false)->save($data);
			if ($res) {
				newLog('提交'.$course_name.'成功',$openid);
				$this->apiReturn('恭喜您','200','提交成功');
			}else{
				newLog($course_name.'提交异常',$openid);
				$this->apiReturn('很抱歉','401','提交异常！');
			}
		}else{		//不为0则设置了签到范围
			$key = '4n5f9nGoP7R54lVrqEjAD8502ZvLRA78';  //百度密钥
			$url = "http://api.map.baidu.com/geoconv/v1/?coords=$longitude,$latitude&from=3&to=5&ak=$key"; //GET请求
			$content = $this->http_request($url);    //将源坐标转换为百度经纬度坐标
			$result = json_decode($content);
			$longitude_1 = $result->result[0]->x; 		//经度
			$latitude_1  = $result->result[0]->y;		//纬度
			$coordinate = M('new_add')->where('id',$id)->getField('coordinate');
			$coordinate = explode(',',$coordinate);
			$longitude_2 = $coordinate[0];				//经度
			$latitude_2  = $coordinate[1];				//纬度
			$distance = $this->distance($latitude_1,$longitude_1,$latitude_2,$longitude_2,false);
			$distance_1 = sprintf("%.1f",$distance);
			$course_name = M('new_add')->where('id',$id)->getField('title');
			if ($distance_1 <= $radius) {    //当前距离小于签到半径
				$data = input('post.');
				$model = model('NewAddSurvey');
				$data['time'] = time();
				$res = $model->allowField(true)->save($data);
				// dump($model->id);
				if ($res) {
					newLog('提交'.$course_name.'成功',$openid);
					$this->apiReturn('恭喜您','200','提交成功');
				}else{
					newLog($course_name.'提交异常',$openid);
					$this->apiReturn('很抱歉','401','提交异常！');
				}
			}else{
				newLog('未进入'.$course_name.'提交范围，提交失败，提交经度：'.$longitude.' | 纬度：'.$latitude,$openid);
				$this->apiReturn('提交失败','400','请在目的地'.$radius.'范围内提交！');
			}
		}
	}


	public function activity_position_sign(){		//扫描增员活动签到
		$data = input('post.');
		//判断签到时间
		$sign_time = time();
		if ($sign_time < $act_info['start_time'] || $sign_time > $act_info['end_time']) {    //不在签到时间段
			$this->apiReturn('签到失败','400','不在签到时间段');
		}
		$post_coordinate = $this->coordinate_change($data['longitude'],$data['latitude']);    //POST过来的经纬度转换为百度经纬度坐标
		$act_info = M('new_add')->where('id',$data['activity_id'])->find();
		$coordinate = explode(',',$act_info['coordinate']);
		$longitude_2 = $coordinate[0];				//经度
		$latitude_2  = $coordinate[1];				//纬度
		$distance = $this->distance($post_coordinate['latitude'],$post_coordinate['longitude'],$latitude_2,$longitude_2,false);
		$distance_1 = sprintf("%.1f",$distance);
		$radius = M('new_add')->where('id',$data['activity_id'])->getField('radius');
		$activity_name = M('new_add')->where('id',$data['activity_id'])->getField('title');
		if ($distance_1 <= $radius) {    //当前距离小于签到半径
			$sign_where['activity_id'] = $data['activity_id'];
			$sign_where['openid'] = $data['openid'];
			$sign_where['status'] = 0;
			$sign_where['is_invite'] = 1;
			$sign_count = M('activity_apply')->where($sign_where)->count();
			$data['time'] = time();
			$data['status'] = 1;
			$model = model('ActivityApply');
			if ($sign_count) {		//导入新人报表的活动
				$res = $model->where(['openid'=>$data['openid'],'activity_id'=>$data['activity_id']])->save(['longitude'=>$data['longitude'],'latitude'=>$data['latitude'],'time'=>$data['time'],'status'=>$data['status']]);
			}else{			//未导入新人报表的活动
				$res = $model->allowField(true)->save($data);
			}
			if ($res) {
				M('new_add')->where('id',$data['activity_id'])->setInc('apply_number',1);
				newLog('完成'.$activity_name.'活动签到',$data['openid']);
				$this->apiReturn('恭喜您','200','签到成功！');
			}else{
				newLog($activity_name.'活动签到异常',$data['openid']);
				$this->apiReturn('很抱歉','401','签到异常');
			}
		}else{
			newLog('未进入'.$activity_name.'活动签到范围，签到失败，签到经度：'.$data['longitude'].' | 纬度：'.$data['latitude'],$data['openid']);
			$this->apiReturn('签到失败','400','请在目的地'.$radius.'范围内签到！');
		}
	}

	public function judge_activity_apply(){		//判断新人活动签到
		$id  = input('post.id',5);		//活动id
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');	//用户openid
		$where['openid'] = $openid;
		$where['activity_id'] = $id;
		$where['status'] = 1;
		$is_apply = M('activity_apply')->where($where)->count();
		if ($is_apply) {   	//未签到
			$res['status'] = false;
		}else{			//已签到
			$res['status'] = true;
		}
		newLog($id.$openid.'|'.$is_apply.$res['status'],$data['openid']);
		$this->apiReturn('查询成功','200',$res);
	}

	public function new_character_test(){    	//新人性格测试提交
		$data  = input('post.');
		$res = M('new_character_test')->save($data);
		if ($res) {
			newLog('提交性格测试，最后得分'.$data['fraction']);
			$this->apiReturn('恭喜您','200','提交成功');
		}else{
			newLog('性格测试提交异常，最后得分'.$data['fraction']);
			$this->apiReturn('很抱歉','401','提交异常！');
		}
	}

	public function judge_character_test(){      //判断是否填写性格测试
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');	//用户openid
		$count = M('new_character_test')->where('openid',$openid)->find();
		if ($count) {
			$res['status'] = true;
			$res['fraction'] = $count['fraction'];
		}else{
			$res['status'] = false;
			$res['fraction'] = 0;

		}
		newLog('接口日志：请求judge_character_test，返回参数'.$res['status'].$res['fraction'],$openid);
		$this->apiReturn('查询成功','200',$res);
	}





}