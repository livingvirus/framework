<?php
/**
 * Input::$data['_URL']   ---> 返回 要调用的类 方法  以及相对应的参数
$_URL 变量 映射 classname
规则 就是$_URL 是否符合 如何符合就替换

$data = ['class'=>'','method','vars'=>[]]
 **/
namespace think;

class Route
{
    // URL映射规则
    private static $map = [];
    // 子域名部署规则
    private static $domain = [];
    // 子域名
    private static $subDomain = '';
    // 变量规则
    private static $pattern = [];
    // 域名绑定
    private static $bind    = [];
    private static $rules   = [];
    private static $trueUrl = '';

    public static function run($config = [])
    {
        self::register();
        self::match($config);
        $url = explode('/', $trueUrl);

        $moudel='\app\index';
        $controller  =$moudel'\controller\Index';
        $class = $moudel. $controller;
        
        self::exec($class, $method, $vars);
    }

    // 注册路由规则
    public static function match($rule = [])
    {
        //$map=[$src=>$dest]
        // if (in_array(Input::$data['_URL'], self::$map)) {
        //     self::$trueUrl = self::$map;
        //     return;
        // }
        foreach ($rule as $key => $value) {
            //$value[0], $value[1]  $pattern,$class Input::$data['_URL']
            if (preg_match($value[0], Input::$data['_URL'])) {
                self::$trueUrl = $value[1];
                break;
            }
        }

    }

    private static function exec($class = '', $method = '_empty', $var = [], $config)
    {
        if (class_exists($class)) {
            $instance = new $class;
        } else {
            throw new Exception('class not exist :' . $class, 10007);
        }

        // 操作方法开始监听
        $call = [$instance, $method];
        APP_HOOK && Hook::listen('action_begin', $call);

        if (method_exists($instance, $method)) {
            $method = new \ReflectionMethod($instance, $method);
            $data   = $method->invokeArgs($instance, $var);
            APP_DEBUG && Log::record('[ RUN ] ' . $method->getFileName(), 'info');
        } else {
            throw new Exception('method [ ' . (new \ReflectionClass($class))->getName() . '->' . $method . ' ] not exists ', 10002);
        }
        return $data;
    }
}
