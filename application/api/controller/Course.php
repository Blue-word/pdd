<?php
namespace app\api\controller;
use think\Page;

class Course extends Common{

	public function _initialize(){
        //定义子类_initialize，不调用父类构造方法
    } 

	public function course_list(){      //课程列表
		$p = input('post.p/d',1);
		$field = 'id,title,picture,place,start_time,end_time';
		$list = M('new_course')->page($p,10)->where('status','gt',0)->where('del_status',0)->field($field)->select();
        if ($list) {
        	foreach ($list as $k => $v) {
	    		$course_time = date('Y-m-d H:i',$v['start_time']);
	    		if (!empty($v['end_time'])) {     //结束时间不为空，拼接时间
	    			$course_time = $course_time.' — '.date('Y-m-d H:i',$v['end_time']);
	    		}
	    		$list[$k]['course_time'] = $course_time;
	    		$list[$k]['picture'] = img_nullchange($v['picture'],'absolute');
	    	}
	    	$count = M('new_course')->where('status','gt',0)->count();
            $pager = new Page($count,10);
            $page =  $pager->totalPages;
            $result['list'] = $list;
            $result['pages'] = $page;
            $result['page'] = $p;
            // dump($result);
	    	$this->apiReturn('查询成功','200',$result);
        }else{
        	$this->apiReturn('查询失败','400','查询失败');
        }
	}

	public function class_type_list(){      //班次类型列表
		$list = M('class_type')->where('id','neq',1)->select();
        $this->apiReturn('查询成功','200',$list);
	}

	public function my_course(){		//我的课程列表
		$p = input('post.p/d',1);
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');
		$field = 'id,title,picture,place,start_time,end_time';
		$list = M('course_apply')->page($p,10)->where('openid',$openid)->order('id desc')->select();
        if ($list) {
        	foreach ($list as $k => $v) {
        		$my_course = M('new_course')->where('id',$v['course_id'])->field($field)->find();
	    		$my_course['picture'] = img_nullchange($my_course['picture'],'absolute');
	    		$course_time = date('Y-m-d H:i',$my_course['start_time']);
	    		if (!empty($my_course['end_time'])) {     //结束时间不为空，拼接时间
	    			$course_time = $course_time.' — '.date('Y-m-d H:i',$my_course['end_time']);
	    		}
	    		$my_course['course_time'] = $course_time;
	    		$my_course['sign_status'] = $v['status'];
	    		$my_course_list[$k] = $my_course;
	    	}
	    	$count = M('course_apply')->where('openid',$openid)->count();
            $pager = new Page($count,10);
            $page =  $pager->totalPages;
            $result['list'] = $my_course_list;
            $result['pages'] = $page;
            $result['page'] = $p;
            // dump($result);
	    	$this->apiReturn('查询成功','200',$result);
        }else{
        	$this->apiReturn('查询失败','400','查询失败');
        }
	}

	public function my_course1(){		//我的课程列表
		$p = input('post.p/d',1);
		$openid = input('post.openid','oHvYl0kZ5VHvBDREMnbs3uxv1eYY');
		$new_id = M('userinfo')->where('openid',$openid)->getField('new_id');
		$field = 'id,title,picture,place,start_time,end_time';
		$list = M('new_apply')->page($p,10)->where('new_id',$new_id)->order('time')->select();
        if ($list) {
        	foreach ($list as $k => $v) {
        		$my_course = M('new_course')->where('id',$v['course_id'])->field($field)->find();
	    		$my_course['picture'] = img_nullchange($my_course['picture'],'absolute');
	    		$course_time = date('Y-m-d H:i',$my_course['start_time']);
	    		if (!empty($my_course['end_time'])) {     //结束时间不为空，拼接时间
	    			$course_time = $course_time.' — '.date('Y-m-d H:i',$v['end_time']);
	    		}
	    		$my_course['course_time'] = $course_time;
	    		$my_course_list[$k] = $my_course;
	    	}
	    	$count = M('new_course')->where('status','gt',0)->count();
            $pager = new Page($count,10);
            $page =  $pager->totalPages;
            $result['list'] = $my_course_list;
            $result['pages'] = $page;
            $result['page'] = $p;
            // dump($result);
	    	$this->apiReturn('查询成功','200',$result);
        }else{
        	$this->apiReturn('查询失败','400','查询失败');
        }
	}

	public function character_test_list(){     //性格测试列表
        $list = M('character_test')->where('pid',0)->select();
        foreach ($list as $k => $v) {
            $list[$k]['option'] = M('character_test')->where('pid',$v['id'])->select();
        }
        $this->apiReturn('查询成功','200',$list);
	}

}