<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 加载惯例配置文件 // 加载环境变量配置文件
\think\Config::load(CONF_PATH);
// 注册错误和异常处理机制
\think\Error::register();
// 执行应用
new Model('asdfasdfsadf');
//\think\App::run()->send();
