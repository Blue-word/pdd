<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Page;
use think\Model;
use think\db\Query;

class Report extends Base{
    /*
    *数据源列表
    */
	public function data_source(){
        $role_id = session('role_id');
        $uid = session('uid');
        $organization = M('admin')->where('uid',$uid)->getField('organization');
        if ($role_id == 2) {  //十二中支，三级
            $where['zhongzhi'] = $organization;
        }elseif ($role_id == 3) {   //六十四营服，四级
            $where['market_server_unit'] = $organization;
        }
        $p = I('p',1);
        $model = M('data_source');
		$list = $model->where($where)->page($p,10)->select();
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y-m-d',$v['time']);
        }
        $count = $model->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);
		return $this->fetch();
	}
    /*
    *明细详情表
    */
    public function detail_list(){
        $role_id = session('role_id');
        $uid = session('uid');
        $organization = M('admin')->where('uid',$uid)->getField('organization');
        if ($role_id == 2) {  //十二中支，三级
            $where['organization'] = $organization;
        }elseif ($role_id == 3) {   //六十四营服，四级
            $where['level4_organization'] = $organization;
        }
        // dump($where);
        $p = I('p',1);
        $model = M('detail_list');
        $list = $model->where($where)->page($p,10)->select();
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y-m-d',$v['time']);
        }
        $count = $model->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        return $this->fetch();
    }

    public function data_source_import(){       //数据源导入
        vendor('phpexcel.PHPExcel.Shared.Date');    
        $model = model('data_source');
        $tableName = "data_source";
        $title = array("zhongzhi_code","zhongzhi","market_server_unit","business_area_code","business_unit_code","business_unit_name","business_group_code","business_group_name","salesman_code","salesman_name","salesman_id_number","rank_name","cate","time");
        $result = $this->importExcel($tableName,$title);
        if ($result['status'] == true) {   //success
            Db::startTrans();
            $shared = new \PHPExcel_Shared_Date();
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['time'] = $shared->ExcelToPHP($v['time']);
            }
            $res = $model->saveAll($result['data']);
            if ($res) {
                Db::commit(); 
                exit (json_encode(1));
            }else{
                Db::rollback();
                exit (json_encode(0));
            }
        }else{
            $this->error($result['data']);
        }
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

    // 创说会新人签到表导出
    public function new_sign_list_export(){
        $id = input('post.id');   //活动id
        $field = 'openid';
        $user = M('activity_apply')->where('activity_id',$id)->field($field)->select();
        // dump($data);
        $subject = "创说会新人签到表导出";
        $title = array("新人openID");
        $asd = $this->exportExcel($user,$title,$subject); 
        // dump($asd);
    }

    // 创说会新人签到表导出——>新人报名表导入
    public function new_apply_list_import(){
        $id = input('post.id');
        $tableName = "activity_apply";
        $title = array("openid");
        $result = $this->importExcel($tableName,$title);
        if ($result['status'] == true) {   //success
            $model = model('activity_apply');
            Db::startTrans();
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['activity_id'] = $id;
                $result['data'][$k]['is_invite'] = 1;
            }
            $res = $model->saveAll($result['data']);
            $count = count($result['data']);
            $res_1 = M('new_add')->where('id',$id)->setInc('apply_number',$count);
            if ($res && $res_1) {
                // 发送推送消息
                $act_info = M('new_add')->where('id',$id)->field('title,place,start_time,end_time')->find();
                $act_time = date('Y-m-d H:i',$act_info['start_time']);      //活动时间
                if (!empty($act_time['end_time'])) {     //结束时间不为空，拼接时间
                    $act_time = $act_time.' — '.date('Y-m-d H:i',$act_info['end_time']);
                }
                $content = '您已报名『'.$act_info['title'].'』 , 活动时间：'.$act_time.' , 活动地点：'.$act_info['place'].' , 请准时前往签到';
                $return_code = 3;
                foreach ($result['data'] as $k => $v) {
                    $msg_data = array(
                        'touser' => $v['openid'],
                        'msgtype' => "text",
                        'text' => array(
                            'content' => $content
                        ),
                    ); 
                    $res_2 = $this->customer_service_send($msg_data);
                }
                if ($res_2['errmsg'] == 'ok') {
                    Db::commit();
                    $return_code = 1;
                }else{
                    Db::rollback();
                    $return_code = 2;
                }
                exit (json_encode($return_code));
            }else{
                Db::rollback();
                exit (json_encode(0));
            }
        }else{
            $this->error($result['data']);
        }
    }

    // 课程新人报名表导入
    public function course_apply_list_import(){
        $id = input('post.id');
        $tableName = "course_apply";
        $title = array("openid");
        $result = $this->importExcel($tableName,$title);
        // dump($result);
        if ($result['status'] == true) {   //success
            $model = model('course_apply');
            Db::startTrans();
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['course_id'] = $id;
                $result['data'][$k]['is_invite'] = 1;
                $result['data'][$k]['time'] = time();
            }
            $res = $model->saveAll($result['data']);
            $count = count($result['data']);
            $res_1 = M('new_course')->where('id',$id)->setInc('apply_number',$count);
            if ($res && $res_1) {
                // 发送推送消息
                $act_info = M('new_course')->where('id',$id)->field('title,place,start_time,end_time')->find();
                $act_time = date('Y-m-d H:i',$act_info['start_time']);      //活动时间
                if (!empty($act_time['end_time'])) {     //结束时间不为空，拼接时间
                    $act_time = $act_time.' — '.date('Y-m-d H:i',$act_info['end_time']);
                }
                $content = '您已报名『'.$act_info['title'].'』 , 课程时间：'.$act_time.' , 课程地点：'.$act_info['place'].' , 请准时前往签到';
                $return_code = 3;
                foreach ($result['data'] as $k => $v) {
                    $msg_data = array(
                        'touser' => $v['openid'],
                        'msgtype' => "text",
                        'text' => array(
                            'content' => $content
                        ),
                    ); 
                    $res_2 = $this->customer_service_send($msg_data);
                }
                if ($res_2['errmsg'] == 'ok') {
                    Db::commit();
                    $return_code = 1;
                }else{
                    Db::rollback();
                    $return_code = 2;
                }
                exit (json_encode($return_code));
            }else{
                Db::rollback();
                exit (json_encode(0));
            }
        }else{
            $this->error($result['data']);
        }
    }

    public function send_msg(){
        $openid = 'oHvYl0kZ5VHvBDREMnbs3uxv1eYY';
        $content = '欢迎关注太平人寿江苏分公司';
        $msg_data = array(
            'touser' => $openid,
            'msgtype' => "text",
            'text' => array(
                'content' => $content
            ),
        );
        $res = $this->customer_service_send($msg_data);
        dump($res);
    }
    /*
    *明细表导出
    */
    public function detail_list_export(){
        $role_id = session('role_id');
        $uid = session('uid');
        $organization = M('admin')->where('uid',$uid)->getField('organization');
        if ($role_id == 2) {  //十二中支，三级
            $where['organization'] = $organization;
        }elseif ($role_id == 3) {   //六十四营服，四级
            $where['level4_organization'] = $organization;
        }
        $field = 'organization,level4_organization,business_unit,business_group,new_name,id_number,sign_time,sign_status,recommend_code,recommend_name,recommend_rank,is_payment,is_insurance_zige';
        $list = M('detail_list')->where($where)->field($field)->select();
        // dump($list);
        $subject = "明细表";
        $title = array("机构","四级机构","营业部","营业组","新人姓名","身份证号","提交问卷时间","签到是否有效","推荐人代码","推荐人姓名","推荐人职级","是否缴费","是否愿意拥有保险销售资格");
        $asd = $this->exportExcel($list,$title,$subject); 
        dump($asd);
    }
    public function complex_header_export(){
        
    }

}