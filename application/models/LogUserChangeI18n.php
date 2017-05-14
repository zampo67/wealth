<?php
class LogUserChangeI18nModel extends MBaseModel{
    protected $_table = '{{log_user_change_i18n}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function MSave($data, $ctime = 1){
        return parent::MSave($data, $ctime, 0);
    }

}