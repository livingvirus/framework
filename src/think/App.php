<?php
namespace think;

class App
{
    /**
     * 执行应用程序
     * @access public
     * @return void
     */
    public static function run()
    {
        // 注册错误和异常处理机制
        register_shutdown_function('\think\Error::appShutdown');
        set_error_handler('\think\Error::appError');
        set_exception_handler('\think\Error::appException');
        // 加载配置文件
        Config::load();
        //输入参数处理
        Input::init();
        $config = Config::get();
        // 设置系统时区
        date_default_timezone_set($config['default_timezone']);
        // 监听app_init
        APP_HOOK && Hook::listen('app_init');

        // 启动session CLI 不开启
        if (!IS_CLI) {
            Session::init($config['session']);
        }
        // 监听app_begin
        APP_HOOK && Hook::listen('app_begin');
        $data = Route::run();
        // 监听app_end
        APP_HOOK && Hook::listen('app_end', $data);
        // 输出数据到客户端
        return Response::send($data, Response::type(), Config::get('response_return'));
    }

}
