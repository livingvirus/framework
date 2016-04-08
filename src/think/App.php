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
        // 注册错误和异常处理机制 以及初始化配置
        register_shutdown_function('\think\Error::appShutdown');
        set_error_handler('\think\Error::appError');
        set_exception_handler('\think\Error::appException');

        Config::load(THINK_PATH . 'config' . EXT);

        self::initModule();
        // 获取配置参数
        $config = Config::get();
        //输入参数处理
        new Input;

        // 设置系统时区
        date_default_timezone_set($config['default_timezone']);

        // 监听app_init
        APP_HOOK && Hook::listen('app_init');
        // 开启多语言机制
        // 启动session CLI 不开启
        if (!IS_CLI && $config['use_session']) {
            Session::init($config['session']);
        }
        // 监听app_begin
        APP_HOOK && Hook::listen('app_begin');

        Route::register();
        $result = Route::parseUrl(Input::$data['_URL']);
        $data   = self::module($result['module'], $config);
        // 监听app_end
        APP_HOOK && Hook::listen('app_end', $data);
        // 输出数据到客户端
        return Response::send($data, Response::type(), Config::get('response_return'));
    }

    // 执行函数或者闭包方法 支持参数调用
    private static function invokeFunction($function, $vars = [])
    {
        $reflect = new \ReflectionFunction($function);
        $args    = self::bindParams($reflect, $vars);
        // 记录执行信息
        APP_DEBUG && Log::record('[ RUN ] ' . $reflect->getFileName() . '[ ' . var_export($vars, true) . ' ]', 'info');
        return $reflect->invokeArgs($args);
    }

    // 调用反射执行类的方法 支持参数绑定
    private static function invokeMethod($method, $vars = [])
    {
        if (empty($vars)) {
            // 自动获取请求变量
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $vars = array_merge($_GET, $_POST);
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $vars);
                    break;
                default:
                    $vars = $_GET;
            }
        }
        if (is_array($method)) {
            $class   = is_object($method[0]) ? $method[0] : new $method[0];
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
    private static function bindParams($reflect, $vars)
    {
        $args = [];
        if ($reflect->getNumberOfParameters() > 0) {
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $name = $param->getName();
                if (isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new Exception('method param miss:' . $name, 10004);
                }
            }
            // 全局过滤
            //array_walk_recursive($args, 'think\\Input::filterExp');
        }
        return $args;
    }

    // 执行 模块/控制器/操作
    private static function module($result, $config)
    {
        $module = $result[0] ?: $config['default_module'];
        if ($maps = $config['url_module_map']) {
            if (isset($maps[$module])) {
                // 记录当前别名
                define('MODULE_ALIAS', $module);
                // 获取实际的项目名
                $module = $maps[MODULE_ALIAS];
            } elseif (array_search($module, $maps)) {
                // 禁止访问原始项目
                $module = '';
            }
        }
        // 获取模块名称
        define('MODULE_NAME', strip_tags($module));

        // 模块初始化
        if (MODULE_NAME && !in_array(MODULE_NAME, $config['deny_module_list']) && is_dir(APP_PATH . MODULE_NAME)) {
            APP_HOOK && Hook::listen('app_begin');
            define('MODULE_PATH', APP_PATH . MODULE_NAME . DS);
            define('VIEW_PATH', MODULE_PATH . VIEW_LAYER . DS);
            // 初始化模块
            self::initModule(MODULE_NAME, $config);
        } else {
            throw new Exception('module [ ' . MODULE_NAME . ' ] not exists ', 10005);
        }

        // 获取控制器名
        define('CONTROLLER_NAME', (strip_tags($result[1] ?: Config::get('default_controller'))));
        // 获取操作名
        define('ACTION_NAME', (strip_tags($result[2] ?: Config::get('default_action'))));

        if (Config::get('action_bind_class')) {
            $class    = self::bindActionClass(Config::get('empty_controller'));
            $instance = new $class;
            // 操作绑定到类后 固定执行run入口
            $action = 'run';
        } else {
            $class = '\\' . APP_NAMESPACE . '\\' . $module . '\\' . CONTROLLER_LAYER . '\\' . CONTROLLER_NAME;
            if (class_exists($class)) {
                $instance = new $class;
            } else {
                throw new Exception('class not exist :' . $class, 10007);
            }
            // 获取当前操作名
            $action = ACTION_NAME . Config::get('action_suffix');
        }

        try {
            // 操作方法开始监听
            $call = [$instance, $action];
            APP_HOOK && Hook::listen('action_begin', $call);
            if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
                // 非法操作
                throw new \ReflectionException();
            }
            // 执行操作方法
            $data = self::invokeMethod($call);
        } catch (\ReflectionException $e) {
            // 操作不存在
            if (method_exists($instance, '_empty')) {
                $method = new \ReflectionMethod($instance, '_empty');
                $data   = $method->invokeArgs($instance, [$action, '']);
                APP_DEBUG && Log::record('[ RUN ] ' . $method->getFileName(), 'info');
            } else {
                throw new Exception('method [ ' . (new \ReflectionClass($instance))->getName() . '->' . $action . ' ] not exists ', 10002);
            }
        }
        return $data;
    }

    // 操作绑定到类：模块\controller\控制器\操作类
    private static function bindActionClass($emptyController)
    {
        if (is_dir(MODULE_PATH . CONTROLLER_LAYER . DS . str_replace('.', DS, CONTROLLER_NAME))) {
            $namespace = MODULE_NAME . '\\' . CONTROLLER_LAYER . '\\' . str_replace('.', '\\', CONTROLLER_NAME) . '\\';
        } else {
            // 空控制器
            $namespace = MODULE_NAME . '\\' . CONTROLLER_LAYER . '\\' . $emptyController . '\\';
        }
        $actionName = ACTION_NAME;
        if (class_exists($namespace . $actionName)) {
            $class = $namespace . $actionName;
        } elseif (class_exists($namespace . '_empty')) {
            // 空操作
            $class = $namespace . '_empty';
        } else {
            throw new Exception('bind action class not exists :' . ACTION_NAME, 10003);
        }
        return $class;
    }

    // 初始化模块
    private static function initModule($module = '', $config)
    {
        // 定位模块目录
        $module = $module . DS;
        // 加载初始化文件
        if (is_file(APP_PATH . $module . 'init' . EXT)) {
            include APP_PATH . $module . 'init' . EXT;
        } else {
            $path = APP_PATH . $module;
            // 加载模块配置
            $config = Config::load(APP_PATH . $module . 'config' . EXT);

            // 读取扩展配置文件
            if ($config['extra_config_list']) {
                foreach ($config['extra_config_list'] as $name => $file) {
                    $filename = $path . $file . EXT;
                    Config::load($filename, is_string($name) ? $name : pathinfo($filename, PATHINFO_FILENAME));
                }
            }

            // 读取扩展配置文件
            if ($config['extra_file_list']) {
                foreach ($config['extra_file_list'] as $name => $file) {
                    include $filename = $path . $file . EXT;
                }
            }

            // 加载当前模块语言包
            if ($config['lang_switch_on'] && $module) {
                Lang::load($path . 'lang' . DS . LANG_SET . EXT);
            }
        }
    }
}
