<?php

class WxPublicModel extends MBaseModel{
    protected $_table = '{{wx_public}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function rules(){
        return array(

        );
    }

    public function getInfoToIWeixinById($id){
        return $this->MGetInfoById($id, 'token,appid,appsecret');
    }

    public function getMessageTemplateId($template_key, $public_id=''){
        if(empty($public_id)){
            $public_id = 1;
        }
        if(DOMAIN === TEST_DOMAIN && DEBUG_MODE === true){
            // 测试环境
            $template_list = array(
                1 => array(
                    'compilation_subscription_success' => 'gyVP0_KgpXqTNvf3wx_mgZ2U1f0UqgIWh0MLy9KnisQ',
                    'compilation_info_update' => 'P9vLdvKfgso3ICscg7YAP-X_f8-qUDWs-rjLfg38ROk',
                ),
            );
        }else{
            // 生产环境
            $template_list = array(
                1 => array(
                    'compilation_subscription_success' => 'zoUUrcTSvHUFPug2V1tyxSfUOiE-9Zj4MBJJiQx9mU4',
                    'compilation_info_update' => 'FTWpfSYw_ykA7pOR8kz2TzOpfOlgyaFxYRhcr7fNP3Y',
                ),
            );
        }
        return isset($template_list[$public_id][$template_key]) ? $template_list[$public_id][$template_key] : false;
    }

}