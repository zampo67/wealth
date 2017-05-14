<?php

class QuestionBannerModel extends MBaseModel{
    protected $_table = '{{question_banner}}';

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

    public function getList(){
        $list = $this->MFindAll(array(
            'field' => "href,href_type,CONCAT('".IMAGE_DOMAIN."',image_url) AS image_url",
            'order' => 'sort ASC',
        ));
        return !empty($list) ? $list : array();
    }

}