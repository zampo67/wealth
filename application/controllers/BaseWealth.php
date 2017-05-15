<?php
use \Tools\Browser as Browser;
use \Tools\MobileDetect as MobileDetect;
use \Services\Log\Browser AS BrowserLog;
/**
 * PC端Controller
 */
class BaseWealthController extends BaseController {
    protected $_user = array();
    protected $_check_token = 1;
    protected $_sess_prefix = 'r';
    protected $_mobile_detect = null;
    protected $_is_web = 1;
    Protected $_client_ip = '';

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
            if($this->_is_test == true && $this->_get('htmlTest')==1){
                $user_id = $this->_get('uid', 1);
                $this->_user = UserModel::model()->MGetInfoById($user_id, 'id,mobile,email,i18n_id');
            }else{
                $this->checkLsToken();
            }
            $this->setHtmlHead();
        }
        // PC/移动端检测
        $this->_mobile_detect = new MobileDetect();
        $this->_is_web = ($this->_mobile_detect->isMobile()) ? 0 : 1;
        // 浏览器记录
        $this->browserLog();
        // 设置view路径
        $this->_view_path = PUBLIC_PATH.'/static/cv/';
        $this->_view_page_path = $this->_view_path.'page/';
    }

    public function checkLsToken(){
        $ls_token = $this->getLsToken();
        if(empty($ls_token) || empty(ResumeWebLsTokenModel::model()->checkToken($ls_token))){
            if(!empty(Common::cookie('browser_name'))){
                $ls_token = ResumeWebLsTokenModel::model()->getToken();
                Common::cookie('ls_token', $ls_token);
            }else{
                $this->sendFail(CODE_APP_LS_TOKEN_NOT_EXIST);
            }
        }
        SendLimit::getInstance()->setTokenLimit($ls_token);
    }

    public function getLsToken(){
        return $this->_request('ls_token', Common::cookie('ls_token'));
    }

    public function getSendUserInfo(){
        $this->checkGuide(0);
        return array(
            'headimg_url' => IMAGE_DOMAIN.(!empty($this->_resume['headimgurl']) ? $this->_resume['headimgurl'] : ResumeModel::$default_headimg_url),
            'resume_i18n_id' => $this->_user['i18n_id'],
        );
    }

    public function checkLogin($is_send=1){
        if(empty($this->_user['id'])){
            $sess_id = $this->_request('ls_sess_id');
            if(!empty($sess_id)){
                ISession::getInstance()->sessionId($sess_id, $this->_sess_prefix);
                $user_sess = ISession::getInstance()->get('user');
                if(!empty($user_sess['id'])){
                    $user_info = UserModel::model()->MGetInfoById($user_sess['id'], 'id,mobile,email,i18n_id');
                }
                if(!empty($user_info)){
                    $this->_user = $user_info;
                    ISession::getInstance()->setActive();
                }else{
                    if($is_send == 1){
                        $this->sendFail(CODE_SESSION_TIMEOUT);
                    }else{
                        return false;
                    }
                }
            }else{
                if($is_send == 1){
                    $this->sendFail(CODE_SESSION_TIMEOUT);
                }else{
                    return false;
                }
            }
        }
        return true;
    }

    public function sendFail($error_code = CODE_UNKNOWN_ERROR, $msg = '', $data = array()){
        $data['user_info'] = new stdClass();
        parent::sendFail($error_code, $msg, $data);
    }

    public function sendAndExit($data=array(), $msg='', $send_common_info=1){
        $this->send($data, $msg, $send_common_info);exit;
    }

    public function send($data=array(), $msg='', $send_common_info=1){
        if($send_common_info != 1 && isset($this->_tpl_vars['_head'])){
            unset($this->_tpl_vars['_head']);
        }
        parent::send($data, $msg);
    }

    /**
     * 设置html头部
     * @param array $head 头部信息
     */
    public function setHtmlHead($head=array()){
        $this->_tpl_vars['_head'] = array(
            'title' => ($this->checkTest() ? 'T - ' : '').(!empty($head['title']) ? $head['title'] : '知页 - 年轻人个性化智能求职平台'),
            'keywords' => !empty($head['keywords']) ? $head['keywords'] : '简历模板，个人简历模板，个人简历，简历模板下载，个人简历表格，免费简历模板，求职简历，应届生求职，大学生，找工作，推荐工作',
            'description' => !empty($head['description']) ? $head['description'] : '知页丨中国最受欢迎的简历工具丨智能简历模板帮你找实习找工作',
            'ico_url' => (!empty($head['ico_url']) ? $head['ico_url'] : '/favicon.ico').'?v='.WEB_COMMON_VERSION,
        );
    }

    /**
     * 记录浏览器类型和版本访问记录
     */
    public function browserLog(){
        if(!$this->_isAjax()){
            $this->_client_ip = Tools::getRemoteAddr();
            if($this->_client_ip != $this->_server('SERVER_ADDR')){
                $browser = new Browser();
                $browser_name = $browser->getBrowser();
                $cookie_browser_name = $this->getCacheBrowserName();
                if($browser_name != $cookie_browser_name){
                    $version_name = $browser->getVersion();
                    $browser_log = new BrowserLog($browser_name,$version_name,$this->_client_ip);
                    $browser_log->save();
                    $this->setCacheBrowserName($browser_name);
                }
                if($browser_name=='Internet Explorer' && $this->_server('REQUEST_URI')!='/index/ietips'){
                    $version_arr = explode('.', $browser->getVersion());
                    if(isset($version_arr[0]) && $version_arr[0]<10){
                        $this->sendRedirect('/index/ietips');
                    }
                }
            }
        }
    }

    public function getCacheBrowserName(){
        return Common::cookie('browser_name');
    }

    public function setCacheBrowserName($browser_name){
        return Common::cookie('browser_name', $browser_name);
    }

    /**
     * 检测是否白名单
     * @param string $ip
     */
    public function checkIpWhiteList($ip=''){
        if(!$this->checkTest()){
            parent::checkIpWhiteList($ip);
        }
    }

    /**
     * 检查极验验证码
     */
    public function checkGeeCaptcha(){
        if(empty($this->_post('geetest_validate'))){
            $this->sendFail('captcha', I18n::getInstance()->getErrorController('gee_captcha_wrong'));
        }
        if($this->getTokenCacheData('gt_version') == 3){
            Yaf\Loader::import('GeeTeam3/lib/class.geetestlib.php');
            $GtSdk = new GeetestLib(GEETEST3_CAPTCHA_ID, GEETEST3_PRIVATE_KEY);
            if ($this->getTokenCacheData('gtserver') == 1) {
                $result = $GtSdk->success_validate(
                    $this->_post('geetest_challenge'),
                    $this->_post('geetest_validate'),
                    $this->_post('geetest_seccode'),
                    array(
                        "client_type" => ($this->_is_web == 1) ? 'web' : 'h5', #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                        "ip_address" => Tools::getRemoteAddr() # 请在此处传输用户请求验证时所携带的IP
                    )
                );
                if(empty($result)){
                    $this->sendFail('captcha', I18n::getInstance()->getErrorController('gee_captcha_wrong'));
                }
            }else{
                if (!$GtSdk->fail_validate(
                    $this->_post('geetest_challenge'),
                    $this->_post('geetest_validate'),
                    $this->_post('geetest_seccode')
                )) {
                    $this->sendFail('captcha', I18n::getInstance()->getErrorController('gee_captcha_wrong'));
                }
            }
        }else{
            Yaf\Loader::import('GeeTeam/lib/class.geetestlib.php');
            $GtSdk = new GeetestLib();
            if ($this->getTokenCacheData('gtserver') == 1) {
                $result = $GtSdk->validate($this->_post('geetest_challenge'), $this->_post('geetest_validate'), $this->_post('geetest_seccode'));
                if(empty($result)){
                    $this->sendFail('captcha', I18n::getInstance()->getErrorController('gee_captcha_wrong'));
                }
            } else {
                $this->sendFail('captcha', I18n::getInstance()->getErrorController('gee_captcha_wrong'));
//            if (!$GtSdk->get_answer($this->_post('geetest_validate'))) {
//                $this->sendFail('captcha', I18n::getInstance()->getErrorController('gee_captcha_wrong'));
//            }
            }
        }
    }

    /**
     * 发送手机短信
     * @param int $mobile 手机号码
     * @param string $error_code 错误码
     * @return bool
     */
    public function sendMessage($mobile, $error_code=''){
        if(!empty($mobile) && Common::isMobile($mobile)){
            SendLimit::getInstance()->checkMobileMsg($mobile, $error_code);

            $code = rand(1000,9999);
            $time = time();
            $data = array(
                'mobile' => $mobile,
                'code' => $code,
                'time' => $time,
            );

            $codeData = IRedis::getInstance()->get('mobile_'.$mobile);
            $codeData[] = $data;

            IRedis::getInstance()->setEx('mobile_'.$mobile, $codeData, 600);

            $res = IAlidayu::getInstance()->sendMessageVerifyCode($mobile, $code, '10'.I18n::getInstance()->getOther('minutes'));
            if(!empty($res)){
                SendLimit::getInstance()->setMobileMsg($mobile);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function setTokenCacheData($key, $value){
        $ls_token = $this->getLsToken();
        if(!empty($ls_token)){
            $cache = IRedis::getInstance()->get('tenant_ls_token_data_'.$ls_token);
            if(empty($cache)){
                $cache = array();
            }
            $cache[$key] = $value;
            IRedis::getInstance()->setEx('tenant_ls_token_data_'.$ls_token, $cache, 3600);
        }
    }

    public function getTokenCacheData($key=null){
        $ls_token = $this->getLsToken();
        if(!empty($ls_token)){
            $cache = IRedis::getInstance()->get('tenant_ls_token_data_'.$ls_token);
            if(empty($cache)){
                $cache = array();
            }
            return is_null($key) ? $cache : (isset($cache[$key]) ? $cache[$key] : '');
        }else{
            return '';
        }
    }
}
