<?php

class QQOAuth {
    private static $appid = QQ_CONNECT_APPID;
    private static $appkey = QQ_CONNECT_APPKEY;
    private static $callback = QQ_CONNECT_CALLBACK;
    private static $access_token = '';

    public static function redirect($scope=''){
        $state = md5(uniqid(rand(), TRUE));
        UserIdentity::getInstance('web')->set('qq_state', $state); //CSRF protection
        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
            . self::$appid . "&redirect_uri=" . urlencode(self::$callback)
            . "&state=" . $state
            . "&scope=" . $scope;
        header("Location:$login_url");
    }

    public static function login(){
        if(self::getAccessToken()){
            return self::getOpenid();
        }else{
            return false;
        }
    }

    public static function getOpenid(){
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . self::$access_token;

        $str  = file_get_contents($graph_url);
        if (strpos($str, "callback") !== false) {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($str);
        if (isset($user->error)) {
            return false;
//            echo "<h3>error:</h3>" . $user->error;
//            echo "<h3>msg  :</h3>" . $user->error_description;
//            exit;
        }else{
            //debug
            //echo("Hello " . $user->openid);
            //set openid to session
            return $user->openid;
        }
    }

    public static function getAccessToken(){
        //debug
        //print_r($_REQUEST);
        //csrf
        if($_REQUEST['state'] == UserIdentity::getInstance('web')->get('qq_state')){
            $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
                . "client_id=" . self::$appid . "&redirect_uri=" . urlencode(self::$callback)
                . "&client_secret=" . self::$appkey. "&code=" . $_REQUEST["code"];

            $response = file_get_contents($token_url);
            if (strpos($response, "callback") !== false) {
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                $msg = json_decode($response);
                if (isset($msg->error)) {
                    return false;
//                    echo "<h3>error:</h3>" . $msg->error;
//                    echo "<h3>msg  :</h3>" . $msg->error_description;
//                    exit;
                }
            }

            $params = array();
            parse_str($response, $params);

            //debug
            //print_r($params);

            //set access token to session
            UserIdentity::getInstance('web')->set('qq_access_token',$params["access_token"]);
            self::$access_token = $params["access_token"];
            return self::$access_token;
        } else {
            exit("授权失效，请重新刷新后登录");
        }
    }

    public static function get_user_info($openid){
        $get_user_info = "https://graph.qq.com/user/get_user_info?"
            . "access_token=" . UserIdentity::getInstance('web')->get('qq_access_token')
            . "&oauth_consumer_key=" . self::$appid
            . "&openid=" . $openid
            . "&format=json";

        $info = file_get_contents($get_user_info);
        $arr = json_decode($info, true);

        return $arr;
    }

    public static function getUnionidByOpenid($openid){
        if(!empty($openid)){
            $url = "https://graph.qq.com/oauth2.0/get_unionid?openid={$openid}&client_id=".self::$appid;
            $str  = file_get_contents($url);
            if (strpos($str, "callback") !== false) {
                $lpos = strpos($str, "(");
                $rpos = strrpos($str, ")");
                $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
            }
            $res = json_decode($str, true);
            return isset($res['unionid']) ? $res['unionid'] : false;
        }else{
            return false;
        }
    }

}