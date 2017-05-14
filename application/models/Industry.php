<?php

class IndustryModel extends MBaseModel {
    protected $_table = '{{industry}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

}
