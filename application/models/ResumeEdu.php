<?php

class ResumeEduModel extends MBaseModel{
    protected $_table = '{{resume_edu}}';

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
            'school_id'  => array(
                'empty' => array('type' => 'select'),
                'number' => array(),
            ),
            'school_name'  => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 200),
            ),
            'degree_id' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array' => VariablesModel::model()->getAttrId('degree'))
            ),
            'college_name' => array(
                'filter_func' => array('trim'),
                'max_length' => array('length' => 40),
            ),
            'major_name' => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 60),
            ),
            'double_college_name' => array(
                'filter_func' => array('trim'),
                'max_length' => array('length' => 40),
            ),
            'double_major_name' => array(
                'filter_func' => array('trim'),
                'max_length' => array('length' => 60),
            ),
            'gpa' => array(
                'filter_func' => array('trim'),
                'number' => array('user_error' => 1),
                'float' => array('user_error' => 1, 'length' => 2)
            ),
            'course' => array(
                'filter_func' => array('json_decode'),
                'max_num' => array('num' => 100),
            ),
            'start_time' => array(
                'filter_func' => array('strtotime'),
                'empty' => array('type' => 'select'),
            ),
            'end_time' => array(
                'filter_func' => array('strtotime'),
                'empty' => array('type' => 'select'),
            ),
        );
    }

    public function getTopDegreeEduInfoByResumeId($resume_id, $field='*'){
        return $this->MFind(array(
            'field' => $field,
            'where' => array('resume_id' => $resume_id),
            'order' => 'degree_id ASC,id DESC',
        ));
    }

}