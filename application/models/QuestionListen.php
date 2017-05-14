<?php

class QuestionListenModel extends MBaseModel{
    protected $_table = '{{question_listen}}';
    protected $_table_question = '{{question}}';

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

    public function MFind($options = array()){
        return parent::MFind($options, 0, 0);
    }

    public function MFindAll($options = array(), $all_num = 0){
        return parent::MFindAll($options, $all_num, 0, 0);
    }

    public function MSave($data, $ctime = 1){
        return parent::MSave($data, $ctime, 0);
    }

    public function getListByUserId($user_id){
        $list = array();
        if(!empty($user_id) && is_numeric($user_id)){
            $list = $this->MFindAllBySql(
                "SELECT q.id,q.title,q.voice_url,q.voice_time,q.card_sum
                 FROM {$this->_table} AS ql
                 LEFT JOIN {$this->_table_question} AS q ON q.id=ql.question_id
                 WHERE ql.user_id=:user_id
                 ORDER BY id DESC"
                , array(':user_id' => $user_id));
            if(!empty($list)){
                foreach ($list as &$item){
                    $item['is_listen'] = 1;
                    $item['voice_time'] = !empty($item['voice_url']) ? QuestionModel::model()->getMinute($item['voice_time']) : '';
                    $item['card_minutes'] = !empty($item['card_sum']) ? ceil($item['card_sum']/4) : '';

                    unset($item['voice_url']);
                    unset($item['card_sum']);
                }
                unset($item);
            }else{
                $list = array();
            }
        }
        return $list;
    }

    public function checkIsListen($user_id, $question_id){
        if(empty($user_id) || !is_numeric($user_id) || empty($question_id) || !is_numeric($question_id)){
            return false;
        }
        $item = $this->MFind(array(
            'field' => 'id',
            'where' => array('user_id' => $user_id, 'question_id' => $question_id)
        ));
        return !empty($item) ? true : false;
    }

    public function createIsListen($user_id, $question_id, $params=array()){
        if(empty($user_id) || !is_numeric($user_id) || empty($question_id) || !is_numeric($question_id)){
            return false;
        }

        if(empty($this->checkIsListen($user_id, $question_id))){
            $save_res = $this->MSave(array(
                'user_id' => $user_id,
                'question_id' => $question_id,
                'total_fee' => isset($params['total_fee']) ? $params['total_fee'] : 0,
            ));
            if(!empty($save_res)){
                QuestionModel::model()->MPlusField('buy_num', $question_id);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

}