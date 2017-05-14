<?php

class LogRsRecordBrowserNameModel extends MBaseModel {
    protected $_table = '{{log_rs_record_browser_name}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    /**
     * Model规则
     * @return array
     */
    public function rules(){
        return array(

        );
    }

    public function MFind($options = Array(), $status = 0, $is_del = 0){
        return parent::MFind($options,$status, $is_del);
    }

    public function MSave($data, $ctime = 1, $mtime = 0){
        return parent::MSave($data, $ctime, $mtime);
    }
}