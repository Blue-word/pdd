<?php
namespace app\api\controller;
use think\Session;
use think\Wxpayapi;
use think\Db;

class Payment extends Paycommon{       
  
    public function generate_order(){  //统一下单
        $input = new Wxpayapi();
        $appid = C('WX.APPID');  //公众号appid
        $KEY = C('WX.KEY');   //key为商户平台设置的密钥key，api密钥
        $body = '太平人寿-入职缴费';
        $mch_id = C('WX.PAYMENT_MERCHANT_NUMBER');
        $openid = input('post.openid','oe7aJ5dDm4Pg95jtyltz9vu0eTBU'); 
        $total_fee = input('post.total_fee/f',1);//标价金额(传输过来的金额单位为元)
        $total_fee = $total_fee*100;//标价金额(单位为分)
        $nonce_str = $input->getNonceStr();//随机字符串
        $notify_url =   'https://www.netqlv.com/blue/api/payment/pay';//回调地址
        $out_trade_no = randomFromDev(16);//商户订单号
        $spbill_create_ip = $_SERVER['REMOTE_ADDR'];
        //$spbill_create_ip = '123.12.12.123';
    
        //这里是按照顺序的 因为下面的签名是按照(字典序)顺序 排序错误 肯定出错
        $post['appid'] = $appid;  //小程序ID
        $post['body'] = $body;       //商品描述
        $post['limit_pay'] = 'no_credit';    //指定支付方式
        $post['mch_id'] = $mch_id;  //商户号
        $post['nonce_str'] = $nonce_str;//随机字符串
        $post['notify_url'] = $notify_url;   //通知地址
        $post['openid'] = $openid;    //用户标识
        $post['out_trade_no'] = $out_trade_no;  //商户订单号
        $post['spbill_create_ip'] = $spbill_create_ip;//服务器终端的ip
        $post['total_fee'] = $total_fee;  //标价金额
        $post['trade_type'] = 'JSAPI';   //交易类型
        $sign = $this->MakeSign($post,$KEY);    //签名
        $post['sign'] = $sign;
        ksort($post);
        //拼接XML格式
        $post_xml = $this->arrayToxml($post);
        //dump($post_xml);
        //请求统一下单接口
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url,$post_xml);     //POST方式请求http
        $array = $this->xml2array($xml);  //将api返回xml数据转换成数组，全要大写
        // dump($array);
        if($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS'){
            $time = time();
            $tmp='';                            //临时数组用于签名
            $tmp['appId'] = $appid;
            $tmp['nonceStr'] = $nonce_str;
            $tmp['package'] = 'prepay_id='.$array['PREPAY_ID'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";
    
            $data['state'] = 1;
            $data['timestamp'] = "$time";           //时间戳
            $data['nonceStr'] = $nonce_str;         //随机字符串
            $data['signType'] = 'MD5';              //签名算法，暂支持 MD5
            $data['package'] = 'prepay_id='.$array['PREPAY_ID'];   //统一下单接口返回的 prepay_id 参数值，提交格式如：prepay_id=*
            $data['paySign'] = $this->MakeSign($tmp,$KEY);       //签名,具体签名方案参见微信公众号支付帮助文档;
            $data['out_trade_no'] = $out_trade_no;
            //订单信息存入数据库
            // $uid = M('wx_user')->where('openid',$openid)->getField('id');
            $order_data = array(
                'out_trade_no' => $out_trade_no,
                'openid' => $openid,
                'money' => $total_fee,
                'order_time' => $time,
            );
            $pay_status = M('pay_order')->save($order_data);
            if ($pay_status) {
                $this->apiReturn('下单成功','200',$data);
            }else{
                $error_data['state'] = 0;
                $error_data['text'] = "订单数据写入错误";
                $this->apiReturn('下单失败','401',$error_data);
            }
        }else{
            $error_data['state'] = 0;
            $error_data['text'] = "错误";
            $error_data['RETURN_CODE'] = $array['RETURN_CODE'];
            $error_data['RETURN_MSG'] = $array['RETURN_MSG'];
            $this->apiReturn('下单失败','400',$error_data);
        }
    }

    public function del_order(){  //取消支付删除订单
        $out_trade_no = input('post.out_trade_no');
        $order = M('wx_album')->where('order_id',$out_trade_no)->count();
        if ($order) {  //存在订单
            $res = M('wx_album')->where('order_id',$out_trade_no)->delete();
        }
        if ($res) {
            $this->apiReturn('订单删除成功','200','订单删除成功');
        }else{
            $this->apiReturn('订单删除失败','401','订单删除失败');
        }
    }
  
    public function pay(){    //微信支付回调地址
        $rwa_xml = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $post_data = json_decode(json_encode(simplexml_load_string($rwa_xml,'SimpleXMLElement',LIBXML_NOCDATA)),true);
        $postSign = $post_data['sign'];
        unset($post_data['sign']);
        $KEY = C('WX.KEY');
        $sign = $this->MakeSign($post_data,$KEY);
        $where['out_trade_no'] = $post_data['out_trade_no'];
        $order = M('pay_order')->where($where)->find();
        if($post_data['return_code'] == 'SUCCESS' && $postSign == $sign){
            if($order['order_status'] == 'ok'){
                $this->return_success();
            }else{
                //order表更新
                $order_status = M('pay_order')->where($where)->save(['order_status'=>'ok']);
                if($order_status){
                    $log_data['info'] = 'order_status为ok';
                    $log_data['out_trade_no'] = $post_data['out_trade_no'];
                    $log_data['time'] = time();
                    $log_data['openid'] = $order['openid'];
                    M('pay_log')->add($log_data);
                    $this->return_success();
                }else{
                    M('pay_order')->where($where)->save(['order_status'=>'er']);
                    $log_data['info'] = 'order_status为er';
                    $log_data['out_trade_no'] = $post_data['out_trade_no'];
                    $log_data['time'] = time();
                    $log_data['openid'] = $order['openid'];
                    M('pay_log')->add($log_data);
                    echo '微信支付失败';
                }
            }
        }else{
            $log_data['info'] = 'order_status为er';
            $log_data['out_trade_no'] = $post_data['out_trade_no'];
            $log_data['time'] = time();
            $log_data['openid'] = $order['openid'];
            M('pay_log')->add($log_data);
            echo '微信支付失败';
        }
    }
  
    
  
    public function wxrefund($openid,$id){  //退款
        $input = new Wxpayapi();
        $appid = C('WX.APPID');  //小程序appid
        $mch_id = C('WX.MCHID');
        // $openid = input('post.openid'); 
        $id = (int)$id;
        dump($id);
        $KEY = C('WX.KEY');
        // $id = input('post.album_id',74);  //画册id
        $uid = M('wx_user')->where('openid',$openid)->getField('id');
        $where['openid'] = $uid;
        $where['id'] = $id;
        $album = M('wx_album')->where($where)->field('order_id,money,last_money')->find();
        //$this->apiReturn('查询失败','401',$id);
        if (!$album) {
            $this->apiReturn('查询失败','401','查询失败');
        }
        $total_fee = $album['money'];
        $refund_fee = $album['last_money'];
        $parma = array(
            'appid'=> $appid,
            'mch_id'=> $mch_id,
            'nonce_str'=> $input->getNonceStr(),//随机字符串
            'out_refund_no'=> $input->getNonceStr(),//随机字符串
            'out_trade_no'=> $album['order_id'],
            'total_fee'=> $total_fee,
            'refund_desc' => '猜我所画-红包退款',
            'refund_fee'=> $refund_fee,
        );
        $sign = $this->MakeSign($parma,$KEY);          //签名
        $parma['sign'] = $sign;
        ksort($parma);
        $xmldata = $this->arrayToXml($parma);
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $xmlresult = $input->postXmlCurl($xmldata,$url,true);
        $array = $this->xml2array($xmlresult);  //将api返回xml数据转换成数组
        /*$postSign = $array['SIGN'];
        unset($array['SIGN']);
        $KEY = C('WX.KEY');
        $sign = $this->MakeSign($array,$KEY);*/
        
        if($array['RESULT_CODE'] == 'SUCCESS'){  //验签成功
            $result = M('wx_album')->where('id',$id)->save(['refund_status'=>1,'last_money_num'=>0,'last_money'=>0]);  //已申请退款
            $order_data = array(
                'order_id' => $array['OUT_TRADE_NO'],  //商户订单号
                'out_refund_no' => $array['OUT_REFUND_NO'],    //商户退款单号
                'transaction_id' => $array['TRANSACTION_ID'],  //微信订单号
                'refund_id' => $array['REFUND_ID'],    //微信退款单号
                'refund_fee' => $array['REFUND_FEE'],  //退款金额
                'total_fee' => $array['TOTAL_FEE'],    //标价金额
                'time' => time(),                      //申请时间
                'type' => 2,                           //退款
            );
            $order_res = M('wx_order')->add($order_data);
            if ($result && $order_res) {
                $log_data['info'] = '退款成功';
                $log_data['out_trade_no'] = $array['OUT_TRADE_NO'];
                $log_data['time'] = date('Y-m-d H:i:s',time());
                M('wx_log')->add($log_data);
                // $return['code'] = 200;
                // $return['info'] = '退款成功';
                // return $return;
                $this->apiReturn('申请退款成功','200','申请退款成功');
            }else{
                $log_data['info'] = '退款-数据写入失败';
                $log_data['out_trade_no'] = $array['OUT_TRADE_NO'];
                $log_data['time'] = date('Y-m-d H:i:s',time());
                M('wx_log')->add($log_data);
                $this->apiReturn('申请退款失败','401','申请退款失败');
                // $return['code'] = 401;
                // $return['info'] = '退款-数据写入失败';
                // return $return;
            }
        }else{
            $log_data['info'] = '微信返回退款为ER';
            $log_data['out_trade_no'] = $array['OUT_TRADE_NO'];
            $log_data['time'] = date('Y-m-d H:i:s',time());
            M('wx_log')->add($log_data);
            // $return['code'] = 401;
            // $return['info'] = $array['ERR_CODE_DES'];
            // return $return;
            $this->apiReturn('申请退款失败1','401','申请退款失败');
        }
    }

    

    public function apply_withdraw(){  //申请提现
        $openid = input('post.openid','oe7aJ5dDm4Pg95jtyltz9vu0eTBU');  //用户openID
        $money = input('post.money/d',1);
        $apply_amount = $money*100;  //申请金额元转分
        if ($amount < 200) {
            // $this->apiReturn('提现金额不得低于两元(两百分)','402','提现金额不得低于两元');
        }
        $wallet = M('wx_user')->where('openid',$openid)->getField('wallet');
        if ($wallet < $apply_amount) {  //账户余额不足
            $this->apiReturn('账户余额不足','401','账户余额不足');
        }
        $id = randomFromDev(10);  //订单id
        $realy_amount = $apply_amount*97/100;  //实际到账金额
        $partner_trade_no = randomFromDev(16);//商户订单号
        $data = array(
            'id' => $id,
            'openid' => $openid,
            'apply_amount' => $apply_amount,
            'real_amount' => $realy_amount,
            'partner_trade_no' => $partner_trade_no,
            'time' => time(),
            'payment_time' => 0,
            'status' => 1,
        );
        Db::startTrans();
        $res = Db::table('wx_withdraw')->add($data);
        $res_1 = Db::table('wx_user')->where('openid',$openid)->setDec('wallet',$apply_amount);
        if ($res && $res_1) {
            Db::commit();
            $this->apiReturn('申请提现成功','200','申请提现成功');
        }else{
            Db::rollback();
            $this->apiReturn('申请提现失败','400','申请提现失败');
        }
    }

    public function apply_withdraw_list(){  //提现记录
        $p = input('post.p/d',1);
        $openid = input('post.openid','oe7aJ5dDm4Pg95jtyltz9vu0eTBU');  //用户openID
        $list = M('wx_withdraw')->where('openid',$openid)->field('id,apply_amount,time,status')->page($p,10)->select();
        foreach ($list as $k => $v) {
            if ($v['status'] == 1) {
                $list[$k]['status'] = '申请中';
            }elseif ($v['status'] == 2) {
                $list[$k]['status'] = '提现成功';
            }elseif ($v['status'] == 3) {
                $list[$k]['status'] = '提现失败';
            }else{
                $list[$k]['status'] = '被驳回';
            }
            $amount = sprintf("%.2f",$v['apply_amount']/100);
            $list[$k]['time'] = date('m-d H:i',$v['time']);
            $list[$k]['apply_amount'] = '猜我所画提现'.$amount.'元';
        }
        $count = M('wx_withdraw')->where('openid',$openid)->count();
        $pager = new Page($count,10);
        $page =  $pager->totalPages;
        $res['list'] = $list;
        $res['pages'] = $page;
        $res['page'] =  $p;
        $this->apiReturn('查询成功','200',$res);
    }



    public function withdraw(){  //提现
        $id = input('post.id');  //申请提现id
        $info = M('wx_withdraw')->where('id',$id)->find();
        $input = new Wxpayapi();
        $appid = C('WX.APPID');  //小程序appid
        $mch_id = C('WX.MCHID');
        $KEY = C('WX.KEY');
        $nonce_str = $input->getNonceStr();//随机字符串
        $notify_url =   'https://www.netqlv.com/sxllb/api/index/pay';//回调地址
        $spbill_create_ip = $_SERVER['REMOTE_ADDR'];
        //这里是按照顺序的 因为下面的签名是按照(字典序)顺序 排序错误 肯定出错
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
                $this->apiReturn('下单成功','200',$data);
            }else{
                Db::rollback();
                $this->apiReturn('数据写入失败','400','数据写入失败');
            } 
        }elseif ($array['ERR_CODE'] == 'SYSTEMERROR') {
            $log_data = array(
                'info' => '系统繁忙，请稍后再试',
                'out_trade_no' => '请使用原单号以及原请求参数重试，否则可能造成重复支付等资金风险',
                'time' => time(),
            );
            M('wx_log')->add($log_data);
            $this->apiReturn('微信请求失败','401','微信请求失败');
        }else{
            $log_data = array(
                'info' => '微信提现失败',
                'out_trade_no' => '微信返回RESULT_CODE为FAIL',
                'time' => time(),
            );
            M('wx_log')->add($log_data);
            $this->apiReturn('微信请求失败','401','微信请求失败');
        }
    }

    public function wxrefund_1($openid,$id){  //退款
        $input = new Wxpayapi();
        $appid = C('WX.APPID');  //小程序appid
        $mch_id = C('WX.MCHID');
        // $openid = input('post.openid'); 
        $KEY = C('WX.KEY');
        // $id = input('post.album_id',74);  //画册id
        $uid = M('wx_user')->where('openid',$openid)->getField('id');
        $where['openid'] = $uid;
        $where['id'] = $id;
        $album = M('wx_album')->where($where)->field('order_id,money,last_money')->find();
        //$this->apiReturn('查询失败','401',$id);
        if (!$album) {
            $this->apiReturn('查询失败','401','查询失败');
        }
        $total_fee = $album['money'];
        $refund_fee = $album['last_money'];
        $parma = array(
            'appid'=> $appid,
            'mch_id'=> $mch_id,
            'nonce_str'=> $input->getNonceStr(),//随机字符串
            'out_refund_no'=> $input->getNonceStr(),//随机字符串
            'out_trade_no'=> $album['order_id'],
            'total_fee'=> $total_fee,
            'refund_desc' => '猜我所画-红包退款',
            'refund_fee'=> $refund_fee,
        );
        $sign = $this->MakeSign($parma,$KEY);          //签名
        $parma['sign'] = $sign;
        ksort($parma);
        $xmldata = $this->arrayToXml($parma);
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $xmlresult = $input->postXmlCurl($xmldata,$url,true);
        $array = $this->xml2array($xmlresult);  //将api返回xml数据转换成数组
        /*$postSign = $array['SIGN'];
        unset($array['SIGN']);
        $KEY = C('WX.KEY');
        $sign = $this->MakeSign($array,$KEY);*/
        
        if($array['RESULT_CODE'] == 'SUCCESS'){  //验签成功
            $result = M('wx_album')->where('id',$id)->save(['refund_status'=>1,'last_money_num'=>0,'last_money'=>0]);  //已申请退款
            $order_data = array(
                'order_id' => $array['OUT_TRADE_NO'],  //商户订单号
                'out_refund_no' => $array['OUT_REFUND_NO'],    //商户退款单号
                'transaction_id' => $array['TRANSACTION_ID'],  //微信订单号
                'refund_id' => $array['REFUND_ID'],    //微信退款单号
                'refund_fee' => $array['REFUND_FEE'],  //退款金额
                'total_fee' => $array['TOTAL_FEE'],    //标价金额
                'time' => time(),                      //申请时间
                'type' => 2,                           //退款
            );
            $order_res = M('wx_order')->add($order_data);
            if ($result && $order_res) {
                $log_data['info'] = '退款成功';
                $log_data['out_trade_no'] = $array['OUT_TRADE_NO'];
                $log_data['time'] = date('Y-m-d H:i:s',time());
                M('wx_log')->add($log_data);
                $return['code'] = 200;
                $return['info'] = '退款成功';
                return $return;
                // $this->apiReturn('申请退款成功','200','申请退款成功');
            }else{
                $log_data['info'] = '退款-数据写入失败';
                $log_data['out_trade_no'] = $array['OUT_TRADE_NO'];
                $log_data['time'] = date('Y-m-d H:i:s',time());
                M('wx_log')->add($log_data);
                // $this->apiReturn('申请退款失败','401','申请退款失败');
                $return['code'] = 401;
                $return['info'] = '退款-数据写入失败';
                return $return;
            }
        }else{
            $log_data['info'] = '微信返回退款为ER';
            $log_data['out_trade_no'] = $array['OUT_TRADE_NO'];
            $log_data['time'] = date('Y-m-d H:i:s',time());
            M('wx_log')->add($log_data);
            $return['code'] = 401;
            $return['info'] = $array['ERR_CODE_DES'];
            return $return;
            // $this->apiReturn('申请退款失败1','401','申请退款失败');
        }
    }
  
  
  
}
