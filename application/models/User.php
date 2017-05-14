<?php

class UserModel extends MBaseModel {
    protected $_table = '{{user}}';
    protected $_password_salts = 'zhiye^%$';

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
        return array();
    }

    public function getBindByEmail($email, $field='id'){
        return (empty($email) || !Common::isEmail($email)) ? false : $this->MFind(array(
            'field' => $field,
            'where' => array('email'=>trim($email)),
        ));
    }

    public function getBindByMobile($mobile, $field='id'){
        return (empty($mobile) || !Common::isMobile($mobile)) ? false : $this->MFind(array(
            'field' => $field,
            'where' => array('mobile'=>trim($mobile)),
        ));
    }

    public function getPassword($pwd){
        return md5(md5($pwd).$this->_password_salts);
    }

    public function verifyPassword($pwd, $check){
        return ($this->getPassword($pwd) == $check) ? true : false;
    }

    public function login($data, $user_id, $prefix=''){
        return ISession::getInstance()->login($data, $prefix, $user_id);
    }

    public function logout($sess_id, $prefix=''){
        ISession::getInstance()->sessionId($sess_id, $prefix);
        $user_info = ISession::getInstance()->get('user');
        $user_id = !empty($user_info['id']) ? $user_info['id'] : 0;
        ISession::getInstance()->logout($user_id);
    }

}
