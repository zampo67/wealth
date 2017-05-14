<?php

class ResumeAppVersionModel extends MBaseModel {
    protected $_table = '{{resume_app_version}}';

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

    public function getAndroidNewestVersion($site_type_id=1){
        return $this->getNewestVersion(VariablesModel::model()->getAttrId('deviceType', 'android'), $site_type_id);
    }

    public function getNewestVersion($device_type_id, $site_type_id){
        return $this->MFind(array(
            'field' => 'version,introduction,is_force,link_url',
            'where' => array(
                'site_type_id' => $site_type_id,
                'device_type_id' => $device_type_id,
            ),
            'order' => 'id DESC',
        ));
    }

}