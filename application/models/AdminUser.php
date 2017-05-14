<?php

class AdminUserModel extends MBaseModel {
    protected $_table = '{{admin_user}}';
    protected $_password_salts = 'zhiye^%$';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function getPassword($pwd){
        return md5(md5($pwd).$this->_password_salts);
    }

    public function verifyPassword($pwd, $hash){
        $password = $this->getPassword($pwd);
        if($password == $hash){
            return true;
        }
        return false;
    }

    public function login($data, $user_id, $prefix=''){
        return ISession::getInstance()->login($data, $prefix, $user_id);
    }
}