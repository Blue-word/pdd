<?php
namespace app\index\controller;
use think\Controller;
use think\File;


class Uploadify extends Base{

    /**
     * 图片上传方法
     * @param  string $rootpath 根目录文件夹
     * @param  string $savepath 子目录文件夹
     * @param  int    $type     上传类型1单图2多图
     * @return array           图片全地址或报错
     */
	public function imgUpload($rootpath,$savepath,$type=1){
		//上传规则
        $time = date('Y-m-d',time());
		$path =  '/blue/public/upload/'.$rootpath.'/'.$savepath.'/'.$time;
	    $validate = array(
	    	'size' => 10485760,
	    	'ext'  => 'jpg,png,gif',
	    	);
	    if ($type == 1) {  //单文件上传
	    	$file = request()->file('image');
            if ($file == null) {
                return $file;
            }
            return $file;
	    	// 移动url到/public/uploads/$urlInfo 目录下,保存规则：日期加uniqid文件名
		    $info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'upload' . DS . $rootpath . DS . $savepath . DS . $time);
            // dump($info);
		    if($info){
		    	//$http = "http://";//http协议
		    	//$webpath = UPLOAD_URL;//网站域名
		    	//$picture_url = $http.$webpath.__ROOT__.'/uploads/'.$info->getSaveName();
		    	$saveName = '/'.$info->getSaveName();
		    	$picture_url = $path.$saveName;//图片绝对地址,/jraz/public/uploads/forum_image/house/20170721\123.jpg
		    	$pic_info = $info->getInfo();
		    	$return_result['relative_path'] = $picture_url;
		    	$return_result['filename'] = $pic_info['name'];
		    	$return_result['size'] = $pic_info['size'];
            	$result['code'] = true;  //将数组放入二维数组
            	$result['fileinfo'][] = $return_result;
		    }else{
		    	//上传失败获取错误信息
		    	$result['code'] = false; 
            	$result['fileinfo'] = $file->getError();
		    }
	    }else{  //多文件上传
	    	$files = request()->file('image');
            if ($files == null) {
                return $files;
            }
            foreach($files as $k => $v){
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $v->validate($validate)->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'upload' . DS . $rootpath . DS . $savepath . DS . $time);
                if($info){
                    $saveName = '/'.$info->getSaveName();
                    $picture_url = $path.$saveName;//图片绝对地址,/jraz/public/uploads/forum_image/house/20170721\123.jpg
                    $pic_info = $info->getInfo();
                    $return_result[$k]['relative_path'] = $picture_url;
                    $return_result[$k]['filename'] = $pic_info['name'];
                    $return_result[$k]['size'] = $pic_info['size'];
                    $result['code'] = true;
                    $result['fileinfo'] = $return_result;
                }else{
                    //上传失败获取错误信息
                    $result['code'] = false; 
                    $result['fileinfo'] = $file->getError();
                }    
            }
	    }
	    return $result;
	}

    public function imgUpload_1($rootpath,$savepath,$type=1){
        $time = date('Y-m-d',time());
        $path =  '/public/upload/'.$rootpath.'/'.$savepath.'/'.$time;
        $validate = array(
            'size' => 10485760,
            'ext'  => 'jpg,png,gif',
            );
        if ($type == 1) {  //单文件上传
            $file = request()->file('image');
            if ($file == null) {
                return $file;
            }
            // 移动url到/public/uploads/$urlInfo 目录下,保存规则：日期加uniqid文件名
            $info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'upload' . DS . $rootpath . DS . $savepath . DS . $time);
            if($info){
                $saveName = '/'.$info->getSaveName();
                $picture_url = __ROOT__.$path.$saveName;
                // 生成缩略图
                $image = \think\Image::open($info->getRealPath());
                return $image;
                $thumb_url_1 = ROOT_PATH.$path.'/a'.$info->getFilename();
                $thumb_url_2 = ROOT_PATH.$path.'/b'.$info->getFilename();
                $image->thumb(2150,2150,\think\Image::THUMB_SCALING)->save($thumb_url_2);   //2150分辨率
                $image->thumb(1150,1150,\think\Image::THUMB_SCALING)->save($thumb_url_1);   //1150分辨率
                $pic_info = $info->getInfo();
                //原图
                $return_result['relative_path_2'] = $picture_url;
                //2000分辨率
                $return_result['relative_path_1'] = __ROOT__.$path.'/b'.$info->getFilename();
                //1150分辨率
                $return_result['relative_path'] = __ROOT__.$path.'/a'.$info->getFilename();

                $result['code'] = true;  //将数组放入二维数组
                $result['fileinfo'][] = $return_result;
            }else{
                //上传失败获取错误信息
                $result['code'] = false; 
                $result['fileinfo'] = $file->getError();
            }
        }else{  //多文件上传
            $files = request()->file('image');
            if ($files == null) {
                return $files;
            }
            foreach($files as $k => $v){
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $v->validate($validate)->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'upload' . DS . $rootpath . DS . $savepath . DS . $time);
                if($info){
                    $saveName = '/'.$info->getSaveName();
                    $picture_url = __ROOT__.$path.$saveName;
                    // 生成缩略图
                    $image = \think\Image::open($info->getRealPath());
                    $thumb_url_1 = ROOT_PATH.$path.'/a'.$info->getFilename();
                    $thumb_url_2 = ROOT_PATH.$path.'/b'.$info->getFilename();
                    $qwe = $image->thumb(2150,2150,\think\Image::THUMB_SCALING)->save($thumb_url_2);   //70分辨率
                    $asd = $image->thumb(1150,1150,\think\Image::THUMB_SCALING)->save($thumb_url_1);   //150分辨率
                    $pic_info = $info->getInfo();
                    //原图
                    $return_result[$k]['relative_path'] = $picture_url;
                    //1150分辨率
                    $return_result[$k]['relative_path_1'] = __ROOT__.$path.'/a'.$info->getFilename();
                    //2000分辨率
                    $return_result[$k]['relative_path_2'] = __ROOT__.$path.'/b'.$info->getFilename();
                    
                    $result['code'] = true;
                    $result['fileinfo'] = $return_result;
                }else{
                    //上传失败获取错误信息
                    $result['code'] = false; 
                    $result['fileinfo'] = $file->getError();
                }    
            }
        }
        return $result;
    }

    public function uploads($rootpath,$savepath){
        //上传规则
        $path =  '/public/upload/'.$rootpath.'/'.$savepath;
        $validate = array(
            'size' => 10485760,
            'ext'  => 'jpg,png,gif',
            );
        $files = request()->file('image');
        if ($files == null) {
            return $files;
        }
        // return $files;
        foreach($files as $file){
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload' . DS . $rootpath . DS . $savepath);
            if($info){
                $saveName = '/'.$info->getSaveName();
                $picture_url = __ROOT__.$path.$saveName;//图片绝对地址,/jraz/public/uploads/forum_image/house/20170721\123.jpg
                $pic_info = $info->getInfo();
                $return_result[$k]['relative_path'] = $picture_url;
                $return_result[$k]['filename'] = $pic_info['name'];
                $return_result[$k]['size'] = $pic_info['size'];
                $result['code'] = true;
                $result['fileinfo'] = $return_result;
            }else{
                //上传失败获取错误信息
                $result['code'] = false; 
                $result['fileinfo'] = $file->getError();
            }    
        }
        
        return $result;
    }
    /**
     * fileinput插件文件上传
     *
     * @author 蓝勇强 2018-12-27
     * @return [type] [description]
     */
    public function imgUp(){
        //接收传值
        $rootpath = I('get.rootpath','');
        $savepath = I('get.savepath','');
        $type = I('get.type',2);
        // return $rootpath;
        $upload_result = $this->imgUpload($rootpath,$savepath,$type);
        // $this->ajaxReturn($upload_result,'json');
        // exit (json_encode($upload_result));
        if($upload_result['code']){
            foreach($upload_result['fileinfo'] as $k => $v){
                $initPrev[$k] = $v['relative_path'];
                $initPreConf[$k] = array( 
                    'caption' => $v['filename'], 
                    'size' => $v['size'], 
                    'width' => '213px', 
                    'url' => '../Uploadify/imageDelupload', 
                    'key' => $v['relative_path']
                );
            }
            $return_data = array(
                'initialPreview' => $initPrev,  //初始化展示
                'initialPreviewConfig' => $initPreConf, //预览图配置
                'append' => true,
                );
        }else{
            $return_data['state'] = false;
            $return_data['msg'] = $upload_result['fileinfo'];
        }
       //配合fileinput返回4个数据
        $this->ajaxReturn($return_data,'json');
        //dump($return_data);
    }
   
    public function upload(){
        $func = I('func');
        $path = I('path','temp');
        $info = array(
        	'num'=> I('num/d'),
            'title' => '',       	
            'upload' =>U('admin/Uploadify/imageUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'images')),
            'size' => '4M',
            'type' =>'jpg,png,gif,jpeg',
            'input' => I('input'),
            'func' => empty($func) ? 'undefined' : $func,
        );
        // dump($info);
        $this->assign('info',$info);
        return $this->fetch();
    }
    
    /*
       删除上传的图片
     */
    public function delupload(){
        $action = I('action');                
        $filename= I('filename');
        $filename= str_replace('../','',$filename);
        $filename= trim($filename,'.');
		if($action=='del' && !empty($filename) && file_exists($filename)){
            $size = getimagesize($filename);
            $filetype = explode('/',$size['mime']);
            if($filetype[0]!='image'){
                return false;
                exit;
            }
            unlink($filename);
            exit;
        }
        return false;
    }
    /**
     * fileinput图片删除
     *
     * @author 蓝勇强 2018-12-27
     * @return [type] [description]
     */
    public function imageDelupload(){
        $picture = I('key');                
        $filename= trim($filename,'.');
        $ROOT_PATH = str_replace("\blue","",ROOT_PATH);
        $filename = $ROOT_PATH.$picture;
        if(!empty($picture) && file_exists($filename)){
            $size = getimagesize($filename);
            $filetype = explode('/',$size['mime']);
            if($filetype[0] != 'image'){
                return false;
            }
            unlink($filename);
        }
        return true;
    }

    public function imageUp()
    {       
        // 上传图片框中的描述表单名称，
        $pictitle = I('pictitle');
        $dir = I('dir');
        $title = htmlspecialchars($pictitle , ENT_QUOTES);        
        $path = htmlspecialchars($dir, ENT_QUOTES);
       
        //$input_file           ['upfile'] = $info['Filedata'];  一个是上传插件里面来的, 另外一个是 文章编辑器里面来的
        // 获取表单上传文件
        $file = request()->file('Filedata');
        
        if(empty($file))
            $file = request()->file('upfile');    
        
        $result = $this->validate(
            ['file2' => $file], 
            ['file2'=>'image','file2'=>'fileSize:20000000'],
            ['file2.image' => '上传文件必须为图片','file2.fileSize' => '上传文件过大']                
           );        
        if(true !== $result){            
            $state = "ERROR" . $result;
        }else{
            // 移动到框架应用根目录/public/uploads/ 目录下
            $this->savePath = $this->savePath.date('Y').'/'.date('m-d').'/';
            $info = $file->rule(function ($file) {    
            return  md5(mt_rand()); // 使用自定义的文件保存规则
            })->move('public/upload/'.$this->savePath);        
           //echo print_r($info,true);              
            if ($info) 
                $state = "SUCCESS";                         
            else 
                $state = "ERROR" . $file->getError();                
            $return_data['url'] = '/public/upload/'.$this->savePath.$info->getSaveName();            
        }
        
        
        $return_data['title'] = $title;
        $return_data['original'] = ''; // 这里好像没啥用 暂时注释起来
        $return_data['state'] = $state;
        $return_data['path'] = $path;        
        //print_r($return_data);
        $this->ajaxReturn($return_data,'json');
    }

}