<?php

/**
 * @name ErrorController
 * @desc   错误控制器, 在发生未捕获的异常时刻被调用
 * @see    http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 */
class ErrorController extends BaseController {

    public function init(){
        parent::init();
    }

	//从2.1开始, errorAction支持直接通过参数获取异常
	public function errorAction($exception='') {
        header('HTTP/1.1 404 Not Found');
        header('status: 404 Not Found');
        p_e('error');
//        p_e($exception->getMessage());

        $code = '404';
        $msg = '您访问的页面找不到咯';

        $this->display('', array(
            'code' => $code,
            'msg' => $msg,
        ));

//		return true;
	}
}
