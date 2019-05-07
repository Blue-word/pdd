<?php
namespace app\index\controller;
use think\Controller;
use think\Model;
use think\Session;
use think\db\Query;
use think\Paginator;

class Goods extends Common{

    public function _initialize(){
        parent::_initialize();  //关闭时不调用父类检验token
        $this->sex = C('SEX');
    }

    public function position_set(){
        return $this->fetch();
    }
    /**
    * 商品列表 
    **/
    public function goods_list(){
        $list = M('goods')->where($where)->select();
        if ($list) {
            $list = M('goods')->order('id desc')->paginate(9);
            foreach ($list as $key => $value) {
                $list[$key]['category_name'] = M('category')->where('id',$value['id'])->getField('name');
                $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
            }
            $page = $list->render();
            $this->assign('page', $page);
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function goods_info(){
        $id = input('id');
        if($id){
            $info = M('goods')->where('id',$id)->find();
            // $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            // $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            $picture = explode(',',$info['picture']);
            $cover_pic = explode(',',$info['cover_pic']);
            //分类选中
            $category_info = $this->getCategoryInfo($info['category']);
            $this->assign('info',$info);
            $this->assign('category_info',$category_info);
        }
        $category_where['level'] = 1;
        $category_where['is_delete'] = 0;
        $category_first = M('category')->where($category_where)->select();
        $act = empty($id) ? 'add' : 'edit';
        //地区——点位
        $area = M('area')->where('status',1)->select();
        foreach ($area as $key => $value) {
            $point_where['area'] = $value['id'];
            $point_where['status'] = 1;
            $point_info = M('point')->where($point_where)->select();
            if ($point_info) {
                $area[$key]['point'] = $point_info;
            }else{
                $area[$key]['point'] = array();
            }
            // dump($point_info);
        }
        // dump($category_info);
        // dump($area);
        $this->assign('act',$act);
        $this->assign('pic_list',$picture);
        $this->assign('cover_list',$cover_pic);
        $this->assign('area',$area);
        $this->assign('category_first',$category_first);
        return $this->fetch();
    }

    public function goods_handle(){
        $data = input('post.');
        $model = model('Goods');
        // dump($data);die;
        // $data['picture'] = $data['image'];
        if($data['act'] == 'add'){
            unset($data['id'],$data['image']);           
            $data['add_time'] = time();
            // $data['start_time'] = strtotime($data['start_time']);
            // $data['end_time'] = strtotime($data['end_time']);
            // $data['uid']  = Session::get('uid');
            if ($data['picture']) {
                $data['picture'] = implode(',',$data['picture']);
            }
            if ($data['cover_pic']) {
                $data['cover_pic'] = implode(',',$data['cover_pic']);
            }
            $data['area'] = implode(',',$data['area']);
            // dump($data);
            $res = $model->allowField(true)->save($data);
        }
        
        if($data['act'] == 'edit'){
            $data['time'] = time();
            // $data['start_time'] = strtotime($data['start_time']);
            // $data['end_time'] = strtotime($data['end_time']);
            $data['picture'] = implode(',',$data['picture']);
            // dump($data);
            $res = $model->allowField(true)->save($data,['id' => $data['id']]);
            // dump($model->getLastsql());
            // dump($res);
        }
        
        // if($data['act'] == 'del'){
        //     $res = D('new_course')->where('id', $data['id'])->save(['del_status'=>1]);
        //     exit(json_encode($data));
        // }

        if($data['act'] == 'audit' || $data['act'] == 'ajax'){
            // $audit_uid = Session::get('uid');
            $res = $model->where('id', $data['id'])->save(['status'=>$data['status']]);
            exit(json_encode($res));
            // dump($res);
        }
        
        if($res){
            $this->success("操作成功",U('index/goods/goods_list'));
        }else{
            $this->error("操作失败",U('index/goods/goods_info',array('id'=>$data['id'])));
        }
    }

    public function goods_view(){
        $id = input('id');
        if($id){
            $info = M('goods')->where('id',$id)->find();
            // $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            // $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            // $info['admin_name'] = M('admin')->where('uid',$info['uid'])->getField('name');
            // $info['audit_name'] = M('admin')->where('uid',$info['audit_uid'])->getField('name');
            $this->assign('info',$info);
        }
        // dump($info);
        return $this->fetch();
    }

    public function weixin_info(){
        $id = input('id');
        if($id){
            $info = M('new_survey')->where('id',$id)->find();
            $info['time'] = date('Y-m-d H:i:s',$info['time']);
            $info['sex'] = $this->sex[$info['sex']];
            $info['wx'] = M('userinfo')->where('openid',$info['openid'])->find();
            $info['wx']['address'] =  $info['wx']['country']. $info['wx']['province'].'省'. $info['wx']['city'].'市' ;
            // $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            $this->assign('info',$info);
        }
        // dump($info);
        return $this->fetch();
    }

    public function category_list(){
        $type = input('post.type',1);
        $id = input('post.id');
        if ($id) {
            $where['pid'] = $id;
        }
        // $where['level'] = $type;
        $where['is_delete'] = 0;
        $list = M('category')->where($where)->select();
        foreach ($list as $k => $v) {
             // $list[$k]['admin_name'] = M('admin')->where('uid',$v['uid'])->getField('name');
            $list[$k]['sex'] = $this->sex[$v['sex']];
            $list[$k]['time'] = date('Y-m-d H:i:s',$v['time']);
            if ($v['level'] == 1) {
                $list[$k]['category_name'] = '一级分类';
            }elseif ($v['level'] == 2) {
                $list[$k]['category_name'] = '二级分类';
            }elseif ($v['level'] == 3) {
                $list[$k]['category_name'] = '三级分类';
            }
        }
        $where['level'] = 2;
        $where['is_delete'] = 0;
        $second_category_list = M('category')->where($where)->select();
        
        // dump($list);
        $category_where['level'] = 1;
        $category_where['is_delete'] = 0;
        $category_first = M('category')->where($category_where)->select();
        $this->assign('list',$list);
        $this->assign('second_category_list',$second_category_list);
        $this->assign('category_first',$category_first);
        return $this->fetch();
    }

    public function category_handle(){
        $data = input('post.');
        // var_dump($data);die;
        if(empty($data['id'])){      //无id为新增
            if ($data['sonCategoryId']) {
                $add_data = array(
                    'name' => $data['name'],
                    'level' => 3,
                    'pid' => $data['sonCategoryId'],
                );
            }elseif ($data['firstValue']) {
                $add_data = array(
                    'name' => $data['name'],
                    'level' => 2,
                    'pid' => $data['firstValue'],
                );
            }else{
                $add_data = array(
                    'name' => $data['name'],
                    'level' => 1,
                    'pid' => 0,
                );
            }
            $res = M('category')->save($add_data);
            $string = '操作成功';
        }else{      
            if ($data['act'] == 'del') {    //有id有del为删除操作
                // return (json_encode($data));
                // exit (json_encode($data));
                $res = M('category')->where('id',$data['id'])->save(['is_delete'=>1]);
                exit (json_encode($res));
            }else{      //有id无del为编辑
                if ($data['sonCategoryId']) {
                    $add_data = array(
                        ''
                    );
                }
                $res = M('category')->add($data);
                $string = '操作失败';
            }
        }
        // if ($res) {
        //     $this->redirect('index/goods/category_list');
        // }
        $this->success("$string",U('index/goods/category_list'));
        // $this->redirect('index/pacificocean/class_type_list');
    }

    public function tao_goods_list(){
        $category = input('post.category');
        if ($category) {
            $where['category'] = $category;
        }
        $where['is_delete'] = 0;
        $list = M('tao_goods')->where($where)->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['category_name'] = M('category')->where('id',$value['id'])->getField('name');
                $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
            }
        }
        
        // dump($category_first);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function tao_goods_info(){
        $id = input('id');
        if($id){
            $info = M('tao_goods')->where('id',$id)->find();
            // $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            // $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            //分类选中
            $category_info = $this->getCategoryInfo($info['category']);
            $picture = explode(',',$info['picture']);
            $this->assign('info',$info);
            $this->assign('category_info',$category_info);
        }
        $category_where['level'] = 1;
        $category_where['is_delete'] = 0;
        $category_first = M('category')->where($category_where)->select();
        $act = empty($id) ? 'add' : 'edit';
        // dump($info);
        $this->assign('act',$act);
        $this->assign('pic_list',$picture);
        $this->assign('category_first',$category_first);
        return $this->fetch();
    }

    public function tao_goods_handle(){
        $data = input('post.');
        $model = model('TaoGoods');
        // dump($data);die;
        // $data['picture'] = $data['image'];
        if($data['act'] == 'add'){
            unset($data['id'],$data['image']);           
            $data['add_time'] = time();
            if ($data['picture']) {
                $data['picture'] = implode(',',$data['picture']);
            }else{
                $data['picture'] = '';
            }
            // dump($data);
            $res = $model->allowField(true)->save($data);
        }
        
        if($data['act'] == 'edit'){
            $data['picture'] = implode(',',$data['picture']);
            // dump($data);
            $res = $model->allowField(true)->save($data,['id' => $data['id']]);
            // dump($res);
            // dump($model->getLastsql());
        }
        
        // if($data['act'] == 'del'){
        //     $res = D('new_course')->where('id', $data['id'])->save(['del_status'=>1]);
        //     exit(json_encode($data));
        // }

        if($data['act'] == 'audit' || $data['act'] == 'ajax'){
            // $audit_uid = Session::get('uid');
            $res = $model->where('id', $data['id'])->save(['status'=>$data['status']]);
            // dump($model->getLastsql());
            exit(json_encode($res));
            // dump($res);
        }
        
        if($res){
            $this->success("操作成功",U('index/goods/tao_goods_list'));
        }else{
            $this->error("操作失败",U('index/goods/tao_goods_info',array('id'=>$data['id'])));
        }
    }

    public function tao_goods_view(){
        $id = input('id');
        if($id){
            $info = M('goods')->where('id',$id)->find();
            // $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            // $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            // $info['admin_name'] = M('admin')->where('uid',$info['uid'])->getField('name');
            // $info['audit_name'] = M('admin')->where('uid',$info['audit_uid'])->getField('name');
            $this->assign('info',$info);
        }
        // dump($info);
        return $this->fetch();
    }

    public function getSonCategory(){
        $id = input('category_id');
        $type = input('type');
        $category_where['pid'] = $id;
        $category_where['level'] = $type;
        $category_where['is_delete'] = 0;
        $category_first = M('category')->where($category_where)->select();
        $this->ajaxReturn($category_first);
    }

    public function activity_list(){
        $status = input('post.status');
        if ($status) {
            $where['status'] = $status;
        }
        $where['is_delete'] = 0;
        $list = M('activity')->where($where)->order('id desc')->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['start_time'] = date('Y-m-d H:i',$value['start_time']);
                $list[$key]['end_time'] = date('Y-m-d H:i',$value['end_time']);
                // $list[$key]['picture'] = $this->imageChange($value['picture']);
            }
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function activity_info(){
        $id = input('id');
        if($id){
            $info = M('activity')->where('id',$id)->find();
            $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            // $info['picture'] = $this->imageChange($info['picture']);
            $picture = explode(',',$info['picture']);
            $this->assign('info',$info);
        }
        $act = empty($id) ? 'add' : 'edit';
        // dump($info);
        $this->assign('act',$act);
        $this->assign('pic_list',$picture);
        return $this->fetch();
    }

    public function activity_handle(){
        $data = input('post.');
        $model = model('Activity');
        dump($data);
        // die;
        // $data['picture'] = $data['image'];
        if($data['act'] == 'add'){
            unset($data['id'],$data['image']);           
            $data['add_time'] = time();
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $data['picture'] = implode(',',$data['picture']);
            // dump($data);
            $res = $model->allowField(true)->save($data);
        }
        
        if($data['act'] == 'edit'){
            $data['picture'] = implode(',',$data['picture']);
            // dump($data);
            $res = $model->allowField(true)->save($data,['id' => $data['id']]);
        }
        
        // if($data['act'] == 'del'){
        //     $res = D('new_course')->where('id', $data['id'])->save(['del_status'=>1]);
        //     exit(json_encode($data));
        // }

        if($data['act'] == 'audit' || $data['act'] == 'ajax'){
            $res = $model->where('id', $data['id'])->save(['status'=>$data['status']]);
            exit(json_encode($data));
        }
        
        if($res){
            $this->success("操作成功",U('index/goods/activity_list'));
        }else{
            $this->error("操作失败",U('index/goods/activity_info',array('id'=>$data['id'])));
        }
    }

    public function activity_view(){
        $id = input('id');
        if($id){
            $info = M('activity')->where('id',$id)->find();
            $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            $info['picture'] = $this->imageChange($info['picture']);
            $this->assign('info',$info);
        }
        return $this->fetch();
    }

    /**
     * 获取分类信息
     * @author: 蓝勇强
     * Date: 2019/2/2 10:57
     * @param int $category_id
     * @param int $type
     * @return array
     */
    public function getCategoryInfo($category_id=0,$type=1){
        $type = I('get.type');
        if ($type == 2) {   //ajax
            $category_id = I('gey.category_id',0);
        }else{
            $type = 1;
        }
        // dump($type);
        // dump($category_id);
        $return = array();
        $res_1 = $res_2 = $res_3 = array();
        $res_1 = M('category')->where('id='.$category_id)->find();
        if ($res_1['level'] == 1) {
            $return['first'] = $res_1;
        }elseif ($res_1['level'] ==  2) {
            $res_2 = M('category')->where('id='.$res_1['pid'])->find();
        }elseif ($res_1['level'] ==  3) {
            $res_2 = M('category')->where('id='.$res_1['pid'])->find();
            if ($res_2['pid'] == 0) {
            }else{
                $res_3 = M('category')->where('id='.$res_2['pid'])->find();
            }
        }
        $return['first'] = $res_3;
        $return['second'] = $res_2;
        $return['third'] = $res_1;
        if ($type == 2) {   //ajax  json
            $this->ajaxReturn($return);
            // exit (json_encode($return));
        }else{
            return $return;
        }
    }

    public function point_list(){
        $list = M('point')->where($where)->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['area_name'] = M('area')->where('id',$value['area'])->getField('name');
                // $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
            }
        }
        // dump($list);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function point_info(){
        $id = input('id');
        if($id){
            $info = M('point')->where('id',$id)->find();
            //分类选中
            $picture = explode(',',$info['picture']);
            $this->assign('info',$info);
        }
        $act = empty($id) ? 'add' : 'edit';
        // dump($info);
        $this->assign('act',$act);
        $this->assign('pic_list',$picture);
        return $this->fetch();
    }

    public function point_handle(){
        $data = input('post.');
        $model = model('Point');
        // dump($data);die;
        // $data['picture'] = $data['image'];
        if($data['act'] == 'add'){
            unset($data['id'],$data['image']);           
            // $data['add_time'] = time();
            if ($data['picture']) {
                $data['picture'] = implode(',',$data['picture']);
            }else{
                $data['picture'] = '';
            }
            $res = $model->allowField(true)->save($data);
        }
        
        if($data['act'] == 'edit'){
            $data['picture'] = implode(',',$data['picture']);
            $res = $model->allowField(true)->save($data,['id' => $data['id']]);
        }
        
        // if($data['act'] == 'del'){
        //     $res = D('new_course')->where('id', $data['id'])->save(['del_status'=>1]);
        //     exit(json_encode($data));
        // }

        if($data['act'] == 'audit' || $data['act'] == 'ajax'){
            $res = $model->where('id', $data['id'])->save(['status'=>$data['status']]);
            exit(json_encode($res));
        }
        if($res){
            $this->success("操作成功",U('index/goods/point_list'));
        }else{
            $this->error("操作失败",U('index/goods/point_info',array('id'=>$data['id'])));
        }
    }

    



}
