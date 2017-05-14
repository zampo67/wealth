<?php

class IPHPResque{
    protected static $instance='';

    private function __construct($config){
        Yaf\Loader::import('PHPResque/vendor/autoload.php');
        if (!class_exists('Composer\Autoload\ClassLoader', false)) {
            die(
                'You need to set up the project dependencies using the following commands:' . PHP_EOL .
                'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
                'php composer.phar install' . PHP_EOL
            );
        }
        Resque::setBackend($config['host'].':'.$config['port']);
        defined('MQ_QUEUE') or define('MQ_QUEUE', 'default');
    }

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            $config = Yaf\Registry::get('config');
            self::$instance = new self($config->queue);
        }
        return self::$instance;
    }

    /**
     * 生成队列
     * @param string $class 消费队列的job类名
     * @param array|null $args 参数
     * @param bool $trackStatus 是否跟踪状态
     * @return bool|string
     */
    public function producer($class, $args = null, $trackStatus = false){
        return Resque::enqueue(MQ_QUEUE, $class, $args, $trackStatus);
    }

}