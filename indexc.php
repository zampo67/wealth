<?php
/**
 * 命令行请求入口
 */
define('APPLICATION_PATH', dirname(__FILE__));

$application = new Yaf\Application(APPLICATION_PATH . "/conf/application.ini");
$application->bootstrap()->getDispatcher()->dispatch(new Yaf\Request\Simple());
