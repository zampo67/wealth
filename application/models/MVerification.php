<?php

class MVerificationModel extends MBaseModel {
    protected $_table = '';
    protected $_rules = array();

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    /**
     * Model规则
     * @return array
     */
    public function rules(){
        return $this->_rules;
    }

    public function setRulesByModule($module=''){
        switch ($module){
            case 'resume_login':
                $this->_rules = array(
                    'username' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_login_username')),
                    ),
                    'password' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                );
                break;
            case 'resume_mobile_register':
                $this->_rules = array(
                    'mobile' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorCommon('mobile')),
                        'mobile' => array(),
                    ),
                    'password' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                    'mobile_code' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_mobile_code')),
                        'min_length' => array('length' => 4, 'msg' => I18n::getInstance()->getErrorController('format_wrong_mobile_code')),
                        'max_length' => array('length' => 4, 'msg' => I18n::getInstance()->getErrorController('format_wrong_mobile_code')),
                    ),
                );
                break;
            case 'resume_email_register':
                $this->_rules = array(
                    'email' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorCommon('email')),
                        'email' => array(),
                    ),
                    'password' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                );
                break;
            case 'resume_reset_password':
                $this->_rules =  array(
                    'password' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                    're_password' => array(
                        'empty' => array('user_error' => '1'),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                );
                break;
            case 'resume_set_password':
                $this->_rules = array(
                    'old_password' => array(
                        'empty' => array('type' => 'input'),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                    'new_password' => array(
                        'empty' => array('type' => 'input'),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                );
                break;
            case 'resume_wx_bind_user':
                $this->_rules = array(
                    'username' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_login_username')),
                    ),
                    'password' => array(
                        'empty' => array('msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'min_length' => array('length' => 6, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                        'max_length' => array('length' => 16, 'msg' => I18n::getInstance()->getErrorController('format_wrong_password')),
                    ),
                );
                break;
            case 'resume_guide':
                $resume_rules = ResumeModel::model()->rules();
                $resume_edu_rules = ResumeEduModel::model()->rules();
                $this->_rules = array(
                    'i18n_id' => array(
                        'empty' => array('type' => 'select'),
                        'in_array' => array('array' => VariablesModel::model()->getAttrId('i18n'))
                    ),
                    'username' => $resume_rules['username'],
                    'sex' => $resume_rules['sex'],
                    'school_id' => $resume_edu_rules['school_id'],
                    'school_name' => $resume_edu_rules['school_name'],
                    'major_name' => $resume_edu_rules['major_name'],
                    'start_time' => $resume_edu_rules['start_time'],
                    'end_time' => $resume_edu_rules['end_time'],
                    'degree_id' => $resume_edu_rules['degree_id'],
                    'location_prov_id' => $resume_rules['location_prov_id'],
                    'location_city_id' => $resume_rules['location_city_id'],
                    'email' => $resume_rules['email'],
                    'mobile' => $resume_rules['mobile'],
                );
                break;
        }
        $this->setTable($module);
    }

}