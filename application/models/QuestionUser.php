<?php

class QuestionUserModel extends MBaseModel{
    protected $_table = '{{question_user}}';

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

    public function itemInfo($id){
        if(empty($id) || !is_numeric($id)){
            return false;
        }

        $item = $this->MFind(array(
            'field' => 'user_name,headline,headimg_url,bgimg_url,introduction',
            'where' => array('id' => $id)
        ));
        if(!empty($item)){
            if(!empty($item['headimg_url'])){
                $item['headimg_url'] = IMAGE_DOMAIN.$item['headimg_url'];
            }
            if(!empty($item['bgimg_url'])){
                $item['bgimg_url'] = IMAGE_DOMAIN.$item['bgimg_url'];
            }
            return $item;
        }else{
            return false;
        }
    }
    
}