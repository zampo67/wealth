<?php

namespace WeiXin;
use \IRedis,
    \Log,
    \Tools,
    \Common;

require(dirname(__FILE__).'/Variable.php');

class WeiXin {
    private static $debug = false;
    private $token;
    public $postStr;
    public $postObj;

    private $appid;
    private $appsecret;
    private $access_token;
    private $jsapi_ticket;
    private $access_token_key=Variable::ACCESS_TOKEN_KEY;
    private $jsapi_ticket_key=Variable::JSAPI_TICKET;

    public function __construct($token, $appid, $appsecret, $debug = false) {
        $this->token = $token;
        self::$debug = $debug;
        if (!empty($_GET) && isset($_GET['echostr'])){
            $this->checkSignature();
        }
        $this->handleRequest();
        $this->appid = $appid;
        $this->appsecret = $appsecret;
    }

    public function setAccessTokenKey($access_token_key){
        $this->access_token_key = $access_token_key;
    }

    public function setJsapiTicketKey($jsapi_ticket_key){
        $this->jsapi_ticket_key = $jsapi_ticket_key;
    }

    public function responseCustomerService() {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            </xml>";
        $resultStr = sprintf($textTpl, $this->postObj->FromUserName, $this->postObj->ToUserName, $this->postObj->CreateTime, Variable::REPLY_TYPE_CUSTOMER_SERVICE);
        if (!headers_sent())
            header('Content-Type: application/xml; charset=utf-8');
        self::response($resultStr);
    }

    public function responseTextMessage($content) {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        $resultStr = sprintf($textTpl, $this->postObj->FromUserName, $this->postObj->ToUserName, time(), Variable::REPLY_TYPE_TEXT, str_replace("\r","",$content));
        if (!headers_sent())
            header('Content-Type: application/xml; charset=utf-8');
        self::response($resultStr);
    }

    //回复图片
    public function responseMessageImg($mediaId){
        $imgTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Image>
            <MediaId><![CDATA[%s]]></MediaId>
            </Image>
            </xml>";
        $resultStr = sprintf($imgTpl, $this->postObj->FromUserName, $this->postObj->ToUserName, time(), Variable::REPLY_TYPE_IMAGE, $mediaId);
        if (!headers_sent())
            header('Content-Type: application/xml; charset=utf-8');
        self::response($resultStr);

    }

    //回复图文
    public function responseMessageArt($articles){
        $sum = count($articles);
        if(!$sum){
            return false;
        }
        $item = '';
        foreach($articles as $val){
            $item .='<item>';
            $item .='<Title><![CDATA['.$val['title'].']]></Title> ';
            $item .='<Description><![CDATA['.$val['description'].']]></Description>';
            $item .='<PicUrl><![CDATA['.$val['picurl'].']]></PicUrl>';
            $item .='<Url><![CDATA['.$val['url'].']]></Url>';
            $item .='</item>';
        }

        $now_time  = time();
        $type  = Variable::REPLY_TYPE_NEWS;

        $resultStr = "<xml>
        <ToUserName><![CDATA[{$this->postObj->FromUserName}]]></ToUserName>
        <FromUserName><![CDATA[{$this->postObj->ToUserName}]]></FromUserName>
        <CreateTime>{$now_time}</CreateTime>
        <MsgType><![CDATA[{$type}]]></MsgType>
        <ArticleCount>{$sum}</ArticleCount>
        <Articles>
        {$item}
        </Articles>
        </xml>";

        if (!headers_sent())
            header('Content-Type: application/xml; charset=utf-8');
        self::response($resultStr);
    }

    public function responseMessageTemplate($openid, $template_id, $data, $url='', $topcolor=''){
        if ($this->getAccessToken()){
            $params = array(
                'touser' => $openid,
                'template_id' => $template_id,
                'url' => $url,
                'topcolor' => $topcolor,
                'data' => $data,
            );
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $res = json_decode(self::sendRequest(Variable::getLink('message_send_template').'?access_token='.$this->access_token, $params, 'POST'));
            if(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->responseMessageTemplate($openid, $template_id, $data, $url, $topcolor);
            }else{
                return $res;
            }
        }else{
            return false;
        }
    }

    public function responseMessageCustom($openid,$content,$type=Variable::MSG_TYPE_TEXT){
        if ($this->getAccessToken()){
            $params = array(
                'touser' => $openid,
                'msgtype' => $type,
            );
            switch($type){
                case 'text':
                    $params['text'] = array('content'=>str_replace("\r","",$content));
                    break;
                case 'image':
                    $params['image'] = array('media_id'=>$content);
                    break;
                case 'news':
                    $params['news'] = array('articles'=>$content);
                    break;
                default:
                    return false;
                    break;
            }
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $res = json_decode(self::sendRequest(Variable::getLink('message').'?access_token='.$this->access_token, $params, 'POST'));
            if(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->responseMessageCustom($content, $type);
            }else{
                return $res;
            }
        }else{
            return false;
        }
    }

    //生成二维码 1 临时 2 永久
    public function qrcodeCreate($scene_id,$is_ever=1,$seconds=1800,$scene_str=''){
        if ($this->getAccessToken()){
            if($is_ever==1){
                $params = array(
                    'expire_seconds' => $seconds,
                    'action_name' => 'QR_SCENE',
                    'action_info' => array('scene'=>array(
                        'scene_id' => $scene_id,
                    )),
                );
            }else{
                $params = array(
                    'action_name' => 'QR_LIMIT_SCENE',
                    'action_info' => array('scene'=>array(
                        'scene_id' => $scene_id,
                    )),
                );
                if(!empty($scene_str)){
                    $params['action_info']['scene']['scene_str'] = $scene_str;
                }
            }

            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $res = json_decode(self::sendRequest(Variable::getLink('qrcode').'?access_token='.$this->access_token, $params, 'POST'));
            if(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->qrcodeCreate($scene_id, $is_ever, $seconds);
            }else{
                return $res;
            }
        }else{
            return false;
        }
    }

    public function showQrcode($ticket){
        return Variable::getLink('showqrcode').'?ticket='.urlencode($ticket);
    }

    public function customMenuCreate($message=array()){
        if ($this->getAccessToken()){
            $params = array(
                'button' => $message,
            );
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $res = json_decode(self::sendRequest(Variable::getLink('menu_create').'?access_token='.$this->access_token, $params, 'POST'));
            if(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->customMenuCreate($message);
            }else{
                return $res;
            }
        }else{
            return false;
        }
    }

    public function mediaUpload($filetype, $filepath){
        if ($this->getAccessToken() && file_exists($filepath)){
            $fileext = strtolower(Tools::getFileExtension($filepath));
            $filesize = filesize($filepath);
            switch ($filetype){
                case Variable::MEDIA_TYPE_IMAGE:
                    if ($fileext != 'jpg' || $filesize > 1024 * 1024){
                        return false;
                    }
                    break;
                case Variable::MEDIA_TYPE_VOICE:
                    if (!in_array($fileext, array('amr', 'mp3')) || $filesize > 1024 * 1024 * 2){
                        return false;
                    }
                    break;
                case Variable::MEDIA_TYPE_VIDEO:
                    if ($fileext != 'mp4' || $filesize > 1024 * 1024 * 10){
                        return false;
                    }
                    break;
                case Variable::MEDIA_TYPE_THUMB:
                    if ($fileext != 'jpg' || $filesize > 64 * 1024){
                        return false;
                    }
                    break;
                default:
                    return false;
                    break;
            }
            $url = Variable::getLink('media_upload')."?access_token={$this->access_token}&type={$filetype}";
            exec('curl -F media=@'.$filepath.' "'.$url.'"', $output);
            Log::weixin("file[upload]", "url:{$filepath};output:".json_encode($output));

            $res = json_decode($output[0]);
            if(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->mediaUpload($filetype, $filepath);
            }else{
                return $res;
            }
        }
        return false;
    }

    // 添加永久素材
    public function addMaterial($filetype, $filepath){
        if ($this->getAccessToken() && file_exists($filepath)){
            $fileext = strtolower(Tools::getFileExtension($filepath));
            $filesize = filesize($filepath);
            switch ($filetype){
                case Variable::MEDIA_TYPE_IMAGE:
                    if (!in_array($fileext, array('jpg', 'png')) || $filesize > 1024 * 1024){
                        return false;
                    }
                    break;
                case Variable::MEDIA_TYPE_VOICE:
                    if (!in_array($fileext, array('amr', 'mp3')) || $filesize > 1024 * 1024 * 2){
                        return false;
                    }
                    break;
                case Variable::MEDIA_TYPE_VIDEO:
                    if ($fileext != 'mp4' || $filesize > 1024 * 1024 * 10){
                        return false;
                    }
                    break;
                case Variable::MEDIA_TYPE_THUMB:
                    if ($fileext != 'jpg' || $filesize > 64 * 1024){
                        return false;
                    }
                    break;
                default:
                    return false;
                    break;
            }
            $url = Variable::getLink('add_material')."?access_token={$this->access_token}&type={$filetype}";
            exec('curl -F media=@'.$filepath.' "'.$url.'"', $output);
            Log::weixin("addMaterial", "url:{$filepath};output:".json_encode($output));
            $res = json_decode($output[0]);
            if(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->addMaterial($filetype, $filepath);
            }else{
                return $res;
            }
        }
        return FALSE;
    }

    // 删除永久素材
    public function delMaterial($media_id){
        if ($this->getAccessToken()){
            $params = array(
                'media_id' => $media_id
            );
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $url = Variable::getLink('del_material')."?access_token={$this->access_token}";

            Log::weixin("delMaterial", "url:{$url}");

            $res = json_decode(self::sendRequest($url, $params, 'POST'));
            if(!is_null($res) && isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->delMaterial($media_id);
            }else{
                return $res;
            }
        }
        return false;
    }


    public function mediaDownload($media_id){
        if ($this->getAccessToken()){
            $params = array(
                'access_token' => $this->access_token,
                'media_id' => $media_id
            );
            $url = Variable::getLink('media_download').'?'.http_build_query($params);

            Log::weixin("file[download]", "url:{$url}");
            $image_all = Common::curlGetResponse($url);

            $res = json_decode($image_all['body']);
            if(!is_null($res) && isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->mediaDownload($media_id);
            }else{
                return $image_all;
            }
        }else{
            return false;
        }
    }

    public function newsUpload($message){
        if ($this->getAccessToken()){
            $params = array(
                'articles' => $message,
            );
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $url = Variable::getLink('news_upload')."?access_token={$this->access_token}";
            $res = json_decode(self::sendRequest($url, $params, 'POST'));
            return $res;
        }else{
            return false;
        }
    }

    public function messageSendGroup($media_id, $type, $group_id=0){
        if ($this->getAccessToken()){
            $params = array();

            if(empty($group_id)){
                $params['filter'] = array(
                    'is_to_all' => true
                );
            }else{
                $params['filter'] = array(
                    'is_to_all' => false,
                    'group_id' => $group_id
                );
            }

            $params[$type]['media_id'] = $media_id;
            $params['msgtype'] = $type;

            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $url = Variable::getLink('message_send_group')."?access_token={$this->access_token}";
            $res = json_decode(self::sendRequest($url, $params, 'POST'));
            return $res;
        }else{
            return false;
        }
    }

    public function messageSendOpenId($media_id,$type,$openId){
        if ($this->getAccessToken()){
            $params = array();

            if(empty($openId)){
                return false;
            }
            $params['touser'] = $openId;
            if($type=='text'){
                $params[$type]['content'] = $media_id;
            }else{
                $params[$type]['media_id'] = $media_id;
            }

            $params['msgtype'] = $type;

            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $url = Variable::getLink('message_send_openids')."?access_token={$this->access_token}";
            $res = json_decode(self::sendRequest($url, $params, 'POST'));
            return $res;
        }else{
            return false;
        }
    }

    public function getOauthRedirectUrl($redirect_uri, $scope='snsapi_base'){
        $params = array(
            'appid' => $this->appid,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => $scope,
        );
        $params_str = http_build_query($params);
        $url = Variable::getLink('oauth_code').'?'.$params_str.'#wechat_redirect';
        Log::weixin('oauth-redirect-url', $url);
        return $url;
    }

    public function getOauthOpenid($code){
        $res = $this->getOauthAccessToken($code);
        if(isset($res->openid)){
            return $res->openid;
        }else{
            return false;
        }
    }

    public function getOauthAccessToken($code){
        $params = array(
            'appid'      => $this->appid,
            'secret'     => $this->appsecret,
            'code'       => $code,
            'grant_type' => 'authorization_code',
        );
        $res = json_decode(self::sendRequest(Variable::getLink('oauth_access_token'), $params));
        if(isset($res->errcode) && $res->errcode == '40001'){
            if($res->errcode == '40001'){
                IRedis::getInstance()->delete($this->access_token_key);
                return $this->getOauthAccessToken($code);
            }else{
                return $res;
            }
        }else{
            return $res;
        }
    }

    public function getOauthUserInfo($access_token, $openid){
        if (!empty($access_token) && !empty($openid)){
            $params = array(
                'access_token' => $access_token,
                'openid' => $openid,
            );
            $res = json_decode(self::sendRequest(Variable::getLink('oauth_userinfo'), $params), true);
            return isset($res['errcode']) ? false : $res;
        }else{
            return false;
        }
    }

    public function getUserInfo($openid, $return_arr=false){
        if ($this->getAccessToken()){
            $params = array(
                'access_token' => $this->access_token,
                'openid' => $openid,
            );
            $res = json_decode(self::sendRequest(Variable::getLink('user_info'), $params), $return_arr);
            if(empty($return_arr)){
                if(isset($res->errcode) && $res->errcode == '40001'){
                    IRedis::getInstance()->delete($this->access_token_key);
                    return $this->getUserInfo($openid, $return_arr);
                }else{
                    return $res;
                }
            }else{
                if(isset($res['errcode']) && $res['errcode'] == '40001'){
                    IRedis::getInstance()->delete($this->access_token_key);
                    return $this->getUserInfo($openid, $return_arr);
                }else{
                    return $res;
                }
            }
        }else{
            return false;
        }
    }

    public function getUserListByOpenids($openids){
        if ($this->getAccessToken()){
            $params = json_encode(array(
                'user_list' => $openids,
            ));
            $res = json_decode(self::sendRequest(Variable::getLink('user_batchget').'?access_token='.$this->access_token, $params, 'POST'));
            if(isset($res->errcode)){
                if($res->errcode == '40001'){
                    IRedis::getInstance()->delete($this->access_token_key);
                    return $this->getUserListByOpenids($openids);
                }else{
                    return $res;
                }
            }else{
                return $res;
            }
        }else{
            return false;
        }
    }

    private function getAccessToken(){
        $this->access_token = IRedis::getInstance()->get($this->access_token_key);
        if (empty($this->access_token)){
            $params = array(
                'appid'      => $this->appid,
                'secret'     => $this->appsecret,
                'grant_type' => 'client_credential',
            );
            $res = json_decode(self::sendRequest(Variable::getLink(Variable::ACCESS_TOKEN_KEY), $params));
            if (isset($res->access_token) && isset($res->expires_in)){
                $this->access_token = $res->access_token;
                IRedis::getInstance()->setEx($this->access_token_key, $this->access_token);
            }else{
                return false;
            }
        }
        return $this->access_token;
    }

    private function getJsApiTicket() {
        $this->jsapi_ticket = IRedis::getInstance()->get($this->jsapi_ticket_key);
        if (empty($this->jsapi_ticket)){
            $params = array(
                'type'         => 'jsapi',
                'access_token' => $this->getAccessToken(),
            );
            $res = json_decode(self::sendRequest(Variable::getLink(Variable::JSAPI_TICKET), $params));
            if (isset($res->ticket) && isset($res->expires_in)){
                $this->jsapi_ticket = $res->ticket;
                IRedis::getInstance()->setEx($this->jsapi_ticket_key, $this->jsapi_ticket);
            }elseif(isset($res->errcode) && $res->errcode == '40001'){
                IRedis::getInstance()->delete($this->jsapi_ticket_key);
                return $this->getJsApiTicket();
            }else{
                return false;
            }
        }
        return $this->jsapi_ticket;
    }

    public function getJsApiSignature($noncestr, $now_time, $url){
        $jsapi_ticket = $this->getJsApiTicket();
        $str = "jsapi_ticket={$jsapi_ticket}"
            . "&noncestr={$noncestr}"
            . "&timestamp={$now_time}"
            . "&url={$url}";
        $signature = sha1($str);
        return $signature;
    }

    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getJsApiSignPackage($uri=''){
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = $protocol.$_SERVER['HTTP_HOST'].(!empty($uri) ? urldecode($uri) : $_SERVER['REQUEST_URI']);
        $now_time = time();
        $noncestr = $this->createNonceStr();
        $signature = $this->getJsApiSignature($noncestr, $now_time, $url);

        return array(
            'appId' => $this->appid,
            'timestamp' => $now_time,
            'nonceStr' => $noncestr,
            'signature' => $signature,
        );
    }

    public function getWxIpList(){
        $key = 'wx_ip_list';
        $ip_list = IRedis::getInstance()->get($key);
        if(empty($ip_list)){
            if ($this->getAccessToken()){
                $params = array(
                    'access_token' => $this->access_token,
                );
                $res = json_decode(self::sendRequest(Variable::getLink('ip_list'), $params));
                if (isset($res->ip_list)){
                    IRedis::getInstance()->set($key, implode('||', $res->ip_list));
                    return $res->ip_list;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return explode('||', $ip_list);
        }
    }

    /**
     * 发送请求到微信服务器
     */
    public static function sendRequest($url, $params=array(), $method='GET'){
        if(is_array($params)){
            $params_str = http_build_query($params);
        }else{
            $params_str = $params;
        }
        Log::weixin("send-request[{$method}]", "url:{$url};params:{$params_str}");
        switch ($method){
            case 'GET' : default :
            $res = Tools::curl($url.'?'.$params_str);
            break;
            case 'POST' :
                $res = Tools::curl($url, 'POST', $params);
                break;
        }
        Log::weixin("send-response", $res);
        return $res;
    }

    /**
     * 处理微信服务器的请求
     */
    private function handleRequest() {
        if (isset($_GET['echostr'])){
            echo $_GET['echostr'];
            exit;
        }else{
            if(isset($GLOBALS['HTTP_RAW_POST_DATA']) && $GLOBALS['HTTP_RAW_POST_DATA']){
                $this->postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
                $this->postObj = simplexml_load_string($this->postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            }elseif(isset($_GET['HTTP_RAW_POST_DATA']) && $_GET['HTTP_RAW_POST_DATA']){
                $this->postStr = $_GET['HTTP_RAW_POST_DATA'];
                $this->postObj = json_decode($this->postStr);
            }else{
                return ;
//                $this->postStr = file_get_contents("php://input");
//                $this->postObj = json_decode($this->postStr);
            }
            Log::weixin('request', $this->postStr);
        }
    }

    /**
     * 响应请求到微信服务器
     */
    public static function response($content) {
        Log::weixin('response', 'echo:' . $content);
        echo $content;
    }

    /**
     * 检查签名
     */
    private function checkSignature() {
        Log::weixin('sign', http_build_query($_GET));
        if (self::$debug){
            return true;
        }
        if (!isset($_GET['signature']) || !isset($_GET['timestamp']) || !isset($_GET['nonce'])){
            return false;
        }
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $tmpArr = array($this->token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }
}
