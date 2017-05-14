<?php
class LogRwxRecordQuestionReadModel extends MBaseModel{
    protected $_table = '{{log_rwx_record_question_read}}';

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

    public function MFindAll($options = array(), $all_num=0){
        return parent::MFindAll($options, $all_num, 0, 0);
    }

    public function MSave($data, $ctime = 1){
        return parent::MSave($data, $ctime, 0);
    }

    public function itemSave($data){
        if(empty($data['id'])){
            if(empty($data['user_id']) || empty($data['question_id'])){
                return false;
            }
            $data['is_reg_today_first'] = $data['is_first'] = 0;
            if(empty($this->MFind(array(
                'field' => 'id',
                'where' => array('user_id' => $data['user_id'])
            )))){
                $data['is_first'] = 1;
                if(!empty(LogRwxRecordQuestionViewModel::model()->checkIsRegToday($data['user_id']))){
                    $data['is_reg_today_first'] = 1;
                }
                QuestionModel::model()->MPlusField('read_sum', $data['question_id']);
            }
        }
        return $this->MSave($data);
    }

}