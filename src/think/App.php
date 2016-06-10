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
        // 模块/控制器/方法
        $data = self::module($config);
        // 输出数据到客户端
        return Response::create($data, $type)->send();
    }

    // 执行函数或者闭包方法 支持参数调用
    public static function invokeFunction($function, $vars = []) {
        $reflect = new \ReflectionFunction($function);
        $args = self::bindParams($reflect, $vars);
        // 记录执行信息
        APP_DEBUG && Log::record('[ RUN ] ' . $reflect->getFileName() . '[ ' . var_export($vars, true) . ' ]', 'info');
        return $reflect->invokeArgs($args);
    }

    // 调用反射执行类的方法 支持参数绑定
    public static function invokeMethod($method, $vars = []) {
        if (empty($vars)) {
            // 自动获取请求变量
            $vars = Request::instance()->param();
        }
        if (is_array($method)) {
            $class = is_object($method[0]) ? $method[0] : new $method[0];
            $reflect = new \ReflectionMethod($class, $method[1]);
        } else {
            // 静态方法
            $reflect = new \ReflectionMethod($method);
        }
        $args = self::bindParams($reflect, $vars);
        // 记录执行信息
        APP_DEBUG && Log::record('[ RUN ] ' . $reflect->getFileName() . '[ ' . var_export($args, true) . ' ]', 'info');
        return $reflect->invokeArgs(isset($class) ? $class : null, $args);
    }

    // 绑定参数
    private static function bindParams($reflect, $vars) {
        $args = [];
        // 判断数组类型 数字数组时按顺序绑定参数
        $type = key($vars) === 0 ? 1 : 0;
        if ($reflect->getNumberOfParameters() > 0) {
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $name = $param->getName();
                if (1 == $type && !empty($vars)) {
                    $args[] = array_shift($vars);
                } elseif (0 == $type && isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new Exception('method param miss:' . $name, 10004);
                }
            }
            // 全局过滤
            array_walk_recursive($args, 'think\\Input::filterExp');
        }
        return $args;
    }

    // 执行 模块/控制器/操作
    public static function module($config) {
        $Request = \think\Request::instance(); //初始化
        $tmpUrl = explode("/", $Request->url);
        $tmpNum = count($tmpUrl);
        $result['controller'] = $tmpUrl[$tmpNum - 2];
        $result['action'] = $tmpUrl[$tmpNum - 1];
        unset($tmpUrl[$tmpNum - 2]);
        unset($tmpUrl[$tmpNum - 1]);
        $result['module'] = implode('\\', $tmpUrl);
        $args = $Request->param; //参数
        // 获取模块名称
        $module = $result['module'] ? : "\\" . $config['module']['default_module'];
        // 获取控制器名
        $controllerName = $result['controller'] ? : $config['module']['default_controller'];
        // 获取操作名
        $actionName = $result['action'] ? : $config['module']['default_action'];
        define('MODULE_NAME', $module);
        define('CONTROLLER_NAME', $controllerName);
        define('ACTION_NAME', $actionName);
        // 模块初始化
        self::initModule(MODULE_NAME, $config);
        try {
            $class = MODULE_NAME . "\\" . CONTROLLER_LAYER . "\\" . CONTROLLER_NAME;
            new \ReflectionClass($class);
            $instance = new $class;
            // 操作方法开始监听
            $reflect = new \ReflectionMethod($instance, ACTION_NAME);
            $data = $reflect->invokeArgs($instance, $args);
        } catch (\ReflectionException $e) {
            if (APP_DEBUG) {
                throw new \Exception($e);
            } else {
                echo "404" . $e->message;
            }
        }
        return $data;
    }

    public static function initModule($module, $project = APP_PATH) {
        // 加载初始化文件
        $baseConfigPath = $project . $module . '/' . CONFIG_LAYER . '/';
        $configFile = array_splice(scandir($baseConfigPath), 2);
        // 读取扩展配置文件
        if (count($configFile) > 0) {
            foreach ($configFile as $name => $file) {
                $filename = $baseConfigPath . $file;
                \think\Config::load($filename);
            }
        }
        // 加载全局函数
        $baseHelperPath = $project . $module . HELPER_LAYER . '/';
        $helperFile = array_splice(scandir($baseHelper), 2);
        if (count($helperFile) > 0) {
            foreach ($helperFile as $name => $file) {
                $filename = $baseHelperPath . $file;
                include $filename;
            }
        }
    }

}
