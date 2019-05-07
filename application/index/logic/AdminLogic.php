<?php 
namespace app\index\logic;

use think\Controller;
use think\Db;
use think\Model;

/**
 * admin逻辑层
 */
class AdminLogic extends Model
{
	public function login($params=array()){
		$where['name'] = $params['name'];
        $where['password'] = encrypt($params['password']);
        $res = Db::table('admin')->alias('a')->join('admin_role b','a.role_id = b.role_id','INNER')->where($condition)->find();
        if ($res) {
        	$return = array('code'=>0,'info'=>$res);
        }else{
        	$return = array('code'=>0,'msg'=>'用户名或密码不正确');
        }
        return $return;
	}
}



 ?>