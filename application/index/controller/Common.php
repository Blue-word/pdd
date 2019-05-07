<?php
namespace app\index\controller;
use think\Controller;

class Common extends Controller{
	/**
     * API返回信息
     *
     * @author blue 2018-12-17
     * @param  string  $msg  返回状态信息
     * @param  integer $code 返回状态码
     * @param  string  $data 返回数据
     * @return [type]        [description]
     */
    public function apiReturn($msg='',$code=404,$data=''){
        $result=array(
            "code" => $code,
            "msg" => $msg,
            "result" => $data
            );
        $this->ajaxReturn($result,'json');
        exit;
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     * @return blue
     */
    public function ajaxReturn($data,$type='',$json_option=0) {
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data,$json_option));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data,$json_option).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);            
            default     :
                // 用于扩展其他返回格式数据
                Hook::listen('ajax_return',$data);
        }
    }
    /**
     * 图片地址转换
     *
     * @author 蓝勇强 2018-12-17
     * @param  string $picture 图片地址
     * @return [type]        [description]
     */
    public function imageChange($picture=''){
        $result = C('image').$picture;
        return $result;
    }
    /**
     * 获取分类信息
     *
     * @author blue 2018-12-17
     * @param  string  $model 模型
     * @param  integer $type  类型
     * @param  string  $id    分类id
     * @return [type]         [description]
     */
    public function getcFirstCategory($model='',$type=1,$id=''){
        if (!$model || !$id) {
            return array('code'=>1,'msg'=>'模型或分类id不能为空');
        }
        $return = array();
        if ($type == 1) {   //type=1获取分类信息
            $res = M($model)->where('id='.$id)->find();
            $return = array('code'=>0,'msg'=>'查询成功','info'=>$res);
        }elseif ($type == 2) {  //type=2获取上级分类信息
            $pid = M($model)->where('id='.$id)->getField('pid');
            if ($pid == 0) {
                $return = array('code'=>1,'msg'=>'已是顶级分类');
            }else{
                $res = M($model)->where('id='.$pid)->find();
                $return = array('code'=>0,'msg'=>'查询成功','info'=>$res);
            }
        }elseif ($type == 3) {  //type=3获取顶级分类信息
            $info = M($model)->where('id='.$id)->find();
            if ($info['level'] == 1) {
                $res = M($model)->where('id='.$info['id'])->find();
                $return = array('code'=>0,'msg'=>'查询成功','info'=>$res);
            }elseif ($info['level'] ==  2) {
                $res = M($model)->where('id='.$info['pid'])->find();
                $return = array('code'=>0,'msg'=>'查询成功','info'=>$res);
            }elseif ($info['level'] ==  3) {
                $pid = M($model)->where('id='.$info['pid'])->getField('pid');
                if ($pid == 0) {
                    $return = array('code'=>1,'msg'=>'已是顶级分类');
                }else{
                    $res = M($model)->where('id='.$pid)->find();
                    $return = array('code'=>0,'msg'=>'查询成功','info'=>$res);
                }
            }
        }
        return $return;
    }

}