<?php
namespace app\api\controller;
use think\Controller;
use think\Request;
use think\Cache;

class Index extends Common{

	public function _initialize(){  //继承父类而不覆盖父类构造方法
        // parent::_initialize();   //关闭时不调用父类检验token
    } 

    private function getAccesstoken(){
    	// access_token缓存5400秒，一个半小时
    	$access_token = Cache::get('access_token');
    	if ($access_token) {		//access_token缓存未失效
    		return $access_token;
    	}else{			//access_token缓存已失效
    		$request_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.C('WEIXIN.APPID').'&secret='.C('WEIXIN.APPSECRET');
			$content = $this->http_request($request_url);  
		    $result = json_decode($content,true);
		    // $access_token = $result['access_token'];
		    Cache::set('access_token',$result['access_token'],5400);  //重新缓存
		    $access_token = Cache::get('access_token');
		    return $access_token;
    	}
    }


    public function index(){
    	$token     = 'Blue123456';    //此处填写开发者配置的token
	    $signature = $_GET['signature'];
		$nonce 	   = $_GET['nonce'];
		$timestamp = $_GET['timestamp'];
		$echostr   = $_GET['echostr'];  

		$tmpArr = array($timestamp,$nonce,$token);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);

		if($tmpStr == $signature){
			echo $echostr;
          	exit;
		}else{
			return false;
		}
	}

	public function index1(){
		$type = input('get.type/d');
		dump(Session::has(session_id()));
		if (Session::has(session_id())) {
			if ($type == 1) {  //第一个页面
	    		$url = 'http://www.netqlv.com/taiping/index.html#/register';
	    	}elseif ($type == 2) {
	    		$url = 'http://www.netqlv.com/taiping/index.html#/course';
	    	}elseif ($type == 3) {
	    		$url = 'http://www.netqlv.com/taiping/index.html#/sign';
	    	}else{
	    		$url = 'http://www.netqlv.com/njql';
	    	}
	    	$this->redirect($url,302);
	    }else{
	    	$appid = C('WEIXIN.APPID');
		    $redirecturl = urlencode('http://www.netqlv.com/blue/api/index/getuserinfo');  
		    $snsapi_userInfo_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirecturl.'&response_type=code&scope=snsapi_userinfo&state='.$type.'#wechat_redirect'; 
			header("Location:".$snsapi_userInfo_url);
	    }	
	}

	public function index2(){     //扫码登录微信授权并跳转
		$id = input('id/d');  //创说会活动id
	    $access_token = $this->getAccesstoken();
		$openid = Session::get(session_id());
		if ($openid) {   //该用户授权登录过
			$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token;
			$content = httpsGet($url);
			$openid_array = $content['data']['openid'];
			if (in_array($openid,$openid_array,FALSE)) {   //已关注该公众号
				$url = 'http://www.netqlv.com/taiping/index.html#/activity-sign?id='.$id;
		    	$this->redirect($url,302);
			}else{   //未关注公众号
	          	$code_url = 'https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzUyNTcwMzE5OA==&scene=124#wechat_redirect';
				header("Location:".$code_url);
			}
		}else{		//该用户授权未登录过
			$appid = C('WEIXIN.APPID');
		    $redirecturl = urlencode('http://www.netqlv.com/blue/api/index/getuserinfo1');  
		    $snsapi_userInfo_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirecturl.'&response_type=code&scope=snsapi_userinfo&state='.$id.'#wechat_redirect';
			header("Location:".$snsapi_userInfo_url);
		}
	}

	public function getuserinfo1(){         //扫码进入的微信网页授权登录地址
		$appid = C('WEIXIN.APPID');
		$appsecret = C('WEIXIN.APPSECRET'); 
	    //2.用户手动同意授权,同意之后,获取code  
	    //页面跳转至redirect_uri/?code=CODE&state=STATE  
      	$code = $_GET['code'];  
      	$state = $_GET['state'];  
	    //3.通过code换取网页授权access_token  
	    $curl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';  
	    $content = $this->http_request($curl);  
	    $result = json_decode($content);
	    if (isset($result)) {
	    	//4.通过access_token和openid拉取用户信息  
		    $webAccess_token = $result->access_token;  
		    $openid = $result->openid;  
          	//dump($openid); 
		    $openid_count = M('userinfo')->where('openid',$openid)->count();
          	if (!(Session::has(session_id()))) {
              	//echo 'true';
		    	Session::set(session_id(),$openid);
		    }
		    if (!$openid_count) {    //数据库未保存过该用户信息
		    	$userInfourl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$webAccess_token.'&openid='.$openid.'&lang=zh_CN ';  
			    $recontent = $this->http_request($userInfourl);  
			    $userInfo = json_decode($recontent,true);
			    if (isset($userInfo)) {
			    	$data = array(
				    	'openid'  => $userInfo['openid'],
				    	'nickname'  => $userInfo['nickname'],
				    	'sex'  => $userInfo['sex'],
				    	'province'  => $userInfo['province'],
				    	'city'  => $userInfo['city'],
				    	'country'  => $userInfo['country'],
				    	'headimgurl'  => $userInfo['headimgurl'],
				    	'privilege'  => $userInfo['privilege'],
				    	'unionid'  => $userInfo['unionid'],
				    );  
				    $res = M('userinfo')->save($data);
			    }
		    }
		    if ($openid) {   //该用户授权登录过
              	$access_token = $this->getAccesstoken();
				$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token;
				$content = httpsGet($url);
				$openid_array = $content['data']['openid'];
				if (in_array($openid,$openid_array,FALSE)) {   //已关注该公众号
					$url = 'http://www.netqlv.com/taiping/index.html#/activity-sign?id='.$state;
			    	$this->redirect($url,302);
				}else{   //未关注公众号
		          	$code_url = 'https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzUyNTcwMzE5OA==&scene=124#wechat_redirect';
					header("Location:".$code_url);
				}
			}
	    }
	}

	public function getuserinfo(){         //微信网页授权登录地址
		$type = input('get.type/d');   //1第一个页面2第二个页面3第三个页面
		//1.准备scope为snsapi_userInfo网页授权页面
		$appid = C('WEIXIN.APPID');
		
	    $redirecturl = urlencode('http://www.netqlv.com/blue/api/index/getuserinfo');  
	    $snsapi_userInfo_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirecturl.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';  
	      
	    //2.用户手动同意授权,同意之后,获取code  
	    //页面跳转至redirect_uri/?code=CODE&state=STATE  
	    $code = $_GET['code'];  
	    //3.通过code换取网页授权access_token  
	    $curl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';  
	    $content = $this->http_request($curl);  
	    $result = json_decode($content);
	    if (isset($result)) {
	    	//4.通过access_token和openid拉取用户信息  
		    $webAccess_token = $result->access_token;  
		    $openid = $result->openid;  
		    //检验access_token是否有效
		    $is_token = 'https://api.weixin.qq.com/sns/auth?access_token='.$webAccess_token.'&openid='.$openid;
		    $is_token_res = $this->http_request($is_token);  
		    $is_token_info = json_decode($is_token_res,true);
		    if ($is_token_info['errmsg'] == 'invalid openid' && $is_token_info['errcode'] == 40003) {   //access_token失效
		    	$curl = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$openid.'&grant_type=refresh_token&refresh_token='.$result->refresh_token;
		    	$content = $this->http_request($curl);  
			    $result = json_decode($content,true);
			    $webAccess_token = $result->access_token;  
		    	$openid = $result->openid; 
		    }
		    $openid_count = M('userinfo')->where('openid',$openid)->count();
          	if (!(Session::has(session_id()))) {
              	echo 'true';
		    	Session::set(session_id(),$openid);
		    }
		    if (!$openid_count) {    //数据库已保存过该用户信息
		    	$userInfourl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$webAccess_token.'&openid='.$openid.'&lang=zh_CN ';  
			    $recontent = $this->http_request($userInfourl);  
			    $userInfo = json_decode($recontent,true);
			    if (isset($userInfo)) {
                  	//dump($userInfo);
			    	$data = array(
				    	'openid'  => $userInfo['openid'],
				    	'nickname'  => $userInfo['nickname'],
				    	'sex'  => $userInfo['sex'],
				    	'province'  => $userInfo['province'],
				    	'city'  => $userInfo['city'],
				    	'country'  => $userInfo['country'],
				    	'headimgurl'  => $userInfo['headimgurl'],
				    	'privilege'  => $userInfo['privilege'],
				    	'unionid'  => $userInfo['unionid'],
				    );  
				    $res = M('userinfo')->save($data);
			    }
		    }
	    }

	    if (Session::has(session_id())) {
	    	if ($type == 1) {  //第一个页面
	    		$url = 'http://www.netqlv.com/taiping/index.html#/register';
	    	}elseif ($type == 2) {
	    		$url = 'http://www.netqlv.com/taiping/index.html#/course';
	    	}elseif ($type == 3) {
	    		$url = 'http://www.netqlv.com/taiping/index.html#/sign';
	    	}else{
	    		$url = 'http://www.netqlv.com/njql';
	    	}
	    	$this->redirect($url,302);
	    }else{
	    	dump(Session::has(session_id()));
	    }
      	//Session::delete(session_id());
      	
	}

	public function getOpenid(){		//获取用户openID
		$session_id = session_id();
		if (Session::has($session_id)) {
			$openid = Session::get('name');
			$this->apiReturn('获取成功','200',$openid);
		}else{
			$this->apiReturn('获取失败','400','获取失败');
		}
	}

	public function getAccesstoken1(){
		$request_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.C('WEIXIN.APPID').'&secret='.C('WEIXIN.APPSECRET');
		$content = $this->http_request($request_url);  
	    $result = json_decode($content,true);
	    dump($result);
	}

	public function get_jsapi_ticket(){		//获取jsapi_ticket
		if (Cache::get('access_token')) {
			$access_token = Cache::get('access_token');
		}else{
			$request_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxe3138b54c4fe240e&secret='.C('WEIXIN.APPSECRET');
			$content = $this->http_request($request_url);  
		    $result = json_decode($content,true);
		    $access_token = $result['access_token'];
	      	Cache::set('access_token',$access_token,7200);
		}

		if (Cache::get('jsapi')) {
			$jsapi = Cache::get('jsapi');
		}else{
			$request_url_1 = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
		    $content_1 = $this->http_request($request_url_1);
		    $result_1 = json_decode($content_1,true);
		    $jsapi = $result_1['ticket'];
	      	Cache::set('jsapi',$jsapi,7200);
		}
	    $noncestr = $this->getRandomCode(16);//随机字符串
	    $timestamp = time();		//时间戳
	    $url = 'http://www.netqlv.com/taiping/index.html';  //当前网页的URL
	    //签名是按照(字典序)顺序
        $post['jsapi_ticket'] = $jsapi;     //有效的jsapi_ticket
        $post['noncestr'] = $noncestr;      //随机字符串
        $post['timestamp'] = $timestamp;    //时间戳
        $post['url'] = $url;  				//当前网页的URL
        ksort($post);		//字典序
        $string = $this->ToUrlParams($post);  //参数进行拼接key=value&k=v
        $signature = sha1($string);
        $res['noncestr'] = $noncestr;
        $res['timestamp'] = $timestamp;
        $res['appid'] = C('WEIXIN.APPID');
        $res['signature'] = $signature;
      	$res['access_token'] = Cache::get('access_token');
        $res['jsapi'] = Cache::get('jsapi');
      	$this->apiReturn('获取成功','200',$res);
	}

	public function test1(){
		$request_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.C('WEIXIN.APPID').'&secret='.C('WEIXIN.APPSECRET');
		$content = $this->http_request($request_url);  
	    $result = json_decode($content,true);
	    $access_token = $result['access_token'];
		$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
		$post_data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 124}}}';
		$content_1 = $this->http_request($url,$post_data);     //POST方式请求http
		$result_1 = json_decode($content_1,true);
		$ticket = $result_1['ticket'];
		$ticket_1 = urlencode($ticket);
		// $ticket = 'gQH38TwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyNXBPVk5CdVNjQmkxMDAwMDAwM3AAAgSy0bFaAwQAAAAA';
		// $ticket_1 = urlencode($ticket);
		// $url_1 = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket_1;
		// $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token;
		// $content_1 = https_request($url_1);
		// $content_2 = httpsGet($url);
		// $openid = 'oHvYl0kZ5VHvBDREMnbs3uxv1eYY';
		// $array = $content_2['data']['openid'];
		// dump($array);
		// if (in_array($openid,$array,FALSE)) {
		// 	echo 1;
		// }else{
		// 	echo 2;
		// }
		// $content_3 = http_request($url);
		// dump($result);
		dump($result);
		dump($content_1);
		var_dump($result_1);
		// echo $content_2;
		// dump($content_2);
		// dump($content_3);
		// https://mp.weixin.qq.com/mp/profile_ext?action=home&  biz=MzUyNTcwMzE5OA==&scene=124#wechat_redirect
		https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzUyNTcwMzE5OA==#wechat_webview_type=1&wechat_redirect
	} 

	// 添加客服账号
	public function add_customer_service(){
	    $access_token = $this->getAccesstoken();
		$url = 'https://api.weixin.qq.com/customservice/kfaccount/add?access_token=ACCESS_TOKEN';
		$post_data = array(
			"kf_account" => "taipingkefu1@taipingjiangsufgs",
		    "nickname" => "太平客服",
		    "password" => "tp2580",
		);
		$json_post = json_encode($post_data);
		$content = $this->http_request($url,$json_post);
		$res = json_decode(strtolower($content),true);

		if ($res['errcode'] == 0 && $res['errmsg'] == 'ok') {
			return true;
		}else{
			return false;
		}
		dump($res);
	}

	





}

