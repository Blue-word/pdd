<?php
namespace app\index\controller;
use think\Controller;
use think\Model;

class Table extends Common{

    // public $withdraw_status = array(
    //     '1' => '申请中',
    //     '2' => '提现成功',
    //     '3' => '提现失败',
    //     '4' => '驳回提现',
    // );

    public function _initialize(){
        $this->withdraw_status = C('withdraw_status');
        // $this->publish_cate = C('PUBLIC_CATE');
    }

    public function table_data_tables(){
        return $this->fetch();
    }

    public function table_jqgrid(){
        return $this->fetch();
    }

    public function table_foo_table(){
        return $this->fetch();
    }

    public function table_bootstrap(){
        // $openid = input('post.openid','aoe7aJ5dDm4Pg95jtyltz9vu0eTBU');  //用户openID
        $list = M('wx_withdraw')->select();
        foreach ($list as $k => $v) {
            $list[$k]['status_name'] = $this->withdraw_status[$v['status']];
            $list[$k]['nickname'] = M('wx_user')->where('openid',$v['openid'])->getField('nickName');
            // $amount = sprintf("%.2f",$v['apply_amount']/100);
            $list[$k]['time'] = date('m-d H:i',$v['time']);
            $list[$k]['payment_time'] = date('m-d H:i',$v['payment_time']);
            // $list[$k]['apply_amount'] = '猜我所画提现'.$amount.'元';
        }
        $this->assign('list',$list);
        // dump($list);
        return $this->fetch();
    }

    public function withdraw_audit(){
        $id = input('post.id');
        $act = input('post.act');
        if ($act == 'success') {  //通过
            $info = M('wx_withdraw')->where('id',$id)->find();
            $input = new Wxpayapi();
            $appid = C('WX.APPID');  //小程序appid
            $mch_id = C('WX.MCHID');
            $KEY = C('WX.KEY');
            $nonce_str = $input->getNonceStr();//随机字符串
            $notify_url = 'https://www.netqlv.com/sxllb/api/index/pay';//回调地址
            $spbill_create_ip = $_SERVER['REMOTE_ADDR'];
            //签名是按照(字典序)顺序
            $post['amount'] = $info['amount'];  //金额
            $post['check_name'] = 'NO_CHECK';   //校验用户姓名选项
            $post['desc'] = '猜我所画-余额提现';    //企业付款描述信息
            $post['mch_appid'] = $appid;  //小程序ID
            $post['mch_id'] = $mch_id;  //商户号
            $post['nonce_str'] = $nonce_str;//随机字符串
            $post['openid'] = $info['openid'];    //用户标识
            $post['partner_trade_no'] = $info['partner_trade_no'];  //商户订单号
            $post['spbill_create_ip'] = $spbill_create_ip;//调用接口的机器Ip地址
            $sign = $this->MakeSign($post,$KEY);    //签名
            $post['sign'] = $sign;
            ksort($post);
            //拼接XML格式
            $post_xml = $this->arrayToxml($post);
            //请求统一下单接口
            $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
            $xml = $input->postXmlCurl($post_xml,$url,true);
            // $xml = $this->http_request($url,$post_xml);     //POST方式请求http
            $array = $this->xml2array($xml);  //将api返回xml数据转换成数组，全要大写
            // dump($array);
            
            if($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS'){
                Db::startTrans();
                $payment_time = Db::table('wx_withdraw')->where('id',$id)->save(['payment_time'=>$array['PAYMENT_TIME']]);
                $status = Db::table('wx_withdraw')->where('id',$id)->save(['status'=>2]);
                //日志信息存入数据库
                $info_money = M('wx_withdraw')->where('id',$id)->getField('amount');
                if ($payment_time && $status) {
                    $log_data = array(
                        'info' => '微信提现成功'.$info_money,
                        'out_trade_no' => $array['PARTNER_TRADE_NO'],
                        'time' => time(),
                    );
                    Db::table('wx_log')->add($log_data);
                    Db::commit();
                    exit(json_encode('success'));
                }else{
                    Db::rollback();
                    $log_data = array(
                        'info' => '提现数据写入失败',
                        'out_trade_no' => '请注意！有数据写入错误',
                        'time' => time(),
                    );
                    M('wx_log')->add($log_data);
                    exit(json_encode('error'));
                } 
            }elseif ($array['ERR_CODE'] == 'SYSTEMERROR') {
                $log_data = array(
                    'info' => '系统繁忙，请稍后再试',
                    'out_trade_no' => '请使用原单号以及原请求参数重试，否则可能造成重复支付等资金风险',
                    'time' => time(),
                );
                M('wx_log')->add($log_data);
                exit(json_encode('error'));
            }else{
                $log_data = array(
                    'info' => '微信提现失败',
                    'out_trade_no' => '微信返回RESULT_CODE为FAIL',
                    'time' => time(),
                );
                M('wx_log')->add($log_data);
                exit(json_encode('error'));
            }
        }else {   //不通过
            $res = M('wx_withdraw')->where('id',$id)->save(['status'=>3]);
            if ($res) {
                $log_data = array(
                    'info' => '驳回提现成功',
                    'out_trade_no' => '注意！已驳回用户提现，请审核',
                    'time' => time(),
                );
                M('wx_log')->add($log_data);
                exit(json_encode('success'));
            }else{
                $log_data = array(
                    'info' => '驳回提现失败',
                    'out_trade_no' => '注意！错误驳回提现，请审核',
                    'time' => time(),
                );
                M('wx_log')->add($log_data);
                exit(json_encode('error'));
            }
        }
    }

    public function table_basic(){
        return $this->fetch();
    }

    public function form_validate(){
        return $this->fetch();
    }

    public function form_builder(){
        return $this->fetch();
    }

    public function chat_view(){
        return $this->fetch();
    }

    public function webim(){
        return $this->fetch();
    }

}
