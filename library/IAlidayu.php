<?php

/**
 * 阿里大于,单例模式入口
 * User: James
 * Date: 15/11/2
 * Time: 下午11:44
 */
class IAlidayu{
    protected static $instance='';
    private $client = null;
    private $template_code_verify = 'SMS_53925371';
    private $product_name = '知页';
    private $sign_name = '知页Zalent';

    private function __construct(){
        defined('ALIDAYU_APPKEY') or define('ALIDAYU_APPKEY', '');
        defined('ALIDAYU_APPSECRET') or define('ALIDAYU_APPSECRET', '');
        Yaf\Loader::import('Alidayu/TopSdk.php');
        Yaf\Loader::import('Alidayu/top/TopClient.php');
        Yaf\Loader::import('Alidayu/top/ResultSet.php');
        Yaf\Loader::import('Alidayu/top/RequestCheckUtil.php');
        Yaf\Loader::import('Alidayu/top/TopLogger.php');
        Yaf\Loader::import('Alidayu/top/request/AlibabaAliqinFcSmsNumSendRequest.php');
        $this->client = new TopClient;
        $this->client->appkey = ALIDAYU_APPKEY;
        $this->client->secretKey = ALIDAYU_APPSECRET;
        $this->client->format = 'json';
    }

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 设置为沙盒环境
     */
    public function setSandbox(){
        $this->client->gatewayUrl = 'http://gw.api.tbsandbox.com/router/rest';
    }

    /**
     * 设置为生产环境
     */
    public function setProduct(){
        $this->client->gatewayUrl = 'http://gw.api.taobao.com/router/rest';
    }

    /**
     * 发送短信验证码
     * @param int $mobile 手机号码
     * @param int $code 验证码
     * @param string $limit_time 时限
     * @param array $extend 扩展字段
     * @return bool
     */
    public function sendMessageVerifyCode($mobile, $code, $limit_time, $extend=array()){
        settype($code, 'string');
        $resp = $this->sendMessage($mobile, $this->template_code_verify, array(
            'name' => $this->product_name,
            'code' => $code,
            'limit_time' => $limit_time,
        ), $extend);
        if(isset($resp->result->err_code) && $resp->result->err_code == 0){
            Log::mobile('succ', "mobile:{$mobile},code:{$code},".json_encode($resp));
            return true;
        }else{
            Log::mobile('fail', "mobile:{$mobile},code:{$code},".json_encode($resp));
            return false;
        }
    }

    /**
     * 发送短信
     * @param int $mobile 手机号码
     * @param string $template_code
     * @param array $params 变量参数
     * @param array $extend 扩展字段
     * @return mixed|ResultSet|SimpleXMLElement
     */
    public function sendMessage($mobile, $template_code, $params=array(), $extend=array()){
        if(is_array($mobile)){
            $mobile = implode(',', $mobile);
        }else{
            settype($mobile, 'string');
        }

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        if(!empty($extend)){
            if(is_array($extend)){
                $extend = json_encode($extend);
            }
            $req->setExtend($extend);
        }
        $req->setSmsType("normal");
        $req->setSmsFreeSignName($this->sign_name);
        $req->setSmsParam(json_encode($params));
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode($template_code);
        return $this->client->execute($req);
    }

}