<?php
namespace app\api\controller;
$num = 5;
function fn(){
	static $num = 0;
	return $num++;
}

echo $num;
echo $num++;
echo fn();
echo fn();



?>