<?php

class UserModel extends MBaseModel {
    protected $_table = '{{user}}';
    protected $_password_salts = 'wealth^%$';

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
        return array(
             'mobile' => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'mobile' => array()
            ),
            'password' => array(
                'filter_func' => array('trim'),
                'empty' => array(),
                'min_length' => array( 'length' => 6),
                'max_length' => array( 'length' => 16),
            ),
        );
    }

    public function getPassword($pwd){
        return password_hash($pwd.$this->_password_salts, PASSWORD_DEFAULT);
    }

    public function verifyPassword($pwd, $hash){
        return password_verify($pwd.$this->_password_salts, $hash);
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
