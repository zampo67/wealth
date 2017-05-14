<?php

class UserController extends BaseresumeController {
    protected $user_id = 0;
    protected $resume_id = 0;
    protected $resume_i18n_id = 0;

    public function init(){
        parent::init();
        $this->checkLogin();
        $this->user_id = $this->_user['id'];
    }

    public function changeResumeI18nIdAction(){
        $i18n_id = $this->_get('i18n_id');
        if(!empty($i18n_id) && in_array($i18n_id, array_column(VariablesModel::model()->getAttrs('i18n'), 'id'))){
            UserModel::model()->MSave(array(
                'id' => $this->user_id,
                'i18n_id' => $i18n_id,
            ));
            LogUserChangeI18nModel::model()->MSave(array(
                'user_id' => $this->user_id,
                'to_i18n_id' => $i18n_id,
            ));
            $this->send(array(
                'resume_info' => array(
                    'id' => 0,
                    'i18n_id' => $i18n_id
                ),
            ));
        }else{
            $this->sendError();
        }
    }

}
