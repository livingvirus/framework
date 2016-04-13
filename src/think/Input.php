<?php
namespace think;

class Input
{
    // 全局过滤规则
    public static $filters = [];
    //就使用全局$GLOBALS
    public static $data = []; //只读
    

    public static function init($method)
    {
        self::$data             = &$GLOBALS;
        self::$data['_SESSION'] = &$_SESSION;
        parse_str(file_get_contents('php://input'), self::$data['_PUT']);
        self::$data['_URL'] = explode('/', $_SERVER['PATH_INFO']);
    }

    // 静态调用  Input::session('')
    public static function __callStatic($method, $name, $value = '')
    {
        $methodObject = new '\\think\\input\\driver\\'.ucfirst($method);
        if (is_array($name)) {
            // 初始化
            $methodObject->init($name);
        } elseif (is_null($name)) {
            // 清除
            $methodObject->clear($value);
        } elseif ('' === $value) {
            // 获取
            return $methodObject->get($name);
        } elseif (is_null($value)) {
            // 删除
            return $methodObject->delete($name);
        } else {
            // 设置
            return $methodObject->($name, $value);
        }
    }
}
