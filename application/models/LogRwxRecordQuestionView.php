<?php
class LogRwxRecordQuestionViewModel extends MBaseModel{
    protected $_table = '{{log_rwx_record_question_view}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
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
            if(!empty($data['user_id'])){
                $data['is_first'] = !empty($this->MFind(array(
                    'field' => 'id',
                    'where' => array('user_id' => $data['user_id'])
                ))) ? 0 : 1;
            }else{
                $data['is_first'] = !empty($this->MFind(array(
                    'field' => 'id',
                    'where' => array('wx_user_id' => $data['wx_user_id'])
                ))) ? 0 : 1;
            }
        }
        return $this->MSave($data);
    }

    public function checkIsRegToday($user_id){
        $start_time = TimeFormat::getInstance()->getDayZeroTime();
        $item = $this->MFindBySql(
            "SELECT id FROM {$this->_table}
             WHERE ctime>={$start_time} AND user_id=:user_id AND is_first='1'"
        , array(':user_id' => $user_id));
        return !empty($item['id']) ? true : false;
    }

}