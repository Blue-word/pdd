<?php
namespace app\api\controller;
use \think\Controller;
use fast\Http;
use app\api\controller\Send;

class PddLogistics extends Controller
{
    // 拼多多在线下单订单下发接口URL
    protected $logistics_url = 'http://gw-api.pinduoduo.com/api/router';

    public function _initialize($data=[]){
        $data['Trackingno'] = '8238730201';
        $data['shop_id'] = '2';
        $data['order_sn'] = '190510-633122653153278';
        $this->error = '';
        $this->getRes();    //请求acces_token
        $this->getPddPublicData($data);
        $this->getPddData($data);
    }

    /**
     * 在线下单订单下发接口
     * @return [type] [description]
     */
    public function pddLogisticsOnlineCreate()
    {
        if ($this->error) {
            return [1, $this->error, []];
        }
        $this->pdd_public_data['type'] = 'pdd.logistics.online.create';
        $pdd_data = array_merge($this->pdd_public_data, $this->pdd_data);
        $pdd_data = $this->MakeSign($pdd_data);
        $res = Http::post($this->logistics_url.'?'.http_build_query($pdd_data),$pdd_data);
        $res = json_decode($res, true);
        var_dump($res);
        $msg = '订单下发成功';
        if ($res['error_response'] && !$res['logistics_online_create_response']['is_success']) {
            $msg = '订单下发失败';
        }
        // return ['code'=>0, $msg, $this->res];
    }

    /**
     *  获取拼多多公共请求参数
     */
    public function getPddPublicData($data=[])
    {
        $this->pdd_public_data = [
            'type' => $data['type'],    //API接口名称
            'client_id' => $this->res['client_id'],     //应用的client_id
            'access_token' => $this->res['client_id'],  //access_token
            'timestamp' => time(),          //UNIX时间戳
        ];
    }

    /**
     *  获取拼多多公共请求参数-API
     */
    public function getPddPublicDataApi()
    {
        return $this->pdd_public_data;
    }

    /**
     * 拼接拼多多请求参数
     * @param  string $data 
     */
    public function getPddData($data=[])
    {
        if (!$data) $this->error = '参数为空';
        $send_class = new send();
        $send_address = $send_class->getShippingAddress();
        $this->pdd_data = [
            'tracking_number' => $data['Trackingno'],   //快递单号
            'shipping_id' => '124', //物流公司id(此处使用国通快递)
            'return_id' => $send_address['refund_address_id'],     //refund_address_id
            'delivery_phone' => $send_address['refund_phone'],       //发货人电话
            'delivery_name' => $send_address['refund_name'],         //发货人姓名
            'delivery_address' => $send_address['refund_address'],   //发货人地址
            'delivery_id' => $send_address['id'], //发货人地址id
            'order_sn' => $data['order_sn']             //订单编号
        ];
    }

    /**
     * 调接口获取拼多多请求参数（公共+请求）
     */
    public function getRes()
    {
        $res = [];
        $res = [
            'client_id' => 'a45c23e10b4246588130bae460d20b0b',
            'client_secret' => '7956b0c2c8ef9132d50382d0052f513e9e72d841',
            'access_token' => '4161dcaa131d40f598eb5dde72708ebf532479e8',
        ];
        if (!$res) $this->error = '获取拼多多请求参数失败';
        $this->res = $res;
    }
    
    /**
      * 生成签名
      * @return 签名
      */
    public function MakeSign($param){
        $client_secret = '7956b0c2c8ef9132d50382d0052f513e9e72d841';
        ksort($param);
        $str = $client_secret;
        foreach ($param as $key => $value) {
            $str .= $key.$value;
        }
        $sign = strtoupper(MD5($str.$client_secret));
        $param['sign'] = $sign;
        return $param;
    }
}