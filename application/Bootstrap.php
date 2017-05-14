<?php

class Bootstrap extends Yaf\Bootstrap_Abstract {
	protected $config;
    protected $site_type;

    public function _init(Yaf\Dispatcher $dispatcher){
        Yaf\Loader::import(APPLICATION_PATH . '/conf/defines.inc.php');
        Yaf\Loader::import(APPLICATION_PATH . '/application/functions.php');
        if(php_sapi_name() != 'cli'){
            if($dispatcher->getRequest()->getServer('HTTPS') == 'on'){
                $protocol = 'https://';
            }else{
                $protocol = 'http://';
            }
            switch ($protocol.$dispatcher->getRequest()->getServer('HTTP_HOST')){
                case WEALTH_DOMAIN:default:
                    $site_type_id = 1;
                    $this->site_type = 'wealth';
                    break;
            }
            Yaf\Registry::set('global_var', array(
                'site_type' => array('id'=>$site_type_id,'name'=>$this->site_type),
            ));
        }else{
            $this->site_type = 'script';
        }
    }

	public function _initConfig(Yaf\Dispatcher $dispatcher) {
        $this->config = Yaf\Application::app()->getConfig();
        Yaf\Registry::set('config', $this->config);
        //判断请求方式，命令行请求应跳过一些HTTP请求使用的初始化操作，如模板引擎初始化
//        define('REQUEST_METHOD', strtoupper($dispatcher->getRequest()->getMethod()));
        Yaf\Loader::import(APPLICATION_PATH . '/conf/error_code.php');

        switch ($this->site_type){
            case 'wealth':
            case 'script':
                Yaf\Loader::import(APPLICATION_PATH . '/conf/site_wealth.inc.php');
                break;
        }
	}

	public function _initError(Yaf\Dispatcher $dispatcher) {
        if ($this->config->application->debug){
            define('DEBUG_MODE', true);
            ini_set('display_errors', 'On');
        }else{
            define('DEBUG_MODE', false);
            ini_set('display_errors', 'Off');
        }
	}

	public function _initPlugin(Yaf\Dispatcher $dispatcher) {
        if ($this->site_type == 'script'){
            $benchmark = new ScriptBenchPlugin();
            $dispatcher->registerPlugin($benchmark);
        } elseif (isset($this->config->application->benchmark) && $this->config->application->benchmark == true){
            $benchmark = new BenchmarkPlugin();
            $dispatcher->registerPlugin($benchmark);
        }
	}

	public function _initRoute(Yaf\Dispatcher $dispatcher) {
        $router = $dispatcher->getRouter();
        switch ($this->site_type){
            case 'wealth':
                // web端
                $router->addRoute('common_route', new Yaf\Route\Regex(
                    '#.*#i',
                    array(
                        'module' => 'wealth',
                        'controller' => 'index',
                        'action' => 'index',
                    ),
                    array()
                ));
                $router->addRoute('controller_route', new Yaf\Route\Rewrite(
                    '/wealth/:c',
                    array(
                        'module' => 'wealth',
                        'controller' => ':c',
                        'action' => 'index',
                    )
                ));
                $router->addRoute('action_route', new Yaf\Route\Rewrite(
                    '/wealth/:c/:a',
                    array(
                        'module' => 'wealth',
                        'controller' => ':c',
                        'action' => ':a',
                    )
                ));
                $router->addRoute('page_route', new Yaf\Route\Rewrite(
                    '/page/:a',
                    array(
                        'module' => 'wealth',
                        'controller' => 'page',
                        'action' => ':a',
                    )
                ));

                // 微信端
                $router->addRoute('wx_common_route', new Yaf\Route\Regex(
                    '#^/wx.*$#i',
                    array(
                        'module' => 'wealthwx',
                        'controller' => 'index',
                        'action' => 'index',
                    ),
                    array()
                ));
                $router->addRoute('wx_controller_route', new Yaf\Route\Rewrite(
                    '/wealthwx/:c',
                    array(
                        'module' => 'wealthwx',
                        'controller' => ':c',
                        'action' => 'index',
                    )
                ));
                $router->addRoute('wx_action_route', new Yaf\Route\Rewrite(
                    '/wealthwx/:c/:a',
                    array(
                        'module' => 'wealthwx',
                        'controller' => ':c',
                        'action' => ':a',
                    )
                ));
                $router->addRoute('wx_page_route', new Yaf\Route\Rewrite(
                    '/wx/page/:a',
                    array(
                        'module' => 'wealthwx',
                        'controller' => 'page',
                        'action' => ':a',
                    )
                ));
                break;
            case 'script':
                break;
        }
	}

	public function _initDatabase() {
        $servers = array();
        $database = $this->config->database;
        $servers[] = $database->master->toArray();
        $slaves = $database->slaves;
        if (!empty($slaves)){
            $slave_servers = explode('|', $slaves->servers);
            $slave_users = explode('|', $slaves->users);
            $slave_passwords = explode('|', $slaves->passwords);
            $slave_databases = explode('|', $slaves->databases);
            $slaves = array();
            foreach ($slave_servers as $key => $slave_server){
                if (isset($slave_users[$key]) && isset($slave_passwords[$key]) && isset($slave_databases[$key])){
                    $slaves[] = array('server' => $slave_server, 'user' => $slave_users[$key], 'password' => $slave_passwords[$key], 'database' => $slave_databases[$key]);
                }
            }
            $servers[] = $slaves[array_rand($slaves)];
        }
        Yaf\Registry::set('database', $servers);
        if (isset($database->mysql_cache_enable) && $database->mysql_cache_enable && !defined('MYSQL_CACHE_ENABLE')){
            define('MYSQL_CACHE_ENABLE', true);
        }
        if (isset($database->mysql_log_error) && $database->mysql_log_error && !defined('MYSQL_LOG_ERROR')){
            define('MYSQL_LOG_ERROR', true);
        }
        Yaf\Loader::import('Db/Db.php');
	}

}
