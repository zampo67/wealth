<?php

class LinkRedirectModel extends MBaseModel {
    protected $_table = '{{link_redirect}}';

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

        );
    }

}