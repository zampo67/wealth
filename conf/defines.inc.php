<?php

//mysql
define('DB_TABLE_PREFIX', 'we_');
define('DB_GROUP_CONCAT_SEP', '|||');
define('DB_CONCAT_SEP', '^^^');
define('DB_CHARSET', 'utf8mb4');

//基础域名
define('BASE_PROTOCOL', 'http://');
define('IMAGE_DOMAIN', BASE_PROTOCOL.'139.199.227.192');
//wealth 域名
define('WEALTH_PROTOCOL', BASE_PROTOCOL);
define('WEALTH_DOMAIN', WEALTH_PROTOCOL.'139.199.227.192');
define('WEALTH_TEST_DOMAIN', WEALTH_PROTOCOL.'139.199.227.192');

define('LOG_DIR', APPLICATION_PATH . '/log/');
define('PUBLIC_PATH', APPLICATION_PATH.'/public');
define('IMAGE_PATH', PUBLIC_PATH.'/images');
