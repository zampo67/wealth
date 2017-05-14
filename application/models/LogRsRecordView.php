<?php

/**
 * Class LogRsRecordViewModel
 */
class LogRsRecordViewModel extends MBaseModel{
    CONST TABLE_MAME = '{{log_rs_record_view}}';
    protected $_table = self::TABLE_MAME;
    protected $_create_table_status = array();

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function createTable($year_month = ''){
        if(!isset($this->_create_table_status[$year_month])){
            $this->_create_table_status[$year_month] = 1;
            return $this->MExecute(SqlTemplate::getCreateTableSql($this->getTable(),array('year_month'=>$year_month)));
        }
    }

    public function setTable($year_month = ''){
        $this->resetTable();
        $this->createTable($year_month);
        parent::setTable(SqlTemplate::getTableName(parent::getTable(),array('year_month'=>$year_month)));
    }

    public function resetTable(){
        $this->_table = self::TABLE_MAME;
    }

    public function MFind($options = array(), $year_month = ''){
        $this->setTable($year_month);
        return parent::MFind($options, 0, 0);
    }

    public function MFindAll($options = array(), $all_num = 0, $year_month = ''){
        $this->setTable($year_month);
        return parent::MFindAll($options, $all_num, 0, 0);
    }

    public function MSave($data, $year_month = '', $ctime = 1){
        $this->setTable($year_month);
        return parent::MSave($data, $ctime, 0);
    }

    public function insertRecord($data, $year_month = ''){
        if(!empty($data)){
            return $this->MSave(array(
                'user_id' => $data['user_id'],
                'request_uri' => $data['request_uri'],
                'day' => date('d'),
            ), $year_month);
        }else{
            return false;
        }
    }

    public function getSqlsByDateEgt($date){
        $day = date('d', strtotime($date));
        $year_month = date('Ym', strtotime($date));
        $year_month_now = date('Ym');
        $sqls = array();

        if($year_month != $year_month_now){
            $this->setTable($year_month);
            $table1 = $this->getTable();
            $sqls[] = "SELECT DISTINCT user_id FROM {{{$table1}}} WHERE day>={$day}";

            $this->setTable($year_month_now);
            $table2 = $this->getTable();
            $sqls[] = "SELECT DISTINCT user_id FROM {{{$table2}}}";
        }else{
            $this->setTable($year_month);
            $table = $this->getTable();
            $sqls[] = "SELECT DISTINCT user_id FROM {{{$table}}} WHERE day>={$day}";
        }
        return $sqls;
    }

}