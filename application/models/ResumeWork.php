<?php

class ResumeWorkModel extends MBaseModel{
    protected $_table = '{{resume_work}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function rules(){
        return array();
    }

    public function getCountByResumeId($resume_id){
        if(empty($resume_id) || !is_numeric($resume_id)){
            return false;
        }
        $item = $this->MFind(array(
            'field' => 'COUNT(1) AS num',
            'where' => array('resume_id' => $resume_id),
        ));
        return !empty($item['num']) ? $item['num'] : 0;
    }

    public function getGroupCountForTypeIdByResumeId($resume_id){
        if(empty($resume_id) || !is_numeric($resume_id)){
            return false;
        }
        $count = $this->MFindAll(array(
            'field' => 'type_id,COUNT(1) AS num',
            'where' => array('resume_id' => $resume_id),
            'group' => 'type_id'
        ));
        return !empty($count) ? $count : array();
    }

}