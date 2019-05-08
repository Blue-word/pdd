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

}