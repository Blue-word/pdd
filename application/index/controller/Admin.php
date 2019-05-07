<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\db\Query;
use think\Model;
use app\index\logic;
use think\Loader;

class Admin extends Base{

    public function login(){   //登录
        if(request()->isPost()){
            $data = I('post.');
            $validate = Loader::validate('Admin');
            if(!$validate->check($data)){
                $this->error("请勿重复提交");
            }
            if(session('?uid') && session('uid')>0){
                $this->error("您已登录",U('index/index/index'));
            }
            $logic_model = model('AdminLogic','logic');
            $logic_login = $logic_model->login($data);
            if (!$logic_login['code']) {
                $admin_info = $logic_login['info'];
                if ($admin_info['delete_status'] == 0) {  //账号被高级别管理员停用
                    $this->error("您的管理员账号已被停用，请联系上级管理员",U('index/admin/login'));
                }
                session('uid',$admin_info['uid']);
                session('role_id',$admin_info['role_id']);
                session('role_name',$admin_info['role_name']);
                session('admin_name',$admin_info['name']);
                session('act_list',$admin_info['act_list']);
                M('admin')->where('uid',$admin_info['admin_uid'])->save(array('last_login'=>time(),'last_ip'=>getIP()));
                adminLog($admin_info['name']."登录");
                $this->success("登陆成功",U('index/index/index'));
            }else{
                $this->error($logic_login['msg'],U('index/admin/login'));
            }
        }
        return $this->fetch();
    }

    public function logout(){   //退出登陆
        session_unset();
        session_destroy();
        Session::clear();
        $this->success("退出成功",U('index/Admin/login'));
    }

    public function login_v2(){
        return $this->fetch();
    }

    public function admin_list(){
        $role_id = session('role_id');
        $list = M('admin')->field('password',true)->select();
        foreach ($list as $k => $v) {
            $list[$k]['role_name'] = M('admin_role')->where('role_id',$v['role_id'])->getField('role_name');
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function admin_info(){
        $uid = input('uid');
        if($uid){
            $info = M('admin')->where('uid',$uid)->find();
            $this->assign('info',$info);
        }
        $act = empty($uid) ? 'add' : 'edit';
        $role = D('admin_role')->where('del_status',0)->where('role_id','neq',1)->select();
        $role_id = '1';  
        $role_id = explode(',',$role_id);
        $pid_admin = M('admin')->where('role_id','in',$role_id)->field('uid,info')->select();
        // dump($info);
        $this->assign('act',$act);
        $this->assign('role',$role);
        $this->assign('pid_admin',$pid_admin);
        return $this->fetch();
    }

    public function admin_handle(){
        $data = input('post.');
        $model = model('Admin');
        if(empty($data['password'])){
            unset($data['password']);
        }else{
            $data['password'] = encrypt($data['password']);
        }
        if($data['act'] == 'add'){
            unset($data['admin_id']);
            $data['time'] = time();
            if(D('admin')->where("name", $data['name'])->count()){
                $this->error("此用户名已被注册，请更换",U('Admin/Admin/admin_info'));
            }else{
                $res = $model->allowField(true)->save($data);
                adminLog(session('admin_name')."添加新管理员--".$data['name']);
            }
        }
        
        if($data['act'] == 'edit'){
            $res = $model->allowField(true)->save($data,['uid' => $data['uid']]);
            adminLog(session('admin_name')."修改管理员（".$data['name']."）信息");
        }
        
        if($data['act'] == 'ajax'){
            $res = D('admin')->where('uid', $data['id'])->save(['delete_status'=>$data['status']]);
            if ($data['status'] == 1) { //启用
                $string = '启用';
            }else{
                $string = '停用';
            }
            $admin_name = M('admin')->where('uid',$data['id'])->getField('name');
            adminLog(session('admin_name').$string."管理员（".$admin_name."）");
            exit(json_encode($data));
        }
        
        if($res){
            $this->success("操作成功",U('index/admin/admin_list'));
        }else{
            $this->error("操作失败",U('index/admin/admin_info',array('uid'=>$data['uid'])));
        }
    }

    public function role_list(){
        $list = M('admin_role')->where('role_id','neq',1)->where('del_status',0)->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function role_info(){
        $role_id = input('role_id');
        if($role_id){
            $info = M('admin_role')->where('role_id',$role_id)->find();
            $this->assign('info',$info);
        }
        $act = empty($role_id) ? 'add' : 'edit';
        $this->assign('act',$act);
        $detail = array();
        if($role_id){
            $detail = M('admin_role')->where("role_id",$role_id)->find();
            $detail['act_list'] = explode(',', $detail['act_list']);
            $this->assign('detail',$detail);
        }
        $right = M('system_menu')->order('id')->select();
        foreach ($right as $val){
            if(!empty($detail)){
                $val['enable'] = in_array($val['id'], $detail['act_list']);
            }
            $modules[$val['group']][] = $val;
        }
        //权限组
        $group = array('goods'=>'商品管理','xuanhui'=>'选惠管理','activity'=>'活动管理','permission'=>'权限管理');
        // dump($modules);
        $this->assign('group',$group);
        $this->assign('modules',$modules);
        return $this->fetch();
    }

    public function role_handle(){
        $data = input('post.');
        $model = model('AdminRole');
        if ($data['act'] == 'add') {
            $data['act_list'] = is_array($data['act_list']) ? implode(',', $data['act_list']) : '';
            if(empty($data['act_list']))
            $this->error("请进行权限分配!");
            $admin_role = Db::name('admin_role')->where('role_name',$data['role_name'])->find();
            if($admin_role){
                $this->error("已存在相同的角色名称!");
            }else{
                $res = $model->allowField(true)->save($data);
                adminLog(session('admin_name')."添加新角色--".$data['role_name']);
                $data['role_id'] = $model->role_id;
            }
        }       
        if($data['act'] == 'edit'){
            $data['act_list'] = is_array($data['act_list']) ? implode(',', $data['act_list']) : '';
            if(empty($data['act_list']))
            $this->error("请进行权限分配!");
            $admin_role = Db::name('admin_role')->where(['role_name'=>$data['role_name'],'role_id'=>['<>',$data['role_id']]])->find();
            if($admin_role){
                $this->error("已存在相同的角色名称!");
            }else{
                unset($data['act']);
                $res = $model->allowField(true)->where('role_id',$data['role_id'])->save($data);
                adminLog(session('admin_name')."修改角色（".$data['role_name']."）信息");
            }
        }
        if($data['act'] == 'del'){
            $res = D('admin_role')->where('role_id', $data['id'])->save(['del_status'=>1]);
            $role_name = M('admin_role')->where('role_id',$data['id'])->getField('role_name');
            adminLog(session('admin_name')."删除角色（".$role_name."）");
            exit(json_encode(1));
        }
        if($res){
            $this->success("操作成功!",U('index/Admin/role_info',array('role_id'=>$data['role_id'])));
        }else{
            $this->error("操作失败!",U('index/Admin/role_info',array('role_id'=>$data['role_id'])));
        }
    }

    public function right_list(){
        $list = M('system_menu')->where('is_del',0)->select();
        // dump($list);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function right_info(){
        $id = input('id');
        if($id){
            $info = M('system_menu')->where(array('id'=>$id))->find();
            $info['right'] = explode(',', $info['right']);
            $this->assign('info',$info);
        }
        $group = array('goods'=>'商品管理','xuanhui'=>'选惠管理','activity'=>'活动管理','permission'=>'权限管理');
        $planPath = APP_PATH.'index/controller';
        $planList = array();
        $dirRes = opendir($planPath);
        while($dir = readdir($dirRes))
        {
            if(!in_array($dir,array('.','..','.svn'))){
                $planList[] = basename($dir,'.php');    //文件列表
            }
        }
        // dump($planList);
        $act = empty($id) ? 'add' : 'edit';
        $this->assign('act',$act);
        $this->assign('planList',$planList);
        $this->assign('group',$group);
        
        return $this->fetch();
    }

    public function right_handle(){
        if(request()->isPost()){
            $data = input('post.');
            if($data['act'] == 'edit'){
                $data['right'] = implode(',',$data['right']);
                unset($data['act']);
                $res = M('system_menu')->where(array('id'=>$data['id']))->save($data);
                if ($res) {
                    adminLog(session('admin_name')."修改权限（".$data['name']."）信息");
                    $this->success('操作成功',U('admin/right_list'));
                }else{
                    $this->success('操作失败',U('admin/right_info'));
                }
            }
            if ($data['act'] == 'add') {
                $data['right'] = implode(',',$data['right']);
                if(M('system_menu')->where(array('name'=>$data['name']))->count()>0){
                    $this->error('该权限名称已添加，请检查',U('admin/right_info'));
                }
                unset($data['id'],$data['act']);
                $res = M('system_menu')->add($data);
                if ($res) {
                    adminLog(session('admin_name')."添加新权限--".$data['name']);
                    $this->success('操作成功',U('admin/right_info',array('id'=>$res)));
                }else{
                    $this->success('操作失败',U('admin/right_info',array('id'=>$data['id'])));
                }
            }
            // if($data['act'] == 'del'){
            //     $res = D('system_menu')->where('id', $data['id'])->save(['is_del'=>1]);
            //     exit(json_encode($data));
            // }
            exit;
        }
    }

    function ajax_get_action()
    {
        $control = I('controller');
        $advContrl = get_class_methods("app\\index\\controller\\".str_replace('.php','',$control));
        $baseContrl = get_class_methods('app\index\controller\Base');
        $diffArray  = array_diff($advContrl,$baseContrl);
        $html = '';
        // return $diffArray;
        foreach ($diffArray as $val){
            $html .= "<option value='".$val."'>".$val."</option>";
        }
        exit($html);
    }

    public function log_list(){
        $list = M('admin_log')->select();
        foreach ($list as $k => $v) {
            $list[$k]['admin_name'] = M('admin')->where('uid',$v['admin_id'])->getField('name');
        }
        // dump($list);
        $this->assign('list',$list);
        return $this->fetch();
    }
    /*
    *管理员密码修改
    */
    public function admin_password(){
        $uid = session('uid');
        if (request()->isPost()) {
            $original_pas = input('post.original_pas');
            $original_pas = encrypt($original_pas);
            $password = input('post.password');
            $password = encrypt($password);
            $res = M('admin')->where(['uid'=>$uid,'password'=>$original_pas])->count();
            if ($res) {
                $res_1 = M('admin')->where('uid',$uid)->save(['password'=>$password]);
                if ($res_1) {
                    $this->success('密码修改成功');
                }else{
                    $this->success('密码修改失败');
                }
            }else{
                $this->success('原始密码错误，请重新输入');
            }
        }
        return $this->fetch();
    }
}
