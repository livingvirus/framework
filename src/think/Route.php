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

namespace think;

//路由规则  map  match  *匹配规则pattern
class Route {
    // 路由规则
    private static $rules = [
        'GET'     => [],
        'POST'    => [],
        'PUT'     => [],
        'DELETE'  => [],
        'HEAD'    => [],
        'OPTIONS' => [],
        '*'       => [],
    ];

    // REST路由操作方法定义
    private static $rest = [
        'index'  => ['GET', '', 'index'],
        'create' => ['GET', '/create', 'create'],
        'read'   => ['GET', '/:id', 'read'],
        'edit'   => ['GET', '/:id/edit', 'edit'],
        'save'   => ['POST', '', 'save'],
        'update' => ['PUT', '/:id', 'update'],
        'delete' => ['DELETE', '/:id', 'delete'],
    ];

    // 不同请求类型的方法前缀
    private static $methodPrefix = [
        'GET'    => 'get',
        'POST'   => 'post',
        'PUT'    => 'put',
        'DELETE' => 'delete',
    ];

    // URL映射规则
    private static $map = [];
    // 子域名部署规则
    private static $domain = [];
    // 子域名
    private static $subDomain = '';
    // 变量规则
    private static $pattern = [];
    // 域名绑定
    private static $bind = [];
    // 当前分组
    private static $group;
    private static $option = [];

    private static $result;

    private static function parseUrl() {
        $Request              = Request::instance(); //初始化
        $tmpUrl               = explode("/", $Request->url);
        $tmpNum               = count($tmpUrl);
        $result['method']     = $Request->method; //请求方法
        $result['protocol']   = $Request->protocol;
        $result['domain']     = $Request->domain;
        $result['port']       = $Request->port;
        $result['controller'] = $tmpUrl[$tmpNum - 2];
        $result['action']     = $tmpUrl[$tmpNum - 1];
        $result['args']       = $Request->param; //参数
        unset($tmpUrl[$tmpNum - 2]);
        unset($tmpUrl[$tmpNum - 1]);
        $result['module'] = implode('\\', $tmpUrl);
        var_dump($result);die;
        self::$result = $result;
    }

    // 添加URL映射规则
    public static function run($config) {
        self::parseUrl();
        return $result;
    }
    // 解析规则路由
    // '路由规则'=>'[控制器/操作]?额外参数1=值1&额外参数2=值2...'
    // '路由规则'=>array('[控制器/操作]','额外参数1=值1&额外参数2=值2...')
    // '路由规则'=>'外部地址'
    // '路由规则'=>array('外部地址','重定向代码')
    // 路由规则中 :开头 表示动态变量
    // 外部地址中可以用动态变量 采用 :1 :2 的方式
    // 'news/:month/:day/:id'=>array('News/read?cate=1','status=1'),
    // 'new/:id'=>array('/new.php?id=:1',301), 重定向
    private static function parseRule($rule, $route, $regx) {
    }
    // 解析正则路由
    // '路由正则'=>'[控制器/操作]?参数1=值1&参数2=值2...'
    // '路由正则'=>array('[控制器/操作]?参数1=值1&参数2=值2...','额外参数1=值1&额外参数2=值2...')
    // '路由正则'=>'外部地址'
    // '路由正则'=>array('外部地址','重定向代码')
    // 参数值和外部地址中可以用动态变量 采用 :1 :2 的方式
    // '/new\/(\d+)\/(\d+)/'=>array('News/read?id=:1&page=:2&cate=1','status=1'),
    // '/new\/(\d+)/'=>array('/new.php?id=:1&page=:2&status=1','301'), 重定向
    private static function parseRegex($config) {
        return $result;
    }

    // 添加URL映射规则
    public static function map($map = '', $route = '') {
        return self::setting('map', $map, $route);
    }

    // 添加变量规则
    public static function pattern($name = '', $rule = '') {
        return self::setting('pattern', $name, $rule);
    }

    // 添加子域名部署规则
    public static function domain($domain = '', $rule = '') {
        return self::setting('domain', $domain, $rule);
    }

    // 属性设置
    private static function setting($var, $name = '', $value = '') {
        if (is_array($name)) {
            self::${$var} = self::${$var}+$name;
        } elseif (empty($value)) {
            return empty($name) ? self::${$var} : self::${$var}[$name];
        } else {
            self::${$var}[$name] = $value;
        }
    }

    // 对路由进行绑定和获取绑定信息
    public static function bind($type, $bind = '') {
        if ('' == $bind) {
            return isset(self::$bind[$type]) ? self::$bind[$type] : null;
        } else {
            self::$bind = ['type' => $type, $type => $bind];
        }
    }

    // 设置当前的路由分组
    public static function setGroup($name) {
        self::$group = $name;
    }

    // 设置当前的路由分组
    public static function setOption($option) {
        self::$option = $option;
    }

    // 路由分组
    public static function group($name, $routes, $option = [], $type = '*', $pattern = []) {
        if (is_array($name)) {
            $option = $name;
            $name   = isset($option['name']) ? $option['name'] : '';
        }
        $type = strtoupper($type);
        if (!empty($name)) {
            if ($routes instanceof \Closure) {
                self::setGroup($name);
                call_user_func_array($routes, []);
                self::setGroup(null);
                self::$rules[$type][$name]['option']  = $option;
                self::$rules[$type][$name]['pattern'] = $pattern;
            } else {
                self::$rules[$type][$name] = ['routes' => $routes, 'option' => $option, 'pattern' => $pattern];
            }
        } else {
            if ($routes instanceof \Closure) {
                // 闭包注册
                self::setOption($option);
                call_user_func_array($routes, []);
                self::setOption([]);
            } else {
                // 批量注册路由
                self::rule($routes, '', $type, $option, $pattern);
            }
        }
    }

    // 注册任意请求的路由规则
    public static function any($rule, $route = '', $option = [], $pattern = [], $group = '') {
        self::rule($rule, $route, '*', $option, $pattern, $group);
    }

    // 注册get请求的路由规则
    public static function get($rule, $route = '', $option = [], $pattern = [], $group = '') {
        self::rule($rule, $route, 'GET', $option, $pattern, $group);
    }

    // 注册post请求的路由规则
    public static function post($rule, $route = '', $option = [], $pattern = [], $group = '') {
        self::rule($rule, $route, 'POST', $option, $pattern, $group);
    }

    // 注册put请求的路由规则
    public static function put($rule, $route = '', $option = [], $pattern = [], $group = '') {
        self::rule($rule, $route, 'PUT', $option, $pattern, $group);
    }

    // 注册delete请求的路由规则
    public static function delete($rule, $route = '', $option = [], $pattern = [], $group = '') {
        self::rule($rule, $route, 'DELETE', $option, $pattern, $group);
    }

    // 注册别名路由
    public static function alias($rule, $route = '', $option = [], $pattern = []) {
        foreach (self::$methodPrefix as $type => $val) {
            self::$type($rule . '/:action', $route . '/' . $val . ':action', $option, $pattern);
        }
    }
}
