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

class Request {

    /**
     * @var object 对象实例
     */
    protected static $instance;

    /**
     * @var string 协议
     */
    public $protocol;

    /**
     * @var string 请求方法
    'GET'    => 'get',
    'POST'   => 'post',
    'PUT'    => 'put',
    'DELETE' => 'delete',
     */
    public $method;

    /**
     * @var string 域名
     */
    public $domain;

    /**
     * @var string 端口
     */
    public $port;

    /**
     * @var string URL地址
     */
    public $baseUrl;

    /**
     * @var string pathinfo
     */
    public $pathInfo;

    /**
     * @var string pathinfo（不含后缀）
     */
    public $pathinfo;

    /**
     * @var string 当前执行的文件
     */
    public $baseFile;

    /**
     * @var string 访问的ROOT地址
     */
    public $root;

    /**
     * @var array 后缀信息
     */
    public $ext;

    /**
     * @var array 请求参数
     */
    public $param;
    public $session = [];
    public $file    = [];
    public $cookie  = [];
    public $server  = [];

    /**
     * @var array 资源类型
     */
    public $mimeType = [
        'html' => 'text/html,application/xhtml+xml,*/*',
        'xml'  => 'application/xml,text/xml,application/x-xml',
        'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'   => 'text/javascript,application/javascript,application/x-javascript',
        'css'  => 'text/css',
        'rss'  => 'application/rss+xml',
        'yaml' => 'application/x-yaml,text/yaml',
        'atom' => 'application/atom+xml',
        'pdf'  => 'application/pdf',
        'text' => 'text/plain',
        'png'  => 'image/png',
        'jpg'  => 'image/jpg,image/jpeg,image/pjpeg',
        'gif'  => 'image/gif',
        'csv'  => 'text/csv',
    ];

    /**
     * 架构函数
     * @access public
     * @param array $options 参数
     */
    public function __construct() {
        if (isset($_SERVER['HTTPS'])) {
            //不考虑IIS
            $this->protocol = 'https';
        } else {
            $this->protocol = 'http';
        }
        $this->domain   = $_SERVER['HTTP_HOST'];
        $this->port     = $_SERVER['SERVER_PORT'];
        $this->baseUrl  = $_SERVER['REQUEST_URI'];
        $this->pathInfo = $_SERVER['PATH_INFO'];
        $this->ext      = pathinfo($this->pathInfo, PATHINFO_EXTENSION); //后缀
        $this->url      = str_replace("." . $this->ext, '', str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));
        $this->method   = $_SERVER['REQUEST_METHOD'];
        $this->param    = $_REQUEST;
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return \think\Request
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    /**
     * 获取当前请求的时间
     * @access public
     * @param bool $float 是否使用浮点类型
     * @return integer|float
     */
    public function time($float = false) {
        return $float ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
    }
}
