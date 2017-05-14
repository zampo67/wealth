<?php

class SchoolSearchModel extends MBaseModel {
    protected $_table = '{{school_search}}';
    public static $default_logo_url = '/static/images/common/web_school_default.jpg';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    /**
     * 数据验证规则
     * @return array
     */
    public function rules(){
        return array(
            'name'  => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 50),
            ),
            'type_id' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array'=>Common::arrayColumnToKey(VariablesModel::model()->getList('schoolType'),'','id'))
            ),
            'prov_id' => array(
                'empty' => array('type' => 'select'),
            ),
            'city_id' => array(
                'empty' => array('type' => 'select'),
            ),
            'logo_square_url' => array(
                'filter_func' => array('trim'),
            ),
            'baidu_baike_url' => array(
                'filter_func' => array('trim'),
            ),
            'official_website' => array(
                'filter_func' => array('trim'),
            )
        );
    }

    public function adminEditSave($save_data){
        return $this->MSave($save_data);
    }

    public function adminEditItem($school_id){
        if(empty($school_id) || !is_numeric($school_id)){
            return array();
        }

        return $this->MFind(array(
            'field' => 'id,name,logo_square_url,type_id,baidu_baike_url,official_website,prov_id,city_id',
            'where' => array(
                'id' => $school_id,
            )
        ));
    }

    public function getListOrderCondition(){
         return array(
             array(
                 'id' => 'school_ctime_desc',
                 'name' => '按创建时间倒序',
             ),
             array(
                 'id' => 'school_ctime_asc',
                 'name' => '按创建时间正序',
             ),
         );
    }

    public function getListFilterCondition(){
        $var_data_default = array(array('id'=>'0','name'=>'请选择'));
        return array(
            array(
                'id' => 'school_id',
                'name' => '学校ID',
                'next' => 'input',
                'placeholder' => '',
                'maxlength' => 10,
            )
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
                case 'school_ctime_desc':
                    $order = "l.ctime DESC";
                    break;
                case 'school_ctime_asc':
                    $order = "l.ctime ASC";
                    break;
            }
        }

        $where = implode(' AND ', $where);
        $current_page = (!empty($options['page']) && $options['page']>0) ? (int)$options['page'] : 1;
        $start = ($current_page - 1)*$limit;

        $sql = "SELECT SQL_CALC_FOUND_ROWS l.id,l.name,l.logo_square_url
                ,from_unixtime(ctime, '%Y-%d-%m %h:%i:%s') AS ctime
                FROM {$this->_table} AS l
                WHERE {$where}
                GROUP BY l.id
                ORDER BY {$order}
                LIMIT {$start},{$limit}";
        return $this->MFindAllBySql($sql, $param);
    }
}
