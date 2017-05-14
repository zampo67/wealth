<?php

class ResumeTargetLocationModel extends MBaseModel {
    protected $_table = '{{resume_target_location}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function rules(){
        return parent::rules();
    }

}
