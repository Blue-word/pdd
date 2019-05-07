<?php
namespace app\api\controller;
use think\Controller;

class Test extends Controller{

	// function maopao($arr){
	// 	$count = count($arr);
	// 	for ($i=0; $i <$count-1 ; $i++) { 
	// 		for ($j=0; $j <$count-$i-1 ; $j++) { 
	// 			if ($arr[$i] > $arr[$j+1]) {
	// 				$temp = $arr[$i];
	// 				$arr[$j] = $arr[$j+1];
	// 				$arr[$j+1] = $temp;
	// 			}
	// 		}
	// 	}
	// 	return $arr;
	// }

	function getpao($arr)
{  
  $len=count($arr);
  //设置一个空数组 用来接收冒出来的泡
  //该层循环控制 需要冒泡的轮数
  for($i=1;$i<$len;$i++)
  { //该层循环用来控制每轮 冒出一个数 需要比较的次数
    for($k=0;$k<$len-$i;$k++)
    {
       if($arr[$k]>$arr[$k+1])
        {
            $tmp=$arr[$k+1];
            $arr[$k+1]=$arr[$k];
            $arr[$k]=$tmp;
        }
    }
  }
  return $arr;
} 

	function test1(){
		$asd = new Test;
		$arr =array(1,43,54,62,21,66,32,78,36,76,39);
		$zxc = $asd->getpao($arr);
		dump($zxc);
		
	}

}