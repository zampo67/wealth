<?php

class ResumeTargetPositionModel extends MBaseModel{
    protected $_table = '{{resume_target_position}}';

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

    public function getTemplateInfo($resume_id){
        return $this->MFindAll(array(
            'field' => 'position_name',
            'where' => array(
                'resume_id' => $resume_id,
            )
        ));
    }

}