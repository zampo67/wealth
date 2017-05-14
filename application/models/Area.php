<?php

class AreaModel extends MBaseModel {
    protected $_table = '{{area}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function getInfoById($id, $field=''){
        $field = !empty($field) ? $field : '*';
        $sql = "SELECT {$field} FROM {$this->_table} WHERE id={$id} AND status='1'";
        $row = $this->MFindBySql($sql);
        if($row){
            if(count($row) == 1){
                return $row[$field];
            }else{
                return $row;
            }
        }else{
            return false;
        }
    }

}
