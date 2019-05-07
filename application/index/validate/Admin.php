<?php 
namespace app\index\validate;

use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        '__token__'  =>  'require|max:32|token',
        'name'  =>  'require',
        'password'  =>  'require|max:10',
    ];

}





 ?>