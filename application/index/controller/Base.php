<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
use think\Phpexcel;
use think\Cache;

class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.tp-shop.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        parent::__construct();
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        // 过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout'))){
        	//return;
        }else{
        	if(session('uid') > 0 ){
                // $asd = $this->check_priv();//检查管理员菜单操作权限
        		$son_uid = $this->son_uid();//查询子管理员
                // dump($son_uid);
        	}else{
        		$this->error('请先登录',U('index/admin/login'),1);
        	}
        }
        $this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
       $tpshop_config = array();
       $tp_config = M('config')->cache(true)->select();
       foreach($tp_config as $k => $v)
       {
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('tpshop_config', $tpshop_config);       
    }

    private function son_uid(){      //子级管理员
        $uid = session('uid');   //管理员uid
        $son_uid = cache('son_uid_'.$uid);  //缓存中获取子级管理员
        if ($son_uid == false) {  //未缓存
            $son_uid = M('admin')->where('uid',$uid)->getField('son_uid');
            cache('son_uid_'.$uid, $son_uid, 7200);
        }
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
        $act_list = session('act_list');
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
    	if($ctl == 'Index' || $act_list == 'all'){
    		//后台首页控制器无需验证,超级管理员无需验证
    		return true;
    	}elseif(request()->isAjax() || strpos($act,'ajax')!== false || in_array($act,$uneed_check)){
    		//所有ajax请求不需要验证权限
    		return true;
    	}else{
    		$right = M('system_menu')->where("id", "in", $act_list)->getField('right',true);

    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'@'.$act, $role_right)){
    			$this->error('您没有操作权限['.($ctl.'@'.$act).'],请联系超级管理员分配权限');
    		}
    		
    		 
    	}
    }
    
    public function ajaxReturn($data,$type = 'json'){                        
            exit(json_encode($data));
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

    public function percentage($new,$old){       //两个数值的差值百分比
        //(new-old)/old
        if ($old == 0 && $new == 0) {  //分母为0，分子为0
            return 0;
        }elseif($old == 0 && $new !== 0){  //分母为0，分子不为0 
            return round($new*100);
        }elseif ($old !== 0 && $new !== 0) {   //分母不为0，分子不为0 
            return round(($new-$old)/$old)*100;
        }else{
            return '异常';
        }
    }

    /** 
     * author:10xjzheng
     * Excel导入
     * @param title 导入表格的字段
     * @param tableName 导入表格的名字
     * @param savePath 文件保存的路径，默认在Public/Excel/
     */
    public function importExcel($tableName,$title,$savePath="public/upload/excel/"){   
        if (request()->isPost()) {
            $file = request()->file('file');
            $file_types = explode ( ".", $_FILES ['file'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['size'=>10485760,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public' . DS . 'upload' . DS . 'excel' . DS . date('Y-m-d'),'',true);
            // dump($file);
            if ($info) {
                //获取文件所在目录名
                // $filename = iconv("GB2312","UTF-8",$info->getFilename());
                $path = ROOT_PATH . 'public' . DS . 'upload' . DS . 'excel' . DS . date('Y-m-d') . DS . $info->getFilename();
                // dump($path);
                $ExcelToArrary = new Phpexcel();//实例化
                $res = $ExcelToArrary->read($path,"UTF-8",$file_type);//传参,判断office2007还是office2003
                if ($res) {
                    foreach ( $res as $k => $v ) //循环excel表
                    {
                        if($k>1){
                            $k=$k-2;//addAll方法要求数组必须有0索引
                            for ($i=0; $title[$i]; $i++) {  //i=0,i++后为1从第一列开始扫描
                                $data[$k][$title[$i]] = $v [$i];//创建二维数组 
                                // dump($data[$k][$title[$i]]);
                            }
                        }
                    }
                    $result['status'] = true;
                    $result['data'] = $data;
                }else{
                    $result['status'] = false;
                    $result['data'] = '读取Excel文件失败';
                }
                return $result;
            }else{
                $error_info = $file->getError();
                $result['status'] = false;
                $result['data'] = $error_info;
                return $result;
            }
        }
    }




}