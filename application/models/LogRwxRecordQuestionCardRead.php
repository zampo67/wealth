<?php
class LogRwxRecordQuestionCardReadModel extends MBaseModel{
    protected $_table = '{{log_rwx_record_question_card_read}}';

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
            'card_id' => array(
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

    public function itemSave($data){
        if(empty($data['user_id']) || empty($data['question_id'])){
            return false;
        }

        $check = $this->MFind(array(
            'field' => 'id,card_num,card_ids',
            'where' => array(
                'user_id' => $data['user_id'],
                'question_id' => $data['question_id'],
            )
        ));

        if(empty($check)){
            $data['card_ids'] = ','.$data['card_ids'].',';
            $data['is_reg_today_first'] = $data['is_first'] = 0;
            if(empty($this->MFind(array(
                'field' => 'id',
                'where' => array('user_id' => $data['user_id'])
            )))){
                $data['is_first'] = 1;
                if(!empty(LogRwxRecordQuestionViewModel::model()->checkIsRegToday($data['user_id']))){
                    $data['is_reg_today_first'] = 1;
                }
            }
        }else{
            $data['id'] = $check['id'];
            $data['card_ids'] = $check['card_ids'].$data['card_ids'].',';
            if($data['card_num'] <= $check['card_num']){
                unset($data['card_num']);
            }
            unset($data['user_id']);unset($data['question_id']);
        }
        return $this->MSave($data);
    }

}