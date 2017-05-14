<?php

class CollegeModel extends MBaseModel {
    protected $_table = '{{college}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    /**
     * 根据名称获取ID
     * @param string|array $name 名称
     * @param int $user_id ID
     * @return array|bool|int
     */
    public function getIdByName($name, $user_id=0){
        if(empty($name) || is_array($name) || is_object($name)){
            return 0;
        }

        $check = $this->MFind(array(
            'field' => 'id',
            'where' => array(
                'binary' => array(
                    'name' => $name,
                ),
            ),
        ));
        if(empty($check)){
            return $this->MSave(array(
                'name' => $name,
                'user_id' => $user_id,
            ));
        }else{
            return $check['id'];
        }
    }

}
