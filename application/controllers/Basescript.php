<?php

/**
 * 脚本Controller
 * 使用方法: php {APPLICATION_PATH}/indexc.php request_uri="/script/:c/:a"
 */
class BasescriptController extends Yaf\Controller_Abstract {
    protected $_config;

    public function init() {
        if(php_sapi_name() != 'cli'){
            exit;
        }
        $this->_config = Yaf\Registry::get('config');
        Yaf\Dispatcher::getInstance()->disableView();
    }

}
