<?php

/**
 * 基础Controller
 */
class BaseController extends Yaf\Controller_Abstract {
    protected $_config;
    protected $_layouts = 'main';
    protected $_tpl_vars = array();
    protected $_layouts_vars = array();
    protected $_ajax = 0;
    protected $_view_path = '';
    protected $_view_page_path = '';
    protected $_is_test = false;

    public function init() {
        $this->_config = Yaf\Registry::get('config');
        Yaf\Dispatcher::getInstance()->disableView();

        $this->checkTest();
    }

    public function checkTest(){
        if(DOMAIN === TEST_DOMAIN && DEBUG_MODE === true){
            $this->_is_test = true;
        }
        return $this->_is_test;
    }

    public function setResourcesPath(){
        defined('RESOURCES_PATH') or define('RESOURCES_PATH', APPLICATION_PATH.'/application/modules/'.$this->getModuleName().'/resources/');
    }

    public function setI18nPath(){
        $this->setResourcesPath();
        defined('I18N_PATH') or define('I18N_PATH', RESOURCES_PATH.'/i18n/');
    }

    public function checkIpWhiteList($ip=''){
        $check = false;
        if(file_exists(RESOURCES_PATH.'ip_white_list.json')){
            $ip_white_list = json_decode(file_get_contents(RESOURCES_PATH.'ip_white_list.json'), true);
            if(!empty($ip_white_list)){
                if(empty($ip)){
                    $ip = Tools::getRemoteAddr();
                }
                if(in_array($ip, $ip_white_list)){
                    $check = true;
                }
            }
        }
        if($check === false){
            die('access denied');
        }
    }

    public function _get($field, $default_value=''){
        return $this->getRequest()->getQuery($field, $default_value);
    }

    public function _get_decrypt($field, $default_value='', $type=''){
        $value = $this->_get($field, $default_value);
        if($this->checkTest()){
            if(!is_numeric($value)){
                $value = Rijndael::decrypt($value, $type);
            }
        }else{
            $value = Rijndael::decrypt($value, $type);
        }
        return $value;
    }
    
    public function _post($field, $default_value=''){
        return $this->getRequest()->getPost($field, $default_value);
    }

    public function _post_decrypt($field, $default_value='', $type=''){
        $value = $this->_post($field, $default_value);
        if($this->checkTest()){
            if(!is_numeric($value)){
                $value = Rijndael::decrypt($value, $type);
            }
        }else{
            $value = Rijndael::decrypt($value, $type);
        }
        return $value;
    }

    public function _request($field='', $default_value=''){
        return empty($field) ? $this->getRequest()->getRequest() : $this->getRequest()->getRequest($field, $default_value);
    }

    public function _files(){
        return $this->getRequest()->getFiles();
    }

    public function _requestBody(){
        return file_get_contents("php://input");
    }

    public function _isAjax(){
        return $this->getRequest()->isXmlHttpRequest();
    }

    public function _isWeixin(){
        return (strpos($this->_server('HTTP_USER_AGENT'), 'MicroMessenger') !== false) ? true : false;
    }

    public function _server($name=null, $default=null){
        return $this->getRequest()->getServer($name, $default);
    }

    public function assign($name='', $value=''){
        $this->getView()->assign($name, $value);
    }
    
    public function display($view='', $tpl_vars=array()){
        echo $this->render($view, $tpl_vars);
        exit;
    }

    public function displayPartial($view='', $tpl_vars=array()){
        echo $this->renderPartial($view, $tpl_vars);
        exit;
    }

    public function displayIndex(){
        if(!empty($this->_view_path)){
            echo $this->getView()->render($this->_view_path.'index.'.$this->_config->application->view->ext);
        }
        exit;
    }

    public function displayPage($view){
        if(!empty($this->_view_page_path)){
            echo $this->getView()->render($this->_view_page_path.$view.'.'.$this->_config->application->view->ext);
        }
        exit;
    }

    public function displayUpgrading(){
        echo $this->getView()->render($this->getView()->getScriptPath().'/page/upgrading.'.$this->_config->application->view->ext);exit;
    }

    public function render($view='', $tpl_vars=array()){
        $content = $this->renderPartial($view, $tpl_vars);
        $this->assign('content', $content);
        if(!empty($this->_layouts_vars)){
            $this->assign('_layouts_vars', $this->_layouts_vars);
        }
        $tpl = $this->getTpl($this->_layouts, 'layouts');
        return $this->getView()->render($tpl);
    }

    public function renderPartial($view='', $vars=array()){
        $tpl = $this->getTpl($view);

        if(!empty($this->_tpl_vars)){
            foreach($this->_tpl_vars as $k=>$v){
                $this->assign($k, $v);
            }
        }

        if(!empty($vars)){
            foreach($vars as $k=>$v){
                $this->assign($k, $v);
            }
        }
        return $this->getView()->render($tpl);
    }

    public function renderMsg($view='',$vars=array()){
        $tpl = $this->getTpl($view, 'layouts');
        if(!empty($vars)){
            foreach($vars as $k=>$v){
                $this->assign($k, $v);
            }
        }
        return $this->getView()->render($tpl);
    }

    public function _success_msg($message, $jumpUrl='javascript:history.go(-1)',$waitSecond = 3){
        $data['message'] = $message;
        $data['status'] = TRUE;
        $data['jumpUrl'] = $jumpUrl;
        $data['waitSecond'] = $waitSecond;

        echo $this->renderMsg('msg',$data);exit();

    }

    public function _error_msg($message, $jumpUrl='javascript:history.go(-1)', $waitSecond = 3){
        $data['message'] = $message;
        $data['status'] = FALSE;
        $data['jumpUrl'] = $jumpUrl;
        $data['waitSecond'] = $waitSecond;

        echo $this->renderMsg('msg',$data);exit();
    }

    private function getTpl($view='', $controller_name=''){
        $view_path = $this->getView()->getScriptPath();
        $controller_name = empty($controller_name) ? strtolower($this->getRequest()->getControllerName()) : $controller_name;
        $view = empty($view) ? $this->getRequest()->getActionName() : $view;
        $view_ext = $this->_config->application->view->ext;

        $tpl = $view_path . '/' . $controller_name . '/' . $view . '.' . $view_ext;
        return $tpl;
    }

    public function redirect($url){
        header('Location: '.$url);
        exit;
    }

    /**
     * 成功响应
     * @param array $data 响应数据
     * @param string $msg 响应信息
     */
    public function send($data=array(), $msg=''){
        if(isset($this->_tpl_vars['_head'])){
            $data['head'] = $this->_tpl_vars['_head'];
        }
        Common::sendSuccessRes($data, $msg);
    }

    /**
     * 成功响应并退出
     * @param array $data 响应数据
     * @param string $msg 响应信息
     */
    public function sendAndExit($data=array(), $msg=''){
        $this->send($data, $msg);exit;
    }

    /**
     * 失败响应
     * @param int $error_code 错误码
     * @param string $msg 错误信息
     * @param array $data 额外数据
     */
    public function sendFail($error_code=CODE_UNKNOWN_ERROR, $msg='', $data=array()){
        Common::sendFailRes($error_code, $msg, $data);
    }

    /**
     * 失败响应,404
     * @param string $msg 错误信息
     * @param array $data 额外数据
     */
    public function sendError($msg='', $data=array()){
        $this->sendFail(CODE_NOT_FOUND, $msg, $data);
    }

    /**
     * 跳转响应
     * @param string $url 跳转链接
     * @param string $msg 错误信息
     * @param array $data 额外数据
     */
    public function sendRedirect($url, $msg='', $data=array()){
        $data['url'] = $url;
        $this->sendFail(CODE_ERROR_REDIRECT, $msg, $data);
    }

    /**
     * 验证model的字段规则
     * @param string $class model类名
     * @param array $data 数据(引用传递,所以调用时必须要定义一个变量)
     * @param array|string $options 配置项(enable=>启用验证的字段,disable=>关闭验证的字段)
     * @param array $rule 覆盖规则
     */
    public function verificationModelRules($class, &$data, $options=array(), $rule=array()){
        if(empty($class)){
            $class = 'MVerificationModel';
        }
        if($class == 'MVerificationModel'){
            if(is_string($options)){
                MVerificationModel::model()->setRulesByModule($options);
            }elseif(isset($options['_module'])){
                MVerificationModel::model()->setRulesByModule($options['_module']);
            }
        }
        $res = $class::model()->MVerificationRules($data, $options, $rule);
        if(!empty($res['code'])){
            $this->sendFail($res['code'], $res['msg']);
        }
    }

    public function checkLAN(){
        if(DOMAIN != TEST_DOMAIN){
            $this->checkIpWhiteList();
        }
    }

}
