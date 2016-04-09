<?php
namespace think;

class Input
{
    // 全局过滤规则
    public static $filters = [];
    //就使用全局$GLOBALS
    public static $data = [];
    public $method      = ['get', 'post', 'put', 'param', 'request', 'session', 'cookie', 'server', 'url', 'env', 'file'];

    public static function init()
    {
        parse_str(file_get_contents('php://input'), $GLOBALS['_PUT']);
        $GLOBALS['_URL'] = [];
        foreach (explode('/', $_SERVER['PATH_INFO']) as $key => $value) {
            if (!empty($value)) {
                $GLOBALS['_URL'][$key] = $value;
            }
        }
        $GLOBALS['_SESSION'] = $_SESSION;
        self::$data          = $GLOBALS;
    }

    public static function get($method = '', $name = '', $default = null, $filter = null, $merge = false)
    {
        if (!empty($method)) {
            return self::$data["_" . strtoupper($method)][$name];
        }
        //自动判断
        foreach ($this->method as $key => $value) {
            if (isset(self::$data["_" . strtoupper($value)][$name])) {
                return self::$data["_" . strtoupper($value)][$name];
            }
        }
    }
}
