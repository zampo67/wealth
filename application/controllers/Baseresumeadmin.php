<?php

/**
 * PC端后台Controller
 */
class BaseresumeadminController extends BaseController{
    protected $_user = array();
    protected $_request_param = array();
    protected $_mobile_detect = null;
    protected $_is_web = 1;
    protected $_check_token = 1;
    protected $_sess_prefix = 'radm';

    public function init() {
        // 配置国际化文件目录
        $this->setI18nPath();
        parent::init();
        // options请求的判断
        if($this->getRequest()->getMethod() == 'OPTIONS'){
            $this->send();exit;
        }
        $this->_request_param = $this->_request();
        // 日志记录
        Log::request('req|head', json_encode($this->_server()));
        Log::request('req|uri ', $this->_server('HTTP_HOST').$this->_server('REQUEST_URI'));
        Log::request('req|body', http_build_query($this->_request_param));
//        $this->setHtmlHead();
        // 检查token
        if($this->_check_token == 1){
            $this->checkLsToken();
        }
        // PC/移动端检测
        $this->_mobile_detect = new MobileDetect();
        $this->_is_web = ($this->_mobile_detect->isMobile()) ? 0 : 1;
        // 浏览器记录
//        $this->browserLog();
        // 设置view路径
        $this->_view_path = PUBLIC_PATH.'/static/cvadmin/';
        $this->_view_page_path = $this->_view_path.'page/';

        if($this->checkTest() && $this->_get('htmlTest')==1){
            $this->_user['id'] = $this->_get('uid', 1);
        }
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

    public function checkLsToken(){
        $ls_token = $this->getLsToken();
        if(empty($ls_token) || empty(ResumeWebLsTokenModel::model()->checkToken($ls_token))){
            $this->sendFail(CODE_APP_LS_TOKEN_NOT_EXIST);
        }else{
            SendLimit::getInstance()->setTokenLimit($ls_token);
        }
    }

    public function getLsToken(){
        return $this->_request('ls_token');
    }

    public function checkLogin($is_send=1){
        if(empty($this->_user['id'])){
            $sess_id = $this->_request('ls_sess_id');
            if(empty($sess_id)){
                if($is_send == 1){
                    $this->sendFail(CODE_SESSION_TIMEOUT, I18n::getInstance()->getErrorController('session_timeout'));
                }else{
                    return false;
                }
            }

            ISession::getInstance()->sessionId($sess_id, $this->_sess_prefix);
            $user_info = ISession::getInstance()->get('user');
            if(empty($user_info['id']) || !is_numeric($user_info['id'])){
                if($is_send == 1){
                    $this->sendFail(CODE_SESSION_TIMEOUT, I18n::getInstance()->getErrorController('session_timeout'));
                }else{
                    return false;
                }
            }
            ISession::getInstance()->setActive();
            $this->_user = AdminUserModel::model()->MFind(array(
                'field' => 'id,username',
                'where' => array('id' => $user_info['id']),
            ));
        }
        return true;
    }

    public function refreshThisUser(){
        $this->_user = AdminUserModel::model()->MFind(array(
            'field' => 'id,username',
            'where' => array('id' => $this->_user['id']),
        ));
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
                $cookie_browser_name = Common::cookie('browser_name');
                if($browser_name != $cookie_browser_name){
                    $version_name = $browser->getVersion();
                    LogRsRecordBrowserModel::model()->MSave(array(
                        'ip' => $ip,
                        'name_id' => VariablesModel::model()->getVarId($browser_name,1,1),
                        'version_id' => VariablesModel::model()->getVarId($version_name,1,2),
                    ));
                    Common::cookie('browser_name', $browser_name);
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

    public function getTimeInterval($time_type_id){
        $end_time = time();
        switch($time_type_id){
            case 1://最近7天
                $start_time = strtotime(date('Y-m-d'))-6*86400;
                break;
            case 2:
                $start_time = strtotime(date('Y-m-d'))-30*86400;
                break;
            case 3:
                $start_time = strtotime('-5 month',strtotime(date('Y-m-01')));
                break;
            case 99:
                $start_time = strtotime($this->_request('start_time'));
                $end_time = strtotime($this->_request('end_time'));
                if(empty($start_time) || empty($end_time)){
                    $this->sendFail(CODE_ACTION_NOT_ALLOWED, I18n::getInstance()->getErrorController('time_select_empty'));
                }
                if($end_time<=$start_time){
                    $this->sendFail(CODE_ACTION_NOT_ALLOWED, I18n::getInstance()->getErrorController('end_time_error'));
                }
                break;
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED, I18n::getInstance()->getErrorController('parameter_error'));
                $start_time = '';
                break;
        }

        return array(
            'start_time' => $start_time,
            'end_time' => $end_time,
            'time_type_id' => $time_type_id
        );
    }
}