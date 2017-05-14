<?php

class PositionModel extends MBaseModel {
    protected $_table = '{{position}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function getListOrderCondition(){
        return array(
            array(
                'id' => 'position_ctime_desc',
                'name' => '按创建时间倒序',
            ),
            array(
                'id' => 'position_ctime_asc',
                'name' => '按创建时间正序',
            ),
        );
    }

    public function getListFilterCondition(){
        $var_data_default = array(array('id'=>'0','name'=>'请选择'));
        return array(
        );
    }


    public function adminEditList($options=array(),$limit=10){
        $where = array(
            "l.status='1'",
            "l.is_del='0'",
        );
        $param = array();

        if(!empty($options['filter'])) {
            foreach ($options['filter'] as $f) {
                if (empty($f['value'])) {
                    continue;
                }
                $v = $f['value'];
                switch ($f['key']){
                    case 'school_id':
                        $where[] = "l.id=:school_id";
                        $param[':school_id'] = $v;
                        break;
                }
            }
        }

        //关键词搜索
        if(isset($options['keyword'])){
            $options['keyword'] = trim($options['keyword']);
            if(!empty($options['keyword'])){
                $where[] = "l.name LIKE :keyword";
                $param[':keyword'] = '%'.$options['keyword'].'%';
            }
        }

        $order = "l.ctime DESC";
        if(!empty($options['order'])){
            switch($options['order']){
                case 'position_ctime_desc':
                    $order = "l.ctime DESC";
                    break;
                case 'position_ctime_asc':
                    $order = "l.ctime ASC";
                    break;
            }
        }

        $where = implode(' AND ', $where);
        $current_page = (!empty($options['page']) && $options['page']>0) ? (int)$options['page'] : 1;
        $start = ($current_page - 1)*$limit;

        $sql = "SELECT SQL_CALC_FOUND_ROWS l.id,l.name
                ,from_unixtime(ctime, '%Y-%d-%m %h:%i:%s') AS ctime
                FROM {$this->_table} AS l
                WHERE {$where}
                ORDER BY {$order}
                LIMIT {$start},{$limit}";
        return $this->MFindAllBySql($sql, $param);
    }

}
