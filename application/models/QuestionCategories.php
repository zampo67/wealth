<?php

class QuestionCategoriesModel extends MBaseModel{
    protected $_table = '{{question_categories}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function rules(){
        return array(

        );
    }
    
}