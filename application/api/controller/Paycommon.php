<?php
namespace app\api\controller;
use think\Controller;

class Paycommon extends Controller{

  //空操作
  public function _empty(){
      $this->apiReturn("URL地址找不到",'404',"URL地址找不到");
  }

  /*API返回信息
  *JSON格式
  *@param $msg 返回状态信息
  *@param $code 返回状态码
  *@param $data 返回数据
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
   * @return void
   */
  protected function ajaxReturn($data,$type='',$json_option=0) {
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
   * 调用接口， $data是数组参数
   * @return 签名
   */
  public function http_request($url,$data = null,$headers=array())
  {
      $curl = curl_init();
      if( count($headers) >= 1 ){
          curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      }
      curl_setopt($curl, CURLOPT_URL, $url);
  
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
  
      if (!empty($data)){
          curl_setopt($curl, CURLOPT_POST, 1);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      }
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $output = curl_exec($curl);
      curl_close($curl);
      return $output;
  }
  /**
  * 生成签名, $KEY就是支付key
  * @return 签名
  */
  public function MakeSign( $params,$KEY){
      //签名步骤一：按字典序排序数组参数
      ksort($params);
      $string = $this->ToUrlParams($params);  //参数进行拼接key=value&k=v
      //签名步骤二：在string后加入KEY
      $string = $string . "&key=".$KEY;
      //签名步骤三：MD5加密
      $string = md5($string);
      //签名步骤四：所有字符转为大写
      $result = strtoupper($string);
      return $result;
  }
  /**
   * 将参数拼接为url: key=value&key=value
   * @param $params
   * @return string
   */
  public function ToUrlParams( $params ){
      $string = '';
      if( !empty($params) ){
          $array = array();
          foreach( $params as $key => $value ){
              $array[] = $key.'='.$value;
          }
          $string = implode("&",$array);
      }
      return $string;
  }
  /**
   * 数组转换成xml
   */
  public function arrayToXml($arr) { 
    $xml = "<xml>"; 
    foreach ($arr as $key => $val) { 
      if (is_array($val)) { 
        $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">"; 
      } else { 
        $xml .= "<" . $key . ">" . $val . "</" . $key . ">"; 
      } 
    } 
    $xml .= "</xml>"; 
    return $xml; 
  } 
  /**
   * 百度地图：源坐标转换为百度经纬度坐标
   * $longitude--经度；$latitude--纬度
   */
  public function coordinate_change($longitude,$latitude){
      $key = '4n5f9nGoP7R54lVrqEjAD8502ZvLRA78';  //百度密钥
      $url = "http://api.map.baidu.com/geoconv/v1/?coords=$longitude,$latitude&from=3&to=5&ak=$key"; //GET请求
      $content = $this->http_request($url);    //将源坐标转换为百度经纬度坐标
      $result = json_decode($content);
      $longitude_1 = $result->result[0]->x;     //经度
      $latitude_1  = $result->result[0]->y;   //纬度
      $res['longitude'] = $longitude_1;
      $res['latitude'] = $latitude_1;
      return $res;
  }
  /**
   * access_token
   * 获取access_token
   */
  private function getAccesstoken(){
      // access_token缓存5400秒，一个半小时
      $access_token = Cache::get('access_token');
      if ($access_token) {    //access_token缓存未失效
        return $access_token;
      }else{      //access_token缓存已失效
        $request_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.C('WEIXIN.APPID').'&secret='.C('WEIXIN.APPSECRET');
        $content = $this->http_request($request_url);  
        $result = json_decode($content,true);
         // $access_token = $result['access_token'];
        Cache::set('access_token',$result['access_token'],5400);  //重新缓存
        $access_token = Cache::get('access_token');
        return $access_token;
      }
  }
  /**
   * 客服
   * 客服消息发送
   */
  public function customer_service_send($msg_data){
      $access_token = $this->getAccesstoken();
      $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
      $post_data = json_encode($msg_data);
      $content = $this->http_request($url,$post_data);
      $res = json_decode($content,true);
      return $res;
  }
    



}