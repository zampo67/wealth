<?php

/**
 * @name ErrorController
 * @desc   错误控制器, 在发生未捕获的异常时刻被调用
 * @see    http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 */
class ErrorController extends BaseresumeController {

    public function init(){
        $this->_check_token = 0;
        parent::init();
    }

	//从2.1开始, errorAction支持直接通过参数获取异常
	public function errorAction($exception='') {
        if ($this->checkTest()) {
            p_e("web_{$this->_sess_prefix}:" . $exception->getMessage());
        }
        $this->sendFail(CODE_ACTION_NOT_ALLOWED);
	}

}
