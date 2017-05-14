<?php

/**
 * Class SendLimit
 * 发送短信/邮箱相关限制类
 */

class SendLimit {
    protected static $instance='';
    protected $_token_limit='';
    
    private function __construct(){

    }

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 设置客户端凭证
     * @param string|int $token
     * @return string
     */
    public function setTokenLimit($token){
        $this->_token_limit = $token;
        return $this->_token_limit;
    }

    /**
     * 检查并获取客户端凭证
     * @param string|int $error_code 错误码
     * @param int $is_exit  是否中断
     * @return string|bool
     */
    public function getTokenLimit($error_code=CODE_CLIENT_NOT_ALLOWED, $is_exit=1){
        if(empty($this->_token_limit)){
            if($is_exit == 1){
                Common::sendFailRes($error_code);
            }else{
                return false;
            }
        }
        return $this->_token_limit;
    }

    public function checkDailyLimit($limit_key, $options=array(), $is_exit=1){
        $limit_key = 'send_limit_'.$limit_key;
        $limit_cache = IRedis::getInstance()->get($limit_key);
        if(!empty($limit_cache)){
            if(!empty($options['limit_count']) && count($limit_cache) >= $options['limit_count']){
                if($is_exit == 1){
                    $code = !empty($options['limit_count_error']['code']) ? $options['limit_count_error']['code'] : CODE_SEND_LIMIT_UPPER;
                    $msg = !empty($options['limit_count_error']['msg']) ? $options['limit_count_error']['msg'] : '';
                    Common::sendFailRes($code, $msg);
                }else{
                    return false;
                }
            }
            if(!empty($options['limit_time'])){
                $now_time = time();
                foreach ($limit_cache as $lt) {
                    if($now_time - $lt['ctime'] < $options['limit_time']){
                        if($is_exit == 1){
                            $code = !empty($options['limit_count_error']['code']) ? $options['limit_count_error']['code'] : CODE_SEND_LIMIT_TOO_FAST;
                            $msg = !empty($options['limit_count_error']['msg']) ? $options['limit_count_error']['msg'] : '';
                            Common::sendFailRes($code, $msg);
                        }else{
                            return false;
                        }
                    }
                }
            }
        }
    }

    public function setDailyLimit($limit_key, $row=array()){
        $limit_key = 'send_limit_'.$limit_key;
        $now_time = time();
        $expire_time = mktime(23, 59, 59, date('m'), date('d'), date('Y')) - $now_time;
        if($expire_time <= 0){
            $expire_time = 1;
        }
        $limit_cache = IRedis::getInstance()->get($limit_key);
        if(empty($limit_cache)){
            $limit_cache = array();
        }
        $row['ctime'] = $now_time;
        $limit_cache[] = $row;
        return IRedis::getInstance()->setEx($limit_key, $limit_cache, $expire_time);
    }

    public function checkMobileMsg($mobile, $code='', $is_exit=1){
        $token_limit = $this->getTokenLimit();
        $options = array(
            'limit_count' => 10,
            'limit_count_error' => array('code'=>$code, 'msg'=>''),
            'limit_time' => 60,
            'limit_time_error' => array('code'=>$code, 'msg'=>''),
        );
        $this->checkDailyLimit('mobile_'.$token_limit, $options, $is_exit);
        $this->checkDailyLimit('mobile_'.$mobile, $options, $is_exit);
    }

    public function setMobileMsg($mobile){
        $token_limit = $this->getTokenLimit();
        $this->setDailyLimit('mobile_'.$token_limit);
        $this->setDailyLimit('mobile_'.$mobile);
    }

}
