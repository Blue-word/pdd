<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Model;
use think\db\Query;
require_once dirname(APP_PATH) . '/vendor/phpexcel/PHPExcel/IOFactory.php';
class Excel extends PHPExcel_IOFactory{


    /**
     * 导出
     */
    public function export(){
        // Vendor('phpexcel.PHPExcel');
        // Vendor('phpexcel.PHPExcel.IOFactory');
        // Vendor('phpexcel.PHPExcel.Reader.Excel5');
    	// $field = 'id,name,id_number,phone,recommend_number,class,sex,other_content';
     //    $user = M('new_survey_copy')->field($field)->select();
     //    // dump($data);
     //    $subject = "Excel导出测试";
     //    $title = array("id","姓名","身份证","手机号","推荐工号","班次","性别","其他内容");
     //    $asd = $this->exportExcel($user,$title,$subject); 
        // dump(dirname(APP_PATH) . '/vendor/phpexcel/PHPExcel/IOFactory.php');
    }
    



}