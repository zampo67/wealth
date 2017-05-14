<?php

class QuestionLikeModel extends MBaseModel{
    protected $_table = '{{question_like}}';

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
            'question_id' => array(
                'empty' => array('type' => 'select'),
            ),
        );
    }

    public function MFind($options = array()){
        return parent::MFind($options, 0, 0);
    }

    public function MFindAll($options = array(), $all_num = 0){
        return parent::MFindAll($options, $all_num, 0, 0);
    }

    public function MSave($data, $ctime = 1){
        return parent::MSave($data, $ctime, 0);
    }

    public function itemSave($data){
        return $this->MSave($data);
    }

    public function checkIsLike($user_id, $question_id){
        if(empty($user_id) || !is_numeric($user_id) || empty($question_id) || !is_numeric($question_id)){
            return false;
        }
        $item = $this->MFind(array(
            'field' => 'id',
            'where' => array(
                'user_id' => $user_id,
                'question_id' => $question_id,
                'type_id' => 1
            ),
        ));
        return !empty($item) ? true : false;
    }

}