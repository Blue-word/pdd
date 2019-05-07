<?php 
namespace app\admin\controller;
header("Content-type: text/html; charset=utf-8");
$con = mysqli_connect('localhost','root','root','llb');
if (!$con){
    die("连接错误: " . mysqli_connect_error());
} 
// print_r($con);
$sql = "select * from user";
$result = mysqli_query($con,$sql);
// print_r($result);
dump($result);



mysqli_close($con);


 ?>