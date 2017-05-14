<?php

class WxQuestionGetnewSubscribeModel extends MBaseModel{
    protected $_table = '{{wx_question_getnew_subscribe}}';
    protected $_public_id = '';

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

    public function setTableByPublicId($public_id){
        if(isset($public_id) && is_numeric($public_id)){
            $this->setTable('wx_question_getnew_subscribe_'.$public_id);
        }
    }

    public function setPublicId($public_id){
        if(!empty($public_id) && is_numeric($public_id)){
            $this->_public_id = $public_id;
        }
    }

    public function setTableAndPublicId($public_id){
        $this->setPublicId($public_id);
        $this->setTableByPublicId($public_id);
    }

    public function checkIsLink($wx_user_id, $subscribe_wx_user_id){
        return $this->MFind(array(
            'field' => 'id',
            'where' => array(
                'subscribe_wx_user_id' => $subscribe_wx_user_id,
            ),
        ));
    }

    public function saveLink($wx_user_id, $subscribe_wx_user_id,$question_compilation_id){
        return $this->MSave(array(
            'wx_user_id' => $wx_user_id,
            'subscribe_wx_user_id' => $subscribe_wx_user_id,
            'question_compilation_id'=>$question_compilation_id
        ));
    }

    public function getLinkNum($wx_user_id,$question_compilation_id){
        $item = $this->MFind(array(
            'field' => 'COUNT(1) AS num',
            'where' => array('wx_user_id' => $wx_user_id,'question_compilation_id'=>$question_compilation_id),
        ));
        return !empty($item['num']) ? $item['num'] : 0;
    }

}