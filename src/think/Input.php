<?php
namespace think;

class Input
{
    // 全局过滤规则
    public static $filters = [];
    //就使用全局$GLOBALS
    public static $data = [];
    public $method      = ['get', 'post', 'put', 'param', 'request', 'session', 'cookie', 'server', 'url', 'env', 'file'];

    public function __construct()
    {
        self::$data             = &$GLOBALS;
        self::$data['_SESSION'] = &$_SESSION;
        parse_str(file_get_contents('php://input'), self::$data['_PUT']);
        self::$data['_URL'] = explode('/', $_SERVER['PATH_INFO']);

    }

    public static function init($method)
    {

    }
}
