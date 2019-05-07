<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use think\Paginator;
use think\Db;
use think\db\Query;
use think\Model;

class Index extends Base{

    public function index(){
        return $this->fetch();
    }

    public function index_v1(){
        $nowtime = time();   //获取现在的时间戳
        $starttime = mktime(0,0,0,date("m"),1,date("Y"));   //当月第一天时间戳
        $last_starttime = mktime(0,0,0,date("m")-1,1,date("Y"));   //上月第一天时间戳
        $last_endtime = $starttime-1;   //上月最后一天时间戳
        $map['time'] = array('between',"$starttime,$nowtime");
        $last_map['time'] = array('between',"$last_starttime,$last_endtime");

        //管理员日志
        $admin_log = Db::name('admin_log')->order('log_id desc')->paginate(5)->each(function ($item,$key){
            $item['admin_name'] = M('admin')->where('uid',$item['admin_id'])->getField('name');
            return $item;
        });
        $page = $admin_log->render();
        $this->assign('page', $page);
        $this->assign('list',$list);
        $this->assign('admin_log',$admin_log);
        $this->assign('new_log',$new_log);
        return $this->fetch();
    }

    public function index_v2(){
        return $this->fetch();
    }

    public function index_v3(){
        return $this->fetch();
    }

    public function index_v4(){
        return $this->fetch();
    }

    public function index_v5(){
        return $this->fetch();
    }
}
