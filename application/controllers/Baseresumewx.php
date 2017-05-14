<?php

/**
 * 微信Controller
 */
class BaseresumewxController extends BaseController {
    protected $_wx_user = array();
    protected $_user = array();
    protected $_openid = '';
    protected $_check_token = 1;
    protected $_sess_prefix = 'rwx';
    protected $_mobile_detect = null;
    protected $_is_web = 0;

    public function init() {
        // 配置国际化文件目录
        $this->setI18nPath();
        parent::init();
        // options请求的判断
        if($this->getRequest()->getMethod() == 'OPTIONS'){
            $this->sendAndExit();
        }
        // 检查token
        if($this->_check_token == 1){
            // 日志记录
            Log::request('req|head', json_encode($this->_server()));
            Log::request('req|uri ', $this->_server('HTTP_HOST').$this->_server('REQUEST_URI'));
            Log::request('req|body', http_build_query($this->_request()));
            $this->checkLsToken();
            $this->setHtmlHead();
            $this->setWxShare();
        }
        // 浏览器记录
        $this->browserLog();
        // 设置view路径
        $this->_view_path = PUBLIC_PATH.'/static/cvwx/';
        $this->_view_page_path = $this->_view_path.'page/';
    }

    public function checkLsToken(){
        $ls_token = $this->getLsToken();
        if(empty($ls_token) || empty(ResumeWebLsTokenModel::model()->checkToken($ls_token))){
            if(!empty($this->commonCookie('browser_name'))){
                $ls_token = ResumeWebLsTokenModel::model()->getToken();
                $this->commonCookie('ls_token', $ls_token);
            }else{
                $this->sendFail(CODE_APP_LS_TOKEN_NOT_EXIST);
            }
        }
        SendLimit::getInstance()->setTokenLimit($ls_token);
    }

    public function getLsToken(){
        return $this->_request('ls_token');
    }

    public function checkBind($is_send=1){
        $this->checkLogin();
        if(empty($this->_wx_user['unionid']) || empty($this->_user['id'])){
            $res = $this->refreshThisUser(array('wx_unionid' => $this->_wx_user['unionid']));
            if(!empty($res)){
                ISession::getInstance()->set('user', $this->_user);
            }else{
                $user_id = UserModel::model()->MSave(array(
                    'wx_unionid' => $this->_wx_user['unionid'],
                    'wx_openid_public' => $this->_wx_user['openid'],
                    'wx_nickname' => $this->_wx_user['nickname'],
                    'register_type_id' => VariablesModel::model()->getAttrs('registerType', 'wx', 'id'),
                    'register_plat_type_id' => VariablesModel::model()->getAttrs('registerPlatType', 'web_mobile', 'id'),
                    'register_module_type_id' => VariablesModel::model()->getAttrs('registerModuleType', 'question', 'id'),
                ));
                if(!empty($user_id)){
                    $this->refreshThisUser(array('id' => $user_id));
                }else{
                    if($is_send == 1){
                        $this->sendFail(CODE_WX_USER_NOT_BIND);
                    }else{
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function refreshThisUser($where){
        $user_info = UserModel::model()->MFind(array(
            'field' => 'id,mobile,email,wx_openid_public,wx_unionid',
            'where' => $where,
        ));
        if(!empty($user_info)){
            if(!empty($this->_wx_user['unionid']) && $this->_wx_user['unionid'] != $user_info['wx_unionid']){
                ISession::getInstance()->delete('user');
                return false;
            }
            if(!empty($this->_wx_user['openid']) && $this->_wx_user['openid'] != $user_info['wx_openid_public']){
                UserModel::model()->MSave(array(
                    'id' => $user_info['id'],
                    'wx_openid_public' => $this->_wx_user['openid'],
                ));
                $user_info['wx_openid_public'] = $this->_wx_user['openid'];
            }
            $this->_user = $user_info;
            return true;
        }else{
            return false;
        }
    }

    public function checkLogin($is_send=1){
        if(empty($this->_wx_user['unionid'])){
            $sess_id = $this->commonCookie('ls_sess_id');
            if(empty($sess_id)){
                $sess_id = $this->_request('ls_sess_id', '');
            }
            if(!empty($sess_id)){
                ISession::getInstance()->sessionId($sess_id, $this->_sess_prefix);
                $wx_user_info = ISession::getInstance()->get('wx_user');
                if(!empty($wx_user_info)){
                    $this->_wx_user = $wx_user_info;
                    $this->_openid = $wx_user_info['openid'];

                    $user_info = ISession::getInstance()->get('user');
                    if(!empty($user_info['id'])){
                        $this->refreshThisUser(array('id' => $user_info['id']));
                    }

                    ISession::getInstance()->setActive();
                }else{
                    UserModel::model()->logout($sess_id);
                    if($is_send == 1){
                        $this->sendFail(CODE_SESSION_TIMEOUT);
                    }elseif($is_send == 2){
                        $this->setLoginSession();
                    }else{
                        return false;
                    }
                }
            }else{
                if($is_send == 1){
                    $this->sendFail(CODE_SESSION_TIMEOUT);
                }elseif($is_send == 2){
                    $this->setLoginSession();
                }else{
                    return false;
                }
            }
        }
        return true;
    }

    public function setLoginSession(){
        $openid = $this->getOpenId();
        if(!empty($openid)){
            $save_data = WxUserModel::model()->saveByOpenid($openid);
            if(!empty($save_data)){
                $wx_user_info = array(
                    'id' => $save_data['id'],
                    'openid' => $save_data['openid'],
                    'unionid' => $save_data['unionid'],
                    'nickname' => !empty($save_data['nickname']) ? $save_data['nickname'] : '',
                );
                $sess_id = UserModel::model()->login(array('wx_user' => $wx_user_info), $wx_user_info['id'], $this->_sess_prefix);
                $this->commonCookie('ls_sess_id', $sess_id);

                $protocol = ($this->_server('HTTPS') == 'ON' || $this->_server('SERVER_PORT') == 443) ? "https://" : "http://";
                $parse = parse_url($this->_server('REQUEST_URI'));
                $param = $_GET;
                if(isset($param['code'])){
                    unset($param['code']);
                }
                if(isset($param['state'])){
                    unset($param['state']);
                }

                $this->redirect($protocol.$this->_server('HTTP_HOST').$parse['path'].(!empty($param) ? '?'.http_build_query($param) : ''));
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function getOpenId($scope='snsapi_base'){
        $code = $this->_get('code');
        if(!empty($code)){
            Log::weixin('redirect-response',json_encode($_GET));
            $user_info = IWeiXin::getInstance()->getOauthAccessToken($code);
            return isset($user_info->openid) ? $user_info->openid : '';
        }else{
            $redirect_uri = BASE_PROTOCOL.$this->_server('HTTP_HOST').$this->_server('REQUEST_URI');
            $weixin_url = IWeiXin::getInstance()->getOauthRedirectUrl($redirect_uri, $scope);
            $this->redirect($weixin_url);
        }
    }

    public function sendAndExit($data=array(), $msg='', $send_common_info=1){
        $this->send($data, $msg, $send_common_info);exit;
    }

    public function send($data=array(), $msg='', $send_common_info=1){
        if($send_common_info == 1){
            $data['wx_config'] = IWeiXin::getInstance()->getJsApiSignPackage($this->_request('ls_wx_uri'));
            if(isset($this->_tpl_vars['wx_share'])){
                $data['wx_share'] = $this->_tpl_vars['wx_share'];
            }
        }else{
            if(isset($this->_tpl_vars['_head'])){
                unset($this->_tpl_vars['_head']);
            }
        }
        parent::send($data, $msg);
    }

    public function setWxShare($share=array()){
        $this->_tpl_vars['wx_share'] = array(
            'title' => !empty($share['title']) ? $share['title'] : $this->_tpl_vars['_head']['title'],
            'desc' => !empty($share['desc']) ? $share['desc'] : $this->_tpl_vars['_head']['description'],
            'link' => !empty($share['link']) ? $share['link'] : '',
            'imgUrl' => !empty($share['img_url']) ? $share['img_url'] : IMAGE_DOMAIN.'/static/images/web/logo_wx_share.png?v='.WEB_COMMON_VERSION,
        );
    }

    /**
     * 设置html头部
     * @param array $head 头部信息
     */
    public function setHtmlHead($head=array()){
        $this->_tpl_vars['_head'] = array(
            'title' => ($this->checkTest() ? 'T - ' : '').(!empty($head['title']) ? $head['title'] : I18n::getInstance()->getOther('head_title')),
            'keywords' => !empty($head['keywords']) ? $head['keywords'] : I18n::getInstance()->getOther('head_keywords'),
            'description' => !empty($head['description']) ? $head['description'] : I18n::getInstance()->getOther('head_description'),
            'ico_url' => (!empty($head['ico_url']) ? $head['ico_url'] : '/favicon.ico').'?v='.WEB_COMMON_VERSION,
        );
    }

    /**
     * 记录浏览器类型和版本访问记录
     */
    public function browserLog(){
        if(!$this->_isAjax()){
            $ip = Tools::getRemoteAddr();
            if($ip != $this->_server('SERVER_ADDR')){
                $browser = new Browser();
                $browser_name = $browser->getBrowser();
                $cookie_browser_name = $this->commonCookie('browser_name');
                if($browser_name != $cookie_browser_name){
                    $version_name = $browser->getVersion();
                    LogRwxRecordBrowserModel::model()->MSave(array(
                        'ip' => $ip,
                        'name_id' => VariablesModel::model()->getVarId($browser_name,1,1),
                        'version_id' => VariablesModel::model()->getVarId($version_name,1,2),
                    ));
                    $this->commonCookie('browser_name', $browser_name);
                }
            }
        }
    }

    /**
     * 检测是否本地网络
     */
    public function checkLocalhost(){
        if(!$this->checkTest()){
            $ip = Tools::getRemoteAddr();
            if($ip != '127.0.0.1'){
                $this->sendError();
            }
        }
    }

    public function setTokenCacheData($key, $value){
        $ls_token = $this->getLsToken();
        if(!empty($ls_token)){
            $cache = IRedis::getInstance()->get($this->_sess_prefix.'_ls_token_data_'.$ls_token);
            if(empty($cache)){
                $cache = array();
            }
            $cache[$key] = $value;
            IRedis::getInstance()->setEx($this->_sess_prefix.'_ls_token_data_'.$ls_token, $cache, 3600);
        }
    }

    public function getTokenCacheData($key=null){
        $ls_token = $this->getLsToken();
        if(!empty($ls_token)){
            $cache = IRedis::getInstance()->get($this->_sess_prefix.'_ls_token_data_'.$ls_token);
            if(empty($cache)){
                $cache = array();
            }
            return is_null($key) ? $cache : (isset($cache[$key]) ? $cache[$key] : '');
        }else{
            return '';
        }
    }

    public function commonCookie($name='', $value='', $option=array()){
        $option['prefix'] = 'wx_';
        return Common::cookie($name, $value, $option);
    }

}
