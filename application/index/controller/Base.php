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
        // if(in_array(ACTION_NAME,array('login','logout'))){
        // 	//return;
        // }else{
        // 	if(session('uid') > 0 ){
        //         // $asd = $this->check_priv();//检查管理员菜单操作权限
        // 		$son_uid = $this->son_uid();//查询子管理员
        //         // dump($son_uid);
        // 	}else{
        // 		$this->error('请先登录',U('index/admin/login'),1);
        // 	}
        // }
        // $this->public_assign();
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
    /**
     * Excel导出
     * @param data 导出数据
     * @param title 表格的字段名 字段长度有限制，一般都够用，可以改变 $length1和$length2来增长
     * @return subject 表格主题 命名为主题+导出日期
     */
    public function exportExcel($data,$title,$subject){  
        Vendor('phpexcel.PHPExcel');
        Vendor('phpexcel.PHPExcel.IOFactory');
        Vendor('phpexcel.PHPExcel.Reader.Excel5');
        // Create new PHPExcel object  
        $objPHPExcel = new \PHPExcel();
        // Set properties  
        $objPHPExcel->getProperties()->setCreator("ctos")  
            ->setLastModifiedBy("ctos")  
            ->setTitle("Office 2007 XLSX Test Document")  
            ->setSubject("Office 2007 XLSX Test Document")  
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
            ->setKeywords("office 2007 openxml php")  
            ->setCategory("Test result file");  
        $length1=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD');
        $length2=array('A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1','O1','P1','Q1','R1','S1','T1','U1','V1','W1','X1','Y1','Z1','AA1','AB1','AC1','AD1');
        //set width  
        for($a=0;$a<count($title);$a++){
             $objPHPExcel->getActiveSheet()->getColumnDimension($length1[$a])->setWidth(20); 
        }
        // dump($objPHPExcel);
        // set font size bold  
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);  
        $objPHPExcel->getActiveSheet()->getStyle($length2[0].':'.$length2[count($title)-1])->getFont()->setBold(true); 
        $objPHPExcel->getActiveSheet()->getStyle($length2[0].':'.$length2[count($title)-1])->getFont()->setBold(true);    
        $objPHPExcel->getActiveSheet()->getStyle($length2[0].':'.$length2[count($title)-1])->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);  
        // return $objPHPExcel;
        // set table header content  
        // dump(\PHPExcel_Style_Border::BORDER_THIN);
        // dump(\PHPExcel_IOFactory::createWriter());
        for($a=0;$a<count($title);$a++){
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue($length2[$a], $title[$a]); 
        }
        for($i=0;$i<count($data);$i++){ 
            $buffer=$data[$i];
            $number=0;
            foreach ($buffer as $value) {
                $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($length1[$number].($i+2),$value,\PHPExcel_Cell_DataType::TYPE_STRING);//设置单元格为文本格式
                $number++;
            }
            unset($value);
            $objPHPExcel->getActiveSheet()->getStyle($length1[0].($i+2).':'.$length1[$number-1].($i+2))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);  
            $objPHPExcel->getActiveSheet()->getStyle($length1[0].($i+2).':'.$length1[$number-1].($i+2))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);  
            $objPHPExcel->getActiveSheet()->getRowDimension($i+2)->setRowHeight(16);  
        }  
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet  
        $objPHPExcel->setActiveSheetIndex(0); 
        // dump($objPHPExcel); 

        ob_end_clean();//清除缓冲区,避免乱码
        // Redirect output to a client’s web browser (Excel5)  
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-Disposition: attachment;filename='.$subject.'('.date('Y-m-d').').xls');  
        header('Cache-Control: max-age=0');  
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); 

        $objWriter->save('php://output');  
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
        $post_data = json_encode($msg_data,JSON_UNESCAPED_UNICODE);
        $content = $this->http_request($url,$post_data);
        $res = json_decode($content,true);
        return $res;
    }


    public function exportExcel_1(){  
        Vendor('phpexcel.PHPExcel');
        Vendor('phpexcel.PHPExcel.IOFactory');
        Vendor('phpexcel.PHPExcel.Reader.Excel5');
        // Create new PHPExcel object  
        $objPHPExcel = new \PHPExcel();
        // Set document properties 设置文件信息
        echo date('H:i:s') , " Set document properties" , EOL;
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                                     ->setLastModifiedBy("Maarten Balliauw")
                                     ->setTitle("Office 2007 XLSX Test Document")
                                     ->setSubject("Office 2007 XLSX Test Document")
                                     ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                                     ->setKeywords("office 2007 openxml php")
                                     ->setCategory("Test result file");


        // Create a first sheet, representing sales data
        echo date('H:i:s') , " Add some data" , EOL;
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Invoice');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', \PHPExcel_Shared_Date::PHPToExcel( gmmktime(0,0,0,date('m'),date('d'),date('Y')) ));//设置具体的数值
        $objPHPExcel->getActiveSheet()->getStyle('D1')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX15); //设置展示格式
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '#12566');

        $objPHPExcel->getActiveSheet()->setCellValue('A3', 'Product Id');
        $objPHPExcel->getActiveSheet()->setCellValue('B3', 'Description');
        $objPHPExcel->getActiveSheet()->setCellValue('C3', 'Price');
        $objPHPExcel->getActiveSheet()->setCellValue('D3', 'Amount');
        $objPHPExcel->getActiveSheet()->setCellValue('E3', 'Total');

        $objPHPExcel->getActiveSheet()->setCellValue('A4', '1001');
        $objPHPExcel->getActiveSheet()->setCellValue('B4', 'PHP for dummies');
        $objPHPExcel->getActiveSheet()->setCellValue('C4', '20');
        $objPHPExcel->getActiveSheet()->setCellValue('D4', '1');
        $objPHPExcel->getActiveSheet()->setCellValue('E4', '=IF(D4<>"",C4*D4,"")'); //支持直接写入公式

        $objPHPExcel->getActiveSheet()->setCellValue('A5', '1012');
        $objPHPExcel->getActiveSheet()->setCellValue('B5', 'OpenXML for dummies');
        $objPHPExcel->getActiveSheet()->setCellValue('C5', '22');
        $objPHPExcel->getActiveSheet()->setCellValue('D5', '2');
        $objPHPExcel->getActiveSheet()->setCellValue('E5', '=IF(D5<>"",C5*D5,"")');

        $objPHPExcel->getActiveSheet()->setCellValue('E6', '=IF(D6<>"",C6*D6,"")');
        $objPHPExcel->getActiveSheet()->setCellValue('E7', '=IF(D7<>"",C7*D7,"")');
        $objPHPExcel->getActiveSheet()->setCellValue('E8', '=IF(D8<>"",C8*D8,"")');
        $objPHPExcel->getActiveSheet()->setCellValue('E9', '=IF(D9<>"",C9*D9,"")');

        $objPHPExcel->getActiveSheet()->setCellValue('D11', 'Total excl.:');
        $objPHPExcel->getActiveSheet()->setCellValue('E11', '=SUM(E4:E9)'); //支持直接写入Excel函数

        $objPHPExcel->getActiveSheet()->setCellValue('D12', 'VAT:');
        $objPHPExcel->getActiveSheet()->setCellValue('E12', '=E11*0.21');

        $objPHPExcel->getActiveSheet()->setCellValue('D13', 'Total incl.:');
        $objPHPExcel->getActiveSheet()->setCellValue('E13', '=E11+E12');

        // Add comment
        echo date('H:i:s') , " Add comments" , EOL;

        $objPHPExcel->getActiveSheet()->getComment('E11')->setAuthor('PHPExcel');
        $objCommentRichText = $objPHPExcel->getActiveSheet()->getComment('E11')->getText()->createTextRun('PHPExcel:');
        $objCommentRichText->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getComment('E11')->getText()->createTextRun("\r\n");
        $objPHPExcel->getActiveSheet()->getComment('E11')->getText()->createTextRun('Total amount on the current invoice, excluding VAT.');

        $objPHPExcel->getActiveSheet()->getComment('E12')->setAuthor('PHPExcel');
        $objCommentRichText = $objPHPExcel->getActiveSheet()->getComment('E12')->getText()->createTextRun('PHPExcel:');
        $objCommentRichText->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getComment('E12')->getText()->createTextRun("\r\n");
        $objPHPExcel->getActiveSheet()->getComment('E12')->getText()->createTextRun('Total amount of VAT on the current invoice.');

        $objPHPExcel->getActiveSheet()->getComment('E13')->setAuthor('PHPExcel');
        $objCommentRichText = $objPHPExcel->getActiveSheet()->getComment('E13')->getText()->createTextRun('PHPExcel:');
        $objCommentRichText->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getComment('E13')->getText()->createTextRun("\r\n");
        $objPHPExcel->getActiveSheet()->getComment('E13')->getText()->createTextRun('Total amount on the current invoice, including VAT.');
        $objPHPExcel->getActiveSheet()->getComment('E13')->setWidth('100pt');
        $objPHPExcel->getActiveSheet()->getComment('E13')->setHeight('100pt');
        $objPHPExcel->getActiveSheet()->getComment('E13')->setMarginLeft('150pt');
        $objPHPExcel->getActiveSheet()->getComment('E13')->getFillColor()->setRGB('EEEEEE');


        // Add rich-text string
        echo date('H:i:s') , " Add rich-text string" , EOL;
        $objRichText = new \PHPExcel_RichText(); //创建富文本对象
        $objRichText->createText('This invoice is ');

        $objPayable = $objRichText->createTextRun('payable within thirty days after the end of the month');
        $objPayable->getFont()->setBold(true);
        $objPayable->getFont()->setItalic(true);
        $objPayable->getFont()->setColor( new \PHPExcel_Style_Color( \PHPExcel_Style_Color::COLOR_DARKGREEN ) );

        $objRichText->createText(', unless specified otherwise on the invoice.');

        $objPHPExcel->getActiveSheet()->getCell('A18')->setValue($objRichText);

        // Merge cells
        echo date('H:i:s') , " Merge cells" , EOL;
        $objPHPExcel->getActiveSheet()->mergeCells('A18:E22');
        $objPHPExcel->getActiveSheet()->mergeCells('A28:B28');     // Just to test...
        $objPHPExcel->getActiveSheet()->unmergeCells('A28:B28');   // Just to test...

        // Protect cells
        echo date('H:i:s') , " Protect cells" , EOL;
        $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);   // Needs to be set to true in order to enable any worksheet protection!
        $objPHPExcel->getActiveSheet()->protectCells('A3:E13', 'PHPExcel');

        // Set cell number formats
        echo date('H:i:s') , " Set cell number formats" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('E4:E13')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);

        // Set column widths
        echo date('H:i:s') , " Set column widths" , EOL;
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);

        // Set fonts
        echo date('H:i:s') , " Set fonts" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setName('Candara');
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setSize(20);
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setUnderline(\PHPExcel_Style_Font::UNDERLINE_SINGLE);
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_WHITE);

        $objPHPExcel->getActiveSheet()->getStyle('D1')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_WHITE);
        $objPHPExcel->getActiveSheet()->getStyle('E1')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_WHITE);

        $objPHPExcel->getActiveSheet()->getStyle('D13')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('E13')->getFont()->setBold(true);

        // Set alignments
        echo date('H:i:s') , " Set alignments" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('D11')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('D12')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('D13')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $objPHPExcel->getActiveSheet()->getStyle('A18')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);
        $objPHPExcel->getActiveSheet()->getStyle('A18')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $objPHPExcel->getActiveSheet()->getStyle('B5')->getAlignment()->setShrinkToFit(true);

        // Set thin black border outline around column  设置复杂的格式
        echo date('H:i:s') , " Set thin black border outline around column" , EOL;
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => 'FF000000'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A4:E10')->applyFromArray($styleThinBlackBorderOutline);


        // Set thick brown border outline around "Total"
        echo date('H:i:s') , " Set thick brown border outline around Total" , EOL;
        $styleThickBrownBorderOutline = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THICK,
                    'color' => array('argb' => 'FF993300'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('D13:E13')->applyFromArray($styleThickBrownBorderOutline);

        // Set fills
        echo date('H:i:s') , " Set fills" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFill()->getStartColor()->setARGB('FF808080');

        // Set style for header row using alternative method
        echo date('H:i:s') , " Set style for header row using alternative method" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('A3:E3')->applyFromArray(
                array(
                    'font'    => array(
                        'bold'      => true
                    ),
                    'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    ),
                    'borders' => array(
                        'top'     => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN
                        )
                    ),
                    'fill' => array(
                        'type'       => \PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
                        'rotation'   => 90,
                        'startcolor' => array(
                            'argb' => 'FFA0A0A0'
                        ),
                        'endcolor'   => array(
                            'argb' => 'FFFFFFFF'
                        )
                    )
                )
        );

        $objPHPExcel->getActiveSheet()->getStyle('A3')->applyFromArray(
                array(
                    'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    ),
                    'borders' => array(
                        'left'     => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                )
        );

        $objPHPExcel->getActiveSheet()->getStyle('B3')->applyFromArray(
                array(
                    'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    )
                )
        );

        $objPHPExcel->getActiveSheet()->getStyle('E3')->applyFromArray(
                array(
                    'borders' => array(
                        'right'     => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                )
        );

        // Unprotect a cell
        echo date('H:i:s') , " Unprotect a cell" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getProtection()->setLocked(\PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);

        // Add a hyperlink to the sheet
        echo date('H:i:s') , " Add a hyperlink to an external website" , EOL;
        $objPHPExcel->getActiveSheet()->setCellValue('E26', 'www.phpexcel.net');
        $objPHPExcel->getActiveSheet()->getCell('E26')->getHyperlink()->setUrl('http://www.phpexcel.net'); //加入超链接
        $objPHPExcel->getActiveSheet()->getCell('E26')->getHyperlink()->setTooltip('Navigate to website');
        $objPHPExcel->getActiveSheet()->getStyle('E26')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        echo date('H:i:s') , " Add a hyperlink to another cell on a different worksheet within the workbook" , EOL;
        $objPHPExcel->getActiveSheet()->setCellValue('E27', 'Terms and conditions');
        $objPHPExcel->getActiveSheet()->getCell('E27')->getHyperlink()->setUrl("sheet://'Terms and conditions'!A1");
        $objPHPExcel->getActiveSheet()->getCell('E27')->getHyperlink()->setTooltip('Review terms and conditions');
        $objPHPExcel->getActiveSheet()->getStyle('E27')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        // Add a drawing to the worksheet 
        echo date('H:i:s') , " Add a drawing to the worksheet" , EOL;
        $objDrawing = new \PHPExcel_Worksheet_Drawing(); //添加图片
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        // $objDrawing->setPath('./images/officelogo.jpg');
        $objDrawing->setHeight(36);
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

        // Add a drawing to the worksheet
        echo date('H:i:s') , " Add a drawing to the worksheet" , EOL;
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Paid');
        $objDrawing->setDescription('Paid');
        // $objDrawing->setPath('./images/paid.png');
        $objDrawing->setCoordinates('B15');
        $objDrawing->setOffsetX(110);
        $objDrawing->setRotation(25);
        $objDrawing->getShadow()->setVisible(true);
        $objDrawing->getShadow()->setDirection(45);
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

        // Add a drawing to the worksheet
        echo date('H:i:s') , " Add a drawing to the worksheet" , EOL;
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('PHPExcel logo');
        $objDrawing->setDescription('PHPExcel logo');
        // $objDrawing->setPath('./images/phpexcel_logo.gif');
        $objDrawing->setHeight(36);
        $objDrawing->setCoordinates('D24');
        $objDrawing->setOffsetX(10);
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

        // Play around with inserting and removing rows and columns
        echo date('H:i:s') , " Play around with inserting and removing rows and columns" , EOL;
        $objPHPExcel->getActiveSheet()->insertNewRowBefore(6, 10);
        $objPHPExcel->getActiveSheet()->removeRow(6, 10);
        $objPHPExcel->getActiveSheet()->insertNewColumnBefore('E', 5);
        $objPHPExcel->getActiveSheet()->removeColumn('E', 5);

        // Set header and footer. When no different headers for odd/even are used, odd header is assumed.
        echo date('H:i:s') , " Set header/footer" , EOL;
        $objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BInvoice&RPrinted on &D');
        $objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objPHPExcel->getProperties()->getTitle() . '&RPage &P of &N');

        // Set page orientation and size
        echo date('H:i:s') , " Set page orientation and size" , EOL;
        $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        // Rename first worksheet
        echo date('H:i:s') , " Rename first worksheet" , EOL;
        $objPHPExcel->getActiveSheet()->setTitle('Invoice');


        // Create a new worksheet, after the default sheet
        echo date('H:i:s') , " Create a second Worksheet object" , EOL;
        $objPHPExcel->createSheet();

        // Llorem ipsum... 很长的文本
        $sLloremIpsum = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vivamus eget ante. Sed cursus nunc semper tortor. Aliquam luctus purus non elit. Fusce vel elit commodo sapien dignissim dignissim. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Curabitur accumsan magna sed massa. Nullam bibendum quam ac ipsum. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin augue. Praesent malesuada justo sed orci. Pellentesque lacus ligula, sodales quis, ultricies a, ultricies vitae, elit. Sed luctus consectetuer dolor. Vivamus vel sem ut nisi sodales accumsan. Nunc et felis. Suspendisse semper viverra odio. Morbi at odio. Integer a orci a purus venenatis molestie. Nam mattis. Praesent rhoncus, nisi vel mattis auctor, neque nisi faucibus sem, non dapibus elit pede ac nisl. Cras turpis.';

        // Add some data to the second sheet, resembling some different data types
        echo date('H:i:s') , " Add some data" , EOL;
        $objPHPExcel->setActiveSheetIndex(1);
        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Terms and conditions');
        $objPHPExcel->getActiveSheet()->setCellValue('A3', $sLloremIpsum);
        $objPHPExcel->getActiveSheet()->setCellValue('A4', $sLloremIpsum);
        $objPHPExcel->getActiveSheet()->setCellValue('A5', $sLloremIpsum);
        $objPHPExcel->getActiveSheet()->setCellValue('A6', $sLloremIpsum);

        // Set the worksheet tab color
        echo date('H:i:s') , " Set the worksheet tab color" , EOL;
        $objPHPExcel->getActiveSheet()->getTabColor()->setARGB('FF0094FF');;

        // Set alignments
        echo date('H:i:s') , " Set alignments" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('A3:A6')->getAlignment()->setWrapText(true);

        // Set column widths
        echo date('H:i:s') , " Set column widths" , EOL;
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(80);

        // Set fonts
        echo date('H:i:s') , " Set fonts" , EOL;
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('Candara');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setUnderline(\PHPExcel_Style_Font::UNDERLINE_SINGLE);

        $objPHPExcel->getActiveSheet()->getStyle('A3:A6')->getFont()->setSize(8);

        // Add a drawing to the worksheet
        echo date('H:i:s') , " Add a drawing to the worksheet" , EOL;
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Terms and conditions');
        $objDrawing->setDescription('Terms and conditions');
        // $objDrawing->setPath('./images/termsconditions.jpg');
        $objDrawing->setCoordinates('B14');
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

        // Set page orientation and size
        echo date('H:i:s') , " Set page orientation and size" , EOL;
        $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        // Rename second worksheet
        echo date('H:i:s') , " Rename second worksheet" , EOL;
        $objPHPExcel->getActiveSheet()->setTitle('Terms and conditions');


        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);  

        ob_end_clean();//清除缓冲区,避免乱码
        // Redirect output to a client’s web browser (Excel5)  
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-Disposition: attachment;filename='.'测试'.'('.date('Y-m-d').').xls');  
        header('Cache-Control: max-age=0');  
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); 
        $objWriter->save('php://output'); 
    }




}