<?php

class CompanyModel extends MBaseModel {
    protected $_table = '{{company}}';
    public static $default_logo_url = '/static/images/common/web_company_default.jpg';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

}
