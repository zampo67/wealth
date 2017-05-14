<?php
/**
 * 队列worker脚本
 * 使用方法:
 * 在命令行下输入
 * nohup sudo -u apache php /data/www/zhiye/worker.php >> /data/www/zhiye/log/queue/`date "+%y%m%d"`.log 2>&1 &
 */
define('APPLICATION_PATH', dirname(__FILE__));

$application = new Yaf\Application(APPLICATION_PATH."/conf/application.ini");
$application->bootstrap();

$yaf_config = Yaf\Registry::get('config');
putenv('QUEUE='.MQ_QUEUE);
putenv('REDIS_BACKEND='.$yaf_config->queue['host'].':'.$yaf_config->queue['port']);

//加载job文件
$job_path = APPLICATION_PATH.'/application/jobs/';
$job_files = scandir($job_path);
foreach ($job_files as $file){
    if(is_file($job_path.$file)){
        require $job_path.$file;
    }
}

require APPLICATION_PATH.'/library/PHPResque/bin/resque';
