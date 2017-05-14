<?php
class LogRwxRecordQuestionShareModel extends MBaseModel{
    protected $_table = '{{log_rwx_record_question_share}}';

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
            'url_type_id' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array' => array_column(VariablesModel::model()->getAttrs('resumeQuestionUrlType'), 'id')),
            ),
            'link_id' => array(
                'empty_string' => array('type' => 'select'),
            ),
            'link_type_id' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array' => array_column(VariablesModel::model()->getAttrs('resumeQuestionLinkType'), 'id')),
            ),
            'share_type_id' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array' => array_column(VariablesModel::model()->getAttrs('wxShareType'), 'id')),
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
        return $this->MSave($data);
    }

}