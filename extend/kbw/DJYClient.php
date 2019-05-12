<?php
namespace kbw;
use think\Exception;
class DJYClient
{
	public $appkey;
	public $appSecret;
	public $connectTimeout=180000;
	public $readTimeout=180000;
	public $gatewayUrl;
	public $isDebug=false;

	public function __construct($appkey = "",$appSecret = "",$gatewayUrl=""){
		$this->appkey = $appkey;
		$this->appSecret = $appSecret;
		$this->gatewayUrl=$gatewayUrl;
	}

	public function execute($request)
	{
		 
		//接口地址
	    $requestUrl= $this->gatewayUrl;

		//组装系统参数
		$sysParams["appKey"] = $this->appkey;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		
		$apiParams = array();

		//获取业务参数
		$apiParams = $request->getApiParas();

		//签名
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams));

  		//请求参数
	    $parms=array_merge($apiParams, $sysParams);
		
		
			// echo "<br/>";
			// echo "===============post的字符串==================";
			// echo "<br/>";
			// echo $requestUrl; 
		
  		//发起HTTP请求
		try
		{
			$resp = $this->curl($requestUrl, $parms);
		}
		catch (Exception $e)
		{
			 //todo:记录错误日志
 
			// echo $e->getMessage();
			return $e->getMessage();
		}

		//解析返回结果
		return $resp;
		//$respObject = json_decode($resp);
		/*if (null !== $respObject)
		{
  			foreach ($respObject as $propKey => $propValue)
			{
				$respObject = $propValue;
			}
		}*/

		//return $respObject;
	}



	protected function generateSign($params)
	{

	    ksort($params);

		$stringToBeSigned = substr(md5($this->appSecret),8,16);  // 16位MD5加密;
		foreach ($params as $k => $v)
		{
			
			if(is_string($v) && "@" != substr($v, 0, 1) and $v<>"")
			{
				$stringToBeSigned .= "$k$v";
			}
		}
		unset($k, $v);
		$stringToBeSigned .= substr(md5($this->appSecret),8,16);
		
		// echo "===============加密的字符串==================";
		// echo "<br/>";
		// echo $stringToBeSigned;

		$sign= strtoupper(md5($stringToBeSigned));
		if($this->isDebug){
			echo "query==>" .$stringToBeSigned."<br/>";
			echo "sign==>" .$sign."<br/>";
		}

		return $sign;
	}


	public function curl($url, $postFields = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->readTimeout) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
		}
		if ($this->connectTimeout) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		}
		curl_setopt ( $ch, CURLOPT_USERAGENT, "djy-sdk-php-v20160501" );
		//https 请求
		if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		if (is_array($postFields) && 0 < count($postFields))
		{
			$postBodyString = "";
			$postMultipart = false;
			foreach ($postFields as $k => $v)
			{
				if(!is_string($v))
					continue ;
				$postBodyString .= "$k=" . urlencode($v) . "&"; 
			}
			// echo "<br/>";
			// echo "===============post的字符串==================";
			// echo "<br/>";
			// echo $postBodyString; 
			unset($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			 
			$header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
			curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
		 
		}
		$reponse = curl_exec($ch);
		
		if (curl_errno($ch))
		{
			throw new Exception(curl_error($ch),0);
		}
		else
		{
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode)
			{
				throw new Exception($reponse,$httpStatusCode);
			}
		}
		curl_close($ch);
		return $reponse;
	}

}