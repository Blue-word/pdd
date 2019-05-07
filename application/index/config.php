<?php
//配置文件
return [
	'template'               => [
            // 模板引擎类型 支持 php think 支持扩展
            'type'         => 'Think',
            // 模板路径
            'view_path'    => './application/index/view/',
            // 模板后缀
            'view_suffix'  => 'html',
            // 模板文件名分隔符
            'view_depr'    => DS,
            // 模板引擎普通标签开始标记
            'tpl_begin'    => '{',
            // 模板引擎普通标签结束标记
            'tpl_end'      => '}',
            // 标签库标签开始标记
            'taglib_begin' => '<',
            // 标签库标签结束标记
            'taglib_end'   => '>',
        ],

      //默认错误跳转对应的模板文件
      'dispatch_error_tmpl' => 'index:dispatch_jump',
      //默认成功跳转对应的模板文件
      'dispatch_success_tmpl' => 'index:dispatch_jump',
      //图片转化路径
      'image' => '/blue/public',

];