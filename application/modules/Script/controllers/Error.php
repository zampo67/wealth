<?php

class ErrorController extends BasescriptController {

    public function init(){
        parent::init();
    }

	//从2.1开始, errorAction支持直接通过参数获取异常
	public function errorAction($exception='') {
        Log::script('error', json_encode($exception->getMessage()));
        print_r($exception->getMessage().PHP_EOL);
	}

}
