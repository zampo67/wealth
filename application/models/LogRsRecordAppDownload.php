<?php

class LogRsRecordAppDownloadModel extends MBaseModel {
    protected $_table = '{{log_rs_record_app_download}}';

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

    public function createTable(){
        return $this->MExecute(SqlTemplate::getCreateTableSql($this->getTable()));
    }

    public function MSave($data){
        $this->createTable();
        $this->setTable(SqlTemplate::getTableName($this->getTable()));
        return parent::MSave($data, 1, 0);
    }

}