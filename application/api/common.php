<?php
use think\Request;
/*
图片以逗号拆分成数组
 */
function pic_change($data){
	return $data;
	if ($data) {
		return 1;
		foreach ($data as $k => $v) {
			$res = explode(',',$v['picture']);
			return $res;
		}
	}
}
/*
过滤成纯文本用于显示
 */
function clear_all($area_str){ 
    if ($area_str!=''){
        $area_str = trim($area_str); //清除字符串两边的空格
        $area_str = strip_tags($area_str,""); //利用php自带的函数清除html格式
        $area_str = str_replace("&nbsp;","",$area_str);
         
        $area_str = preg_replace("/   /","",$area_str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
        $area_str = preg_replace("/
/","",$area_str); 
        $area_str = preg_replace("/
/","",$area_str); 
        $area_str = preg_replace("/
/","",$area_str); 
        $area_str = preg_replace("/ /","",$area_str);
        $area_str = preg_replace("/  /","",$area_str);  //匹配html中的空格
        $area_str = trim($area_str); //返回字符串
    }
    return $area_str;
}  
/**
 *图片为多张时空与不为空的输出
 */
function img_nullchange($img){
    if (!empty($img)) { //picture不为空则处理
        $pic_arr = explode(',',$img);
        // return $img;
        foreach ($pic_arr as $k => $v) {
            $path_arr[] = img_url_transform($v,'absolute');
            $picture = $path_arr;
        }
        return $picture;
    }else{
        $picture = null;//为空处理成空数组
        return $picture;
    }
}
/*
 *content: 根据数组某个字段进行排序
 * $arr    需要排序的数组
 * $field  数组里的某个字段
 * sort    1为正序排序  2为倒序排序
 * time :  2016年12月21日19:02:33
 */
function f_order($arr,$field,$sort){
    $order = array();
    foreach($arr as $kay => $value){
        $order[] = $value[$field];
    }
    if($sort==1){
        array_multisort($order,SORT_ASC,$arr);
    }else{
        array_multisort($order,SORT_DESC,$arr);
    }
    return $arr;
}
/*
 *判断字符串是否在二维数组里
 */
function array_multi_search($p_needle, $p_haystack) {
  if(!is_array($p_haystack)) return false;
  if(in_array($p_needle, $p_haystack)) { 
          return true;
  } 
  foreach($p_haystack as $row) { 
    if(array_multi_search($p_needle, $row)) { 
       return true; 
    } 
  } 
  return false; 
}




