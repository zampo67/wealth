<?php
Yaf\Loader::import('WeiXin/WeiXin.php');

class IWeiXin{
    protected static $instance = array();

    public static function getInstance($id=''){
        if(!empty($id) && is_numeric($id)){
            if (empty(self::$instance[$id])) {
                $public_info = WxPublicModel::model()->getInfoToIWeixinById($id);
                if (!empty($public_info)) {
                    self::$instance[$id] = new \WeiXin\WeiXin($public_info['token'], $public_info['appid'], $public_info['appsecret']);
                    self::$instance[$id]->setAccessTokenKey(\WeiXin\Variable::ACCESS_TOKEN_KEY . '_' . $id);
                    self::$instance[$id]->setJsapiTicketKey(\WeiXin\Variable::JSAPI_TICKET . '_' . $id);
                }
            }
            return isset(self::$instance[$id]) ? self::$instance[$id] : false;
        }else{
            if (empty(self::$instance['config'])) {
                self::$instance['config'] = new \WeiXin\WeiXin(WEIXIN_PUBLIC_TOKEN, WEIXIN_PUBLIC_APPID, WEIXIN_PUBLIC_APPSECRET);
            }
            return self::$instance['config'];
        }
    }

}