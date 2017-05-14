<?php
define('WEB_COMMON_VERSION', '2');
define('WEB_HTML_VERSION', '2');

define('DOMAIN', WEALTH_DOMAIN);
define('TEST_DOMAIN', WEALTH_TEST_DOMAIN);

define('UPLOAD_PATH', '/upload/wealth/');
define('TEMP_PATH', '/temp/wealth/');
define('IMAGE_UPLOAD_PATH', UPLOAD_PATH.'images/');
define('IMAGE_TEMP_PATH', TEMP_PATH.'images/');

define('IMAGE_SQUARE_SIZE', '300');

//微信公众号 1
define('WEIXIN_PUBLIC_ID', 1);
define('WEIXIN_PUBLIC_TOKEN', '54fd52fe3c89a');
define('WEIXIN_PUBLIC_APPID', 'wx5f0e4aeb0460cdf0');
define('WEIXIN_PUBLIC_APPSECRET', 'efea1b74d030474bc8b23cf405bedb0d');
define('WEIXIN_PUBLIC_ASEKEY', '');

//微信支付
define('WEIXIN_PAY_MCHID', '');
define('WEIXIN_PAY_KEY', '');
define('WEIXIN_PAY_NOTIFY_URL', DOMAIN.'/resumewx/notify/wxpay');

//微信开放平台 1
define('WEIXIN_OPEN_APPID', 'wx8fd6f950c44b936e');
define('WEIXIN_OPEN_APPSECRET', 'dcfbc5b3df3168655df03042beb80079');
define('WEIXIN_OPEN_CALLBACK', DOMAIN.'/connect/wxcallback');

//QQ登录 1
define('QQ_CONNECT_APPID', '101274618');
define('QQ_CONNECT_APPKEY', 'f945f81b5e3c7d3bce64a0de604e0430');
define('QQ_CONNECT_CALLBACK', DOMAIN.'/connect/qqcallback');

//微博 1
define('WEIBO_APPKEY', '700754799');
define('WEIBO_APPSECRET', '7203ef724a086d2f1799ce0e34cd07b6');
define('WEIBO_CALLBACK', DOMAIN.'/connect/weibocallback');

//邮件发送商sendcloud
define('SENDCLOUD_API_KEY', 'I0FfE04hhrjknm6o');
define('SENDCLOUD_API_USER', 'zhiyeapp');
define('SENDCLOUD_API_USER_MULTI', 'zhiyeappMulti');

//阿里大于
define('ALIDAYU_APPKEY', '23669791');
define('ALIDAYU_APPSECRET', '770ce311ed1238e5441776617f9f67ed');

//极验验证码 1
define('GEETEST_CAPTCHA_ID', '117fc67c15af8d00188c82801c861804');
define('GEETEST_PRIVATE_KEY', 'dd8f1418410d7f9ebe73ed1adee46eb6');

//极验3验证码 1
define('GEETEST3_CAPTCHA_ID', 'cb5a1d308c71fa4abcf1bf271132c743');
define('GEETEST3_PRIVATE_KEY', '8fdb96a0ea87e135d48ef299c5048981');

//redis配置
define('REDIS_PREFIX', 'zwr:');
