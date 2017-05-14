<?php


class SiteController extends BaseresumewxController {
    
    public function init(){
        parent::init();
        $this->checkLogin();
    }

    public function wxRegister(){
        if($this->checkBind(0)){
            $this->sendError();
        }

        $res = false;
        $type = $this->_post('type');
        switch ($type){
            case 'bind':
                $data = $this->_request();
                $this->verificationModelRules('', $data, 'resume_wx_bind_user');

                if(Common::isMobile($data['username'])){
                    $user_info = UserModel::model()->getBindByMobile($data['username'], 'id,wx_unionid,password');
                    $username_msg_key = 'mobile_is_not_register';
                }elseif(Common::isEmail($data['username'])){
                    $user_info = UserModel::model()->getBindByEmail($data['username'], 'id,wx_unionid,password');
                    $username_msg_key = 'email_is_not_register';
                }else{
                    $this->sendFail('username', I18n::getInstance()->getErrorController('format_wrong_login_username'));
                }

                if(empty($user_info)){
                    $this->sendFail('username', I18n::getInstance()->getErrorController($username_msg_key));
                }

                if(!UserModel::model()->verifyPassword($data['password'], $user_info['password'])){
                    $this->sendFail('password', I18n::getInstance()->getErrorController('username_or_password_wrong'));
                }
                if(!empty($user_info['wx_unionid'])){
                    $this->sendFail('username', I18n::getInstance()->getErrorController('user_has_bind_wx'));
                }

                $res = UserModel::model()->MSave(array(
                    'id' => $user_info['id'],
                    'wx_unionid' => $this->_wx_user['unionid'],
                    'wx_openid_public' => $this->_wx_user['openid'],
                    'wx_nickname' => $this->_wx_user['nickname'],
                ));
                break;
            case 'cancel':
                $res = UserModel::model()->MSave(array(
                    'wx_unionid' => $this->_wx_user['unionid'],
                    'wx_openid_public' => $this->_wx_user['openid'],
                    'wx_nickname' => $this->_wx_user['nickname'],
                    'register_type_id' => VariablesModel::model()->getAttrs('registerType', 'wx', 'id'),
                    'register_plat_type_id' => VariablesModel::model()->getAttrs('registerPlatType', 'web_mobile', 'id'),
                    'register_module_type_id' => VariablesModel::model()->getAttrs('registerModuleType', 'question', 'id'),
                ));
                break;
            default:
                $this->sendFail(CODE_FORMAT_WRONG_FIELD);
                break;
        }
        if(!empty($res)){
            LogRwxRecordQuestionViewModel::model()->itemSave(array(
                'wx_user_id' => $this->_wx_user['id'],
                'user_id' => $res,
                'url_type_id' => VariablesModel::model()->getAttrs('resumeQuestionUrlType', 'site_wx_register', 'id'),
                'url_param' => !empty($this->_request('ls_wx_uri')) ? urldecode($this->_request('ls_wx_uri')) : $this->_server('REQUEST_URI'),
                'link_id' => 0,
                'link_type_id' => 0,
            ));
            $this->send();
        }else{
            $this->sendError();
        }
    }

}
