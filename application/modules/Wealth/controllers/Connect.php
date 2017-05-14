<?php


class ConnectController extends BaseresumeController {
    
    public function init(){
        parent::init();
    }

    public function wxAction(){
        $type = $this->_get('type', 'login');
        if(in_array($type, array('login','bind'))){
            Common::cookie('connect_type', $type);
            if($this->_isWeixin()){
                $scope = 'snsapi_userinfo';
                $this->redirect(IWeiXin::getInstance()->getOauthRedirectUrl(WEIXIN_OPEN_CALLBACK, $scope));
            }else{
                WXOAuth::getInstance()->redirect();
            }
        }else{
            throw new \Yaf\Exception();
        }
    }

    public function wxcallbackAction(){
        $wx_unionid = $wx_info = '';
        if($this->_isWeixin()){
            $code = $this->_get('code');
            if(!empty($code)){
                $wx_public_oauth_info = IWeiXin::getInstance()->getOauthAccessToken($code);
                if(!empty($wx_public_oauth_info->unionid)){
                    $wx_unionid = $wx_public_oauth_info->unionid;
                    $wx_info = IWeiXin::getInstance()->getOauthUserInfo(
                        $wx_public_oauth_info->access_token,
                        $wx_public_oauth_info->openid
                    );
                }
            }
        }else{
            if(empty(WXOAuth::getInstance()->handleResponse())){
                throw new \Yaf\Exception();
            }
            $wx_unionid = WXOAuth::getInstance()->getUnionid();
            $wx_info = WXOAuth::getInstance()->getUserInfo();
        }
        if(!empty($wx_unionid)){
            $type = Common::cookie('connect_type');
            switch ($type){
                case 'login':
                    $user_info = UserModel::model()->MFind(array(
                        'field' => 'id,mobile,email,wx_unionid,qq_unionid',
                        'where' => array('wx_unionid'=>$wx_unionid),
                    ));
                    if(!empty($user_info)){
                        //登录
                        $sess_id = UserModel::model()->login(array(
                            'user' => array('id' => $user_info['id']),
                        ), $user_info['id'], $this->_sess_prefix);

                        if(!empty($sess_id)){
                            $save_data['id'] = $user_info['id'];
                            $save_data['wx_nickname'] = !empty($wx_info['nickname']) ? $wx_info['nickname'] : '';
                            if($this->_isWeixin()){
                                $save_data['wx_openid_public_b'] = $wx_info['openid'];
                            }else{
                                $save_data['wx_openid_web_b'] = $wx_info['openid'];
                            }
                            UserModel::model()->MSave($save_data);

                            Common::cookie('ls_sess_id', $sess_id);
                            $redirect_url = '/';
                        }else{
                            throw new \Yaf\Exception();
                        }
                    }else{
                        //注册
                        $register_data = array(
                            'wx_unionid' => $wx_unionid,
                            'wx_nickname' => !empty($wx_info['nickname']) ? $wx_info['nickname'] : '',
                            'register_plat_type_id' => ($this->_is_web == 1) ? VariablesModel::model()->getAttrId('platType','web_pc') : VariablesModel::model()->getAttrId('platType','web_mobile'),
                            'register_type_id' => VariablesModel::model()->getAttrId('registerType', 'wx'),
                            'register_site_type_id' => VariablesModel::model()->getAttrId('siteType', 'business'),
                        );
                        if($this->_isWeixin()){
                            $register_data['wx_openid_public_b'] = $wx_info['openid'];
                        }else{
                            $register_data['wx_openid_web_b'] = $wx_info['openid'];
                        }

                        $user_id = UserModel::model()->MSave($register_data);
                        if(!empty($user_id)){
                            //登录
                            $sess_id = UserModel::model()->login(array(
                                'user' => array('id' => $user_id),
                            ), $user_id, $this->_sess_prefix);

                            if(!empty($sess_id)){
                                Common::cookie('ls_sess_id', $sess_id);
                                $redirect_url = '/';
                            }else{
                                throw new \Yaf\Exception();
                            }
                        }else{
                            throw new \Yaf\Exception();
                        }
                    }
                    break;
                default:
                    throw new \Yaf\Exception();
                    break;
            }
            Common::cookie('connect_type', null);
            $this->redirect($redirect_url);
        }else{
            echo '登录失败，请刷新后重试';
        }
        exit;
    }

    public function qqAction(){
        $type = $this->_get('type');
        if(in_array($type, array('login','bind'))){
            Common::cookie('connect_type', $type);
            QQOAuth::redirect();
        }else{
            throw new \Yaf\Exception();
        }
    }

    public function qqcallbackAction(){
        $qq_oauth_info = QQOAuth::login();
        p_e($qq_oauth_info);
    }

}
