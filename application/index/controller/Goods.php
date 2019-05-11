<?php
namespace app\index\controller;
use think\Controller;
use think\Model;
use think\Session;
use think\db\Query;
use think\Paginator;

class Goods extends Common{

    private $status_where = ['status'=>1];

    public function _initialize(){
        parent::_initialize();  //关闭时不调用父类检验token
        $this->sex = C('SEX');
        $this->status_where = $status_where;
    }

    public function position_set(){
        return $this->fetch();
    }
    /**
    * 商品列表 
    **/
    public function goods_list(){
        if (I('post.shop_id')) {
            $where['shop_id'] = I('post.shop_id');
        }
        $where['status'] = 1;
        $list = M('goods')->where($where)->paginate(25);
        // 获取分页显示
        $page = $list->render();
        $total = $list->total();
        // 获取分页列表数据
        $list = $list->items();
        if ($list) {
            foreach ($list as $key => $value) {
                $goods_ids[] = $value['goods_id'];
            }
            $sku_where['goods_id'] = ['in', array_unique($goods_ids)];
            $sku_where['status'] = 1;
            $goods_sku = M('goods_sku')->where($sku_where)->select();
            foreach ($goods_sku as $key => $value) {
                $goods_sku_list[$value['goods_id']][$key]['sku_id'] = $value['sku_id'];
                $goods_sku_list[$value['goods_id']][$key]['amount'] = $value['amount'];
            }
            $shop = M('shop')->where(['status'=>1])->getField('id,name');   //店铺
            foreach ($list as $key => &$value) {
                $sku_str = '';
                if ($goods_sku_list[$value['goods_id']]) {
                    foreach ($goods_sku_list[$value['goods_id']] as $k => $v) {
                        $sku_str .= $v['sku_id'].' / ￥'.$v['amount'].'<br>';
                    }
                }
                $value['sku'] = $sku_str;
                $value['shop'] = $shop[$value['shop_id']];
                $value['time'] = date('Y-m-d H:i', $value['time']);
            }
        }
        $shop_list = M('shop')->where(['status'=>1])->select();
        $this->assign('page', $page);
        $this->assign('shop_list',$shop_list);
        $this->assign('list',$list);
        $this->assign('total',$total);
        return $this->fetch();
    }

    public function goods_info(){
        $id = I('get.id');
        if ($id) {
            $goods = M('goods')->where(['id'=>$id])->find();
            $this->assign('info',$goods);
        }
        $shop_list = M('shop')->where(['status'=>1])->select();
        $act = empty($id) ? 'add' : 'edit';
        $this->assign('act',$act);
        $this->assign('id',$id);
        $this->assign('shop_list',$shop_list);
        return $this->fetch();
    }

    public function goods_handle(){
        $data = I('post.');
        $amount = $data['amount'];
        if ($data['link']) {
            $goods_url = 'https://mobile.yangkeduo.com/goods.html?goods_id=';   //拼多多商品查看url
            $url_query = parse_url($data['link']);  //解析链接的参数
            parse_str($url_query['query'], $url_param);
        }
        // var_dump($data);die;
        $where['id'] = $data['id'];
        if($data['act'] == 'add'){
            $data['time'] = time();
            $data['goods_id'] = $url_param['goods_id'];
            $data['link'] = $goods_url.$url_param['goods_id'];  //拼接商品信息URL,该链接可查看商品信息
            unset($data['amount'], $data['act']);
            $res = M('Goods')->data($data)->add();
        }
        // die;
        if($data['act'] == 'edit'){
            $goods_info = M('goods')->where($where)->find();
            if ($url_param['goods_id'] != $goods_info['goods_id']) {
                $this->error("链接中的goods_id不能修改",U('index/goods/goods_info',array('id'=>$data['id'])));
            }
            $data['link'] = $goods_url.$url_param['goods_id'];  //拼接商品信息URL,该链接可查看商品信息
            unset($data['amount'], $data['act']);
            $res = M('goods')->where($where)->save($data);
        }

        if($data['act'] == 'ajax'){
            $goods_info = M('goods')->where($where)->find();
            $res = M('goods')->where($where)->limit(1)->delete();
            if ($res) {
                M('goods_sku')->where(['goods_id'=>$goods_info['goods_id']])->limit(1)->delete();   // 删除sku
            }
            exit(json_encode($res));
        }
        
        if($res){
            // 写入商品sku表
            $sku = [
                'sku_id'   => $url_param['sku_id'],
                'goods_id' => $url_param['goods_id'],
                'group_id' => $url_param['group_id'],
                'amount'   => substr(sprintf("%.3f", $amount), 0, -1)
            ];
            M('goods_sku')->data($sku)->add();
            $this->success("操作成功",U('index/goods/goods_list'));
        }else{
            $this->error("操作失败",U('index/goods/goods_info',array('id'=>$data['id'])));
        }
    }

    public function goods_sku_info(){
        $goods = M('goods')->where(['id'=>I('post.id')])->find();
        $where['goods_id'] = $goods['goods_id'];
        $where['status'] = 1;
        $goods_sku = M('goods_sku')->where($where)->select();
        $this->assign('goods',$goods);
        $this->assign('goods_sku',$goods_sku);
        return $this->fetch();
    }

    public function goods_sku_handle()
    {
        $data = I('post.');
        if($data['act'] == 'ajax'){
            $res = M('goods_sku')->where(['id'=>$data['id']])->save(['status'=>$data['status']]);
            exit(json_encode($res));
        }
        if ($data['act'] == 'edit') {
            foreach ($data['sku_id'] as $key => $value) {
                $amount = substr(sprintf("%.3f", $data['amount'][$key]), 0, -1);
                $res = M('goods_sku')->where(['id'=>$value])->save(['amount'=>$amount]);
            }
        }
        if($res){
            $this->success("操作成功",U('index/goods/goods_sku_info',array('id'=>$data['id'])));
        }else{
            $this->error("操作失败",U('index/goods/goods_sku_info',array('id'=>$data['id'])));
        }
    }

    public function test01($value='')
    {
        $url = 'http://mobile.yangkeduo.com/proxy/api/api/galen/v2/regions/1';
        $res = httpPost($url,[]);
    }

    



}
