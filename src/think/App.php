<?php
namespace think;

class App {

    /**
     * 执行应用程序
     * @access public
     * @return void
     */
    public static function run() {
        // 注册错误和异常处理机制
        register_shutdown_function('\think\Error::appShutdown');
        set_error_handler('\think\Error::appError');
        set_exception_handler('\think\Error::appException');
        // 初始化应用（公共模块）
        self::initModule(COMMON_MODULE);
        $config = Config::get();
        if (!empty($config['default_timezone'])) {
            // 设置系统时区
            date_default_timezone_set($config['default_timezone']);
        }
        // 启动session CLI 不开启
        if (!IS_CLI) {
            Session::init($config['session']);
        }

        $result = Route::run($config);
        // 模块/控制器/方法
        $data = self::module($result, $config);
        // 输出数据到客户端
        return Response::create($data, $type)->send();
    }

    // 执行函数或者闭包方法 支持参数调用
    public static function invokeFunction($function, $args = []) {
        $reflect = new \ReflectionFunction($function);
        // 记录执行信息
        APP_DEBUG && Log::record('[ RUN ] ' . $reflect->getFileName() . '[ ' . var_export($args, true) . ' ]', 'info');
        return $reflect->invokeArgs($args);
    }

    // 调用反射执行类的方法 支持参数绑定
    public static function invokeMethod($method, $args = []) {
        if (empty($args)) {
            $args = [];
        }
        if (is_array($method)) {
            $class   = is_object($method[0]) ? $method[0] : new $method[0];
            $reflect = new \ReflectionMethod($class, $method[1]);
        } else {
            // 静态方法
            $reflect = new \ReflectionMethod($method);
        }
        // 记录执行信息
        APP_DEBUG && Log::record('[ RUN ] ' . $reflect->getFileName() . '[ ' . var_export($args, true) . ' ]', 'info');
        return $reflect->invokeArgs(isset($class) ? $class : null, $args);
    }

    // 执行 模块/控制器/操作
    public static function module($result = [], $config) {
        // 获取模块名称
        $moduleName = $result['module'] ?: "\\" . $config['module']['default_module'];
        // 获取控制器名
        $controllerName = $result['controller'] ?: $config['module']['default_controller'];
        // 获取操作名
        $actionName = $result['action'] ?: $config['module']['default_action'];
        define('MODULE_NAME', $moduleName);
        define('CONTROLLER_NAME', $controllerName);
        define('ACTION_NAME', $actionName);
        // 模块初始化
        self::initModule(MODULE_NAME, $config);
        try {
            $class    = MODULE_NAME . "\\" . CONTROLLER_LAYER . "\\" . CONTROLLER_NAME;
            $instance = new $class;
            // 操作方法开始监听
            return $data = self::invokeMethod([$instance, ACTION_NAME], $result['args']);
        } catch (\ReflectionException $e) {
            throw new \Exception($e);
        }
    }

    public static function initModule($module, $project = APP_PATH) {
        // 加载初始化文件
        $baseConfigPath = $project . $module . '/' . CONFIG_LAYER . '/';
        $configFile     = array_splice(scandir($baseConfigPath), 2);
        // 读取扩展配置文件
        if (count($configFile) > 0) {
            foreach ($configFile as $name => $file) {
                $filename = $baseConfigPath . $file;
                \think\Config::load($filename);
            }
        }
        // 加载全局函数
        $baseHelperPath = $project . $module . HELPER_LAYER . '/';
        $helperFile     = array_splice(scandir($baseHelper), 2);
        if (count($helperFile) > 0) {
            foreach ($helperFile as $name => $file) {
                $filename = $baseHelperPath . $file;
                include $filename;
            }
        }
    }
}
