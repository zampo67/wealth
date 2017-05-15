<?php

class SiteController extends BaseWealthController {

    public function init(){
        parent::init();
    }

    private function checkTestLimit($username, $error_code=''){
        if($this->checkTest() && empty(ZUserTestModel::model()->checkLimit($username))){
            $this->sendFail($error_code, I18n::getInstance()->getErrorController('account_not_allow'));
        }
    }

    // 接口-公共-检测是否登录
    public function checkLoginAction(){
        if($this->checkLogin(0)){
            $this->send(array(
                'user_info' => $this->getSendUserInfo(),
                'from_check_login' => 1,
            ));
        }else{
            $this->sendFail(CODE_USER_NOT_LOGIN, '', array('from_check_login' => 1));
        }
    }

    // 接口-极验验证启动
    public function geeStartAction(){
        Yaf\Loader::import('GeeTeam/lib/class.geetestlib.php');
        $GtSdk = new GeetestLib();
        $return = $GtSdk->register();
        $this->setTokenCacheData('gt_version', 2);
        if ($return) {
            $this->setTokenCacheData('gtserver', 1);
            $this->send(array(
                'success' => 1,
                'gt' => CAPTCHA_ID,
                'challenge' => $GtSdk->challenge
            ));
        }else{
            $this->setTokenCacheData('gtserver', 0);
            $rnd1 = md5(rand(0,100));
            $rnd2 = md5(rand(0,100));
            $challenge = $rnd1 . substr($rnd2,0,2);
            $result = array(
                'success' => 0,
                'gt' => CAPTCHA_ID,
                'challenge' => $challenge
            );
            $this->setTokenCacheData('challenge', $result['challenge']);
            $this->send($result);
        }
    }

    public function geeStart3Action(){
        Yaf\Loader::import('GeeTeam3/lib/class.geetestlib.php');
        $GtSdk = new GeetestLib(GEETEST3_CAPTCHA_ID, GEETEST3_PRIVATE_KEY);
        $status = $GtSdk->pre_process(array(
            "client_type" => ($this->_is_web == 1) ? 'web' : 'h5', #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => Tools::getRemoteAddr() # 请在此处传输用户请求验证时所携带的IP
        ), 1);
        $response = $GtSdk->get_response();
        $this->setTokenCacheData('gtserver', $status);
        $this->setTokenCacheData('gt_version', 3);
        $this->send($response);
    }
    
    // 接口-登录
    public function loginAction(){
        $data = $this->_request();
        $this->verificationModelRules('UserModel', $data,array('enable'=>array('mobile','password')));

        $this->checkTestLimit($data['mobile'], 'mobile');
        $user_info = UserModel::model()->MFind(array(
            'field' => 'id,mobile,password,i18n_id',
            'where' => array(
                'mobile' => $data['mobile']
            )
        ));

        if(empty($user_info)){
            $this->sendFail('username', I18n::getInstance()->getErrorController('mobile_is_not_register'));
        }

        if(!UserModel::model()->verifyPassword($data['password'], $user_info['password'])){
            $this->sendFail('password', I18n::getInstance()->getErrorController('mobile_or_password_wrong'));
        }

        //用户登录操作
        $sess_id = UserModel::model()->login(array(
            'user' => array(
                'id' => $user_info['id'],
                'mobile' => $user_info['mobile'],
                'mobile_security' => $user_info['mobile']
            ),
        ), $user_info['id'], $this->_sess_prefix);
        if(!empty($sess_id)){
            $this->_user['id'] = $user_info['id'];
            $this->_user['i18n_id'] = $user_info['i18n_id'];

            $this->send(array(
                'ls_sess_id' => $sess_id,
                'ls_sess_expire' => ($this->_request('is_auto_login') == 1) ? 30 : 0
            ));
        }else{
            $this->sendFail('password', I18n::getInstance()->getErrorController('fail_login'));
        }
    }

    // 接口-退出登录
    public function logoutAction(){
        $sess_id = $this->_request('ls_sess_id');
        if(!empty($sess_id)){
            UserModel::model()->logout($sess_id, $this->_sess_prefix);
        }
        $this->send();
    }
}
