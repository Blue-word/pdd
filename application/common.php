<?php
use think\Request;
// 应用公共文件
/**
 * 图片地址转换
 * @param   $url  图片地址
 * @param   $type 转换类型
 * @return        转换后地址
 */
function img_url_transform($url,$type){
    $http = "http://";//http协议
    $website = UPLOAD_URL;//网站域名
    if ($url == '') {
        return null;
    }

    if($type == 'absolute'){    //补充成全地址
        if(is_array($url)){   //看是否是数组
            foreach($url as $v){
                $result[] = $http.$website.$v;
            } 
        }else{
            $result = $http.$website.$url;
        }  
    }
    if($type == 'relative'){    //删减为相对地址
        if(is_array($url)){
            foreach($url as $v){
                $result[] = str_replace($http.$website,'',$v);
            }
        }else{
            $result = str_replace($http.$website,'',$url);
        }     
    }
    return $result;
}

/**
 * 管理员操作记录
 * @author: 蓝勇强 2019/2/2 11:19
 * @param $log_info
 * @return mixed
 */
function adminLog($log_info){
    $request = Request::instance();
    $add['log_time'] = date('Y-m-d H:i:s',time());
    $add['admin_id'] = session('uid');
    $add['log_info'] = $log_info;
    $add['log_ip'] = $request->ip();
    $add['log_url'] = request()->baseUrl() ;
    M('admin_log')->add($add);
    return $add;
}

/**
 * 用户日志记录
 * @author: 蓝勇强
 * Date: 2019/2/2 11:18
 * @param $log_info
 * @param $openid
 * @return mixed
 */
function newLog($log_info,$openid){
    $request = Request::instance();
    $add['log_time'] = date('Y-m-d H:i:s',time());
    $add['new_id'] = $openid;
    $add['log_info'] = $log_info;
    $add['log_ip'] = $request->ip();
    $add['log_url'] = request()->baseUrl() ;
    M('new_log')->add($add);
    return $add;
}
/**
 * code加密
 */
function encrypt($str){
   return md5(C("AUTH_CODE").$str);
}
/**
 * code加密
 */
function getIP(){ 
    global $ip;           
    if (getenv("HTTP_CLIENT_IP"))
         $ip = getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if(getenv("REMOTE_ADDR"))
         $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow";
    return $ip;
}

function http_request($url){
    if (empty($url)) {
        return false;
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function httpPost($url,$post_data){   
    if (empty($url)) {
        return false;
    }
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    // post数据  
    curl_setopt($ch, CURLOPT_POST, 1);  
    // post的变量  
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);  
    $output=json_decode(curl_exec($ch),true);  
    curl_close($ch);  
    return $output;  
} 

function https_request($url = ''){
    if (empty($url)) {
        return false;
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $output=json_decode(curl_exec($curl),true);
    //$data = curl_exec($curl);
    curl_close($curl);
    return $output;
}
/*
时间转换函数，几小时前几天前
 */
function time_change($time){  
    $t=time()-$time;
    $f=array(  
        '31536000'=>'年',  
        '2592000'=>'个月',  
        '604800'=>'星期',  
        '86400'=>'天',  
        '3600'=>'小时',  
        '60'=>'分钟',  
        '1'=>'秒'  
    );  
    foreach ($f as $k=>$v)    {  
        if (0 !=$c=floor($t/(int)$k)) {  
            return $c.$v.'前';  
        }  
    }  
}

function api_notice_increment($url, $data){
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    //     var_dump($tmpInfo);
    //    exit;
    if (curl_errno($ch)) {
      return false;
    }else{
      // var_dump($tmpInfo);
      return $tmpInfo;
    }
  }

  function httpsGet($url){  
    if (empty($url)) {
        return false;
    }
    $curl = curl_init();  
    curl_setopt($curl, CURLOPT_URL, $url);  
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);// https请求不验证证书和hosts  
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  
    $res=json_decode(curl_exec($curl),true);  
    curl_close($curl);  
    return $res;  
} 