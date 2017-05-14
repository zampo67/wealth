<?php

/**
 * James
 * 2017-01-15
 */
class BenchmarkPlugin extends Yaf\Plugin_Abstract {
	public function routerStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
		Yaf\Registry::set('benchmark_start', microtime(true));
	}

	public function routerShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}

	public function dispatchLoopStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {

	}

	public function preDispatch(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {

	}

	public function postDispatch(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {

	}

	public function dispatchLoopShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
		$start = Yaf\Registry::get('benchmark_start');
		Yaf\Registry::del('benchmark_start');
		$time = microtime(true) - (float)$start;
		if ($time > 1)
		{
			Log::out('benchmark', 'I', $request->getRequestUri() . ':' . $time . ':' . (memory_get_usage(true) / 1024) . 'kb');
		}
	}
}
