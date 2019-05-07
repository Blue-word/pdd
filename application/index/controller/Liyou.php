<?php
namespace app\index\controller;
use think\Controller;
use think\Session;

class Liyou extends Common{

    public function _initialize(){
        //初始化
        if (!Session::has('position')) {
            Session::set('position',1);
        }
        $this->position = Session::get('position');
        $this->assign('position',$this->position);
    }

    public function index(){
        $position = Session::get('position');
        // dump($position);
        //派样商品
        $goods_where['area'] = array('like',$position.'%');
        $goods_where['is_delete'] = 0;
        $goods_list = M('goods')->where($goods_where)->order('love_num desc')->limit(3)->select();
        if ($goods_list) {
            foreach ($goods_list as $key => $value) {
                $goods_list[$key]['category_name'] = M('category')->where('id',$value['category'])->getField('name');
                $goods_list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
                $goods_list[$key]['picture'] = explode(',', $value['picture']);
                $goods_list[$key]['content'] = $this->stringModify($value['content'],30,1);
                $category_name_first = $this->getcFirstCategory('category',3,$value['category']);
                if (!$category_name_first['code']) {
                    $goods_list[$key]['category_name_first'] = $category_name_first['info']['name'];
                }else{
                    $goods_list[$key]['category_name_first'] = '';
                }
            }
        }
        // dump($goods_list);
        
        //活动
        $activity_where['status'] = array('in','1,2,3');
        $activity_where['is_delete'] = 0;
        $activity_list = M('activity')->where($activity_where)->order('id desc')->limit(3)->select();
        if ($activity_list) {
            foreach ($activity_list as $key => $value) {
                $activity_list[$key]['start_time'] = date('Y-m-d H:i',$value['start_time']);
                $activity_list[$key]['end_time'] = date('Y-m-d H:i',$value['end_time']);
                $activity_list[$key]['title'] = $this->stringModify($value['title'],20,1);
                // $activity_list[$key]['picture'] = $this->imageChange($value['picture']);
            }
        }
        // dump($activity_list);
        //选惠
        $tao_where['area'] = array('like',$position.'%');
        $tao_where['is_delete'] = 0;
        $tao_list = M('tao_goods')->where($tao_where)->order('id desc')->limit(3)->select();
        if ($tao_list) {
            foreach ($tao_list as $key => $value) {
                $tao_list[$key]['category_name'] = M('category')->where('id',$value['id'])->getField('name');
                $category_name_first = $this->getcFirstCategory('category',3,$value['category']);
                if (!$category_name_first['code']) {
                    $tao_list[$key]['category_name_first'] = $category_name_first['info']['name'];
                }else{
                    $tao_list[$key]['category_name_first'] = '';
                }
                $tao_list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
                // $tao_list[$key]['picture'] = $this->imageChange($value['picture']);
                $tao_list[$key]['content'] = $this->stringModify($value['content'],20,1);
            }
        }
        //轮播
        $carousel_map = $this->carouselMapList();
        // dump($carousel_map);
        $this->assign('goods_list',$goods_list);
        $this->assign('activity_list',$activity_list);
        $this->assign('tao_list',$tao_list);
        $this->assign('carousel_map',$carousel_map);
        return $this->fetch();
    }

    public function u_paiyang(){
        $position = Session::get('position');
        $category = input('post.category');
        if ($category) {
            $where['category'] = $category;
        }
        $where['area'] = array('like',$position.'%');
        $where['is_delete'] = 0;
        $list = M('goods')->where($where)->order('id desc')->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['category_name'] = M('category')->where('id',$value['category'])->getField('name');
                $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
                $list[$key]['picture'] = explode(',', $value['picture']);
                $list[$key]['content'] = $this->stringModify($value['content'],30,1);
                $category_name_first = $this->getcFirstCategory('category',3,$value['category']);
                if (!$category_name_first['code']) {
                    $list[$key]['category_name_first'] = $category_name_first['info']['name'];
                }else{
                    $list[$key]['category_name_first'] = '';
                }
            }
        }
        // dump($list);
        // dump(123);
        // dump(M('goods')->getLastsql());
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function u_xuanhui(){
        // $position = Session::get('position');
        $category = input('post.category');
        if ($category) {
            $where['category'] = $category;
        }
        // $where['area'] = array('like',$position.'%');
        $where['is_delete'] = 0;
        $list = M('tao_goods')->where($where)->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['category_name'] = M('category')->where('id',$value['id'])->getField('name');
                $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
                $list[$key]['content'] = $this->stringModify($value['content'],30,1);
            }
        }
        
        // dump($list);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function u_activity_list(){
        $p = I('p',1);
        $activity_where['status'] = array('in','1,2,3');
        $activity_where['is_delete'] = 0;
        $activity_list = M('activity')->where($where)->order('id desc')->select();
        if ($activity_list) {
            foreach ($activity_list as $key => $value) {
                $activity_list[$key]['start_time'] = date('m-d H:i',$value['start_time']);
                $activity_list[$key]['end_time'] = date('m-d H:i',$value['end_time']);
                // $activity_list[$key]['picture'] = $this->imageChange($value['picture']);
                
                $activity_list[$key]['title'] = $this->stringModify($value['title'],70,1);
                $activity_list[$key]['content'] = $this->stringModify($value['content'],20,1);
            }
        }
        // $count = M('activity')->where($where)->count();
        // $page = new Page($count,10);
        // $show = $page->show();
        // dump($activity_list);
        $this->assign('list',$activity_list);
        // $this->assign('page',$page);
        // $this->assign('show',$show);
        return $this->fetch();
    }

    public function u_paiyang_list(){
        $position = Session::get('position');
        $category = input('post.category');
        if ($category) {
            $where['category'] = $category;
        }
        $where['area'] = array('like',$position.'%');
        $where['is_delete'] = 0;
        $list = M('goods')->where($where)->order('id desc')->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['category_name'] = M('category')->where('id',$value['category'])->getField('name');
                $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
                $list[$key]['picture'] = explode(',', $value['picture']);
                $list[$key]['content'] = $this->stringModify($value['content'],30,1);
                $category_name_first = $this->getcFirstCategory('category',3,$value['category']);
                if (!$category_name_first['code']) {
                    $list[$key]['category_name_first'] = $category_name_first['info']['name'];
                }else{
                    $list[$key]['category_name_first'] = '';
                }
            }
        }
        // dump($list);
        // dump(123);
        // dump(M('goods')->getLastsql());
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function u_xuanhui_list(){
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
                $list[$key]['content'] = $this->stringModify($value['content'],30,1);
                $list[$key]['picture'] = explode(',', $value['picture']);
                $category_name_first = $this->getcFirstCategory('category',3,$value['category']);
                if (!$category_name_first['code']) {
                    $list[$key]['category_name_first'] = $category_name_first['info']['name'];
                }else{
                    $list[$key]['category_name_first'] = '';
                }
            }
        }
        // dump($list);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function u_info(){
        $id = I('id');
        $info = M('goods')->where('id',$id)->find();
        if ($info) {
            $info['category_name'] = M('category')->where('id',$info['category'])->getField('name');
            $info['add_time'] = date('Y-m-d H:i',$info['add_time']);
            $info['picture'] = explode(',', $info['picture']);
            $category_name_first = $this->getcFirstCategory('category',3,$info['category']);
            if (!$category_name_first['code']) {
                $info['category_name_first'] = $category_name_first['info']['name'];
            }else{
                $info['category_name_first'] = '';
            }
            M('goods')->where('id',$id)->setInc('read_num');    //阅读+1
        }
        // dump($info);
        $this->assign('info',$info);
        return $this->fetch();
    }
    public function u_tao_info(){
        $id = I('id');
        $info = M('tao_goods')->where('id',$id)->find();
        if ($info) {
            $info['category_name'] = M('category')->where('id',$info['category'])->getField('name');
            $info['add_time'] = date('Y-m-d H:i',$info['add_time']);
            $info['picture'] = explode(',', $info['picture']);
            $category_name_first = $this->getcFirstCategory('category',3,$info['category']);
            if (!$category_name_first['code']) {
                $info['category_name_first'] = $category_name_first['info']['name'];
            }else{
                $info['category_name_first'] = '';
            }
            M('tao_goods')->where('id',$id)->setInc('read_num');    //阅读+1
        }
        // dump($info);
        $this->assign('info',$info);
        return $this->fetch();
    }
    /**
     * 地区切换Session
     *
     * @author 蓝勇强 2019-01-03
     * @return [type] [description]
     */
    public function savePositionSession(){
        //地区切换：1南京、2苏州、3合肥
        $position = I('post.position',1);
        Session::set('position',$position);
        $res = Session::get('position');
        if ($res) {
            $return = array('code'=>0,'msg'=>'操作成功','info'=>$res);
        }else{
            $return = array('code'=>1,'msg'=>'操作失败','info'=>$res);
        }
        $this->ajaxReturn($return);
    }
    public function carouselMapList(){
        $where['is_delete'] = 0;
        $where['status'] = 1;
        $where['area'] = array('like',$this->position.'%');
        $list = M('carousel_map')->where($where)->limit(5)->select();
        foreach ($list as $key => $value) {
            if ($value['table'] && $value['table_id']) {
                $list[$key]['info'] = M($value['table'])->where('id',$value['table_id'])->find();
            }
        }
        // dump($where);
        // dump($list);
        // dump(M('carousel_map')->getLastsql());
        if ($list) {
            return $list;
        }else{
            return false;
        }
    }
    public function point_list(){
        $area = I('area');
        $area = explode(',',$area);
        $list = M('point')->where('id',array('in',$area))->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['area_name'] = M('area')->where('id',$value['area'])->getField('name');
            }
        }
        // dump($list);
        $this->assign('list',$list);
        return $this->fetch();
    }
    public function point_info(){
        $id = I('id');
        $info = M('point')->where('id',$id)->find();
        if ($info) {
            $info['area_name'] = M('area')->where('id',$info['area'])->getField('name');
        }
        // dump($info);
        $this->assign('info',$info);
        return $this->fetch();
    }
    public function stringModify($str='',$str_len=10,$type=1){
        if ($type == 1) {   //字符串截取
            $res = mb_substr($str,0,$str_len,'utf-8');
            if (strlen($str) > $str_len) {
                $res = $res.'...';
            }
        }
        return $res;
    }


}
