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

    public function MFind($options = Array(), $status = 0, $is_del = 0){
        return parent::MFind($options, $status, $is_del);
    }

    public function MFindAll($options = Array(), $all_num = 0, $status = 0, $is_del = 0){
        return parent::MFindAll($options, $all_num, $status, $is_del);
    }

    public function MSave($data, $ctime = 0, $mtime = 0){
        return parent::MSave($data, $ctime, $mtime);
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