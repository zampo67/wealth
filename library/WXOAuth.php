<?php

class WXOAuth {
    protected static $instance='';
    private $appid;
    private $appkey;
    private $callback;
    private $oauth_info=array();
    private $user_info=array();

    private function __construct(){
        $this->appid = WEIXIN_OPEN_APPID;
        $this->appkey = WEIXIN_OPEN_APPSECRET;
        $this->callback = WEIXIN_OPEN_CALLBACK;
    }

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 跳转至微信获取授权
     * @param string $scope
     */
    public function redirect($scope='snsapi_login'){
        $state = md5(uniqid(rand(), TRUE));
        ITokenCache::getInstance()->set('wx_state', $state);
        header("Location:"
            . "https://open.weixin.qq.com/connect/qrconnect?response_type=code&appid={$this->appid}"
            . "&redirect_uri=" . urlencode($this->callback)
            . "&state={$state}&scope={$scope}");
        exit;
    }

    /**
     * 处理微信授权的返回
     * @return array|bool|mixed
     */
    public function handleResponse(){
        //csrf
        if(!empty($_REQUEST["code"]) && !empty($_REQUEST["state"]) && $_REQUEST['state'] == ITokenCache::getInstance()->get('wx_state')){
            $response = json_decode(file_get_contents(
                "https://api.weixin.qq.com/sns/oauth2/access_token?"
                . "grant_type=authorization_code&appid={$this->appid}"
                . "&secret={$this->appkey}&code={$_REQUEST["code"]}"
            ), true);
            if(!empty($response['access_token']) && !empty($response['unionid'])){
                $this->oauth_info = $response;
                return $this->oauth_info;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 获取access_token
     * @return bool|mixed
     */
    public function getAccessToken(){
        return !empty($this->oauth_info['access_token']) ? $this->oauth_info['access_token'] : false;
    }

    /**
     * 获取unionid
     * @return bool|mixed
     */
    public function getUnionid(){
        return !empty($this->oauth_info['unionid']) ? $this->oauth_info['unionid'] : false;
    }

    /**
     * 获取openid
     * @return bool|mixed
     */
    public function getOpenid(){
        return !empty($this->oauth_info['openid']) ? $this->oauth_info['openid'] : false;
    }

    /**
     * 获取用户信息
     * @return bool|mixed
     */
    public function getUserInfo(){
        if(empty($this->user_info)){
            if(!empty($this->oauth_info)){
                $response = json_decode(file_get_contents(
                    "https://api.weixin.qq.com/sns/userinfo?access_token={$this->oauth_info['access_token']}&openid={$this->oauth_info['openid']}"
                ), true);
                if(empty($response['errcode'])){
                    $this->user_info = $response;
                    return $this->user_info;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return $this->user_info;
        }
    }

}