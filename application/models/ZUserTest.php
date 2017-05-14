<?php
class ZUserTestModel extends MBaseModel{
    protected $_table = '{{z_user_test}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function MFind($options = array()){
        return parent::MFind($options, 0, 0);
    }

    public function MFindAll($options = array(), $all_num=0){
        return parent::MFindAll($options, $all_num, 0, 0);
    }

    public function MSave($data){
        return parent::MSave($data, 0, 0);
    }

    public function checkLimit($user_name){
        $global_var = Yaf\Registry::get('global_var');
        return $this->MFind(array(
            'field' => 'id',
            'where' => array(
                'site_type_id' => !empty($global_var['site_type']['id']) ? array(0, $global_var['site_type']['id']) : 0,
                'user_name' => $user_name,
                'type_id' => Common::isMobile($user_name) ? 2 : 1,
            ),
        ));
    }

}