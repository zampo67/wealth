<?php

class VariablesModel extends MBaseModel {
    protected $_table = '{{variables}}';

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

    /**
     * 设置国际化语言id
     * @param int $i18n_id 语言ID
     */
    public function setI18nById($i18n_id=1){
        $locale = $this->getAttrById('i18n', 'locale', $i18n_id);
        if(!empty($locale)){
            I18n::getInstance()->setI18n($locale);
        }
    }

    /**
     * 重置语言
     */
    public function resetI18nId(){
        $this->setI18nById();
    }

    /**
     * 获取属性值
     * @param string $type 类型
     * @param string|int|null $key 属性的键值
     * @param string|int|null $attr 属性名
     * @return array|mixed|string
     */
    public function getAttrs($type, $key=null, $attr=null){
        if(stripos($type, '_') !== false){
            $type = Common::strUnderToHump($type);
        }
        $list = call_user_func(array('self', $type.'Attrs'));
        if(!is_null($key)){
            if(isset($list[$key])){
                return !is_null($attr) ? (isset($list[$key][$attr]) ? $list[$key][$attr] : '') : $list[$key];
            }else{
                return !is_null($attr) ? '' : array();
            }
        }else{
            return !is_null($attr) ? array_column($list, $attr) : $list;
        }
    }

    /**
     * 根据ID获取属性值
     * @param string $type 类型
     * @param string|int $attr 属性名
     * @param int|string|null $id ID
     * @return array|string
     */
    public function getAttrById($type, $attr, $id=null){
        if(stripos($type, '_') !== false){
            $type = Common::strUnderToHump($type);
        }
        $list = call_user_func(array('self', $type.'Attrs'));
        if(!is_null($id)){
            $list = Common::arrayColumnToKey($list, 'id');
            return isset($list[$id][$attr]) ? $list[$id][$attr] : '';
        }else{
            return Common::arrayColumnToKey($list, 'id', $attr);
        }
    }

    /**
     * 获取ID属性
     * @param string $type 类型
     * @param string|int|null $key 属性的键值
     * @return array|mixed|string
     */
    public function getAttrId($type, $key=null){
        return $this->getAttrs($type, $key, 'id');
    }

    /**
     * 获取name属性
     * @param string $type 类型
     * @param string|int|null $key 属性的键值
     * @return array|mixed|string
     */
    public function getAttrName($type, $key=null){
        return $this->getAttrs($type, $key, 'name');
    }

    /**
     * 通过ID获取name属性
     * @param string $type 类型
     * @param int|string $id ID
     * @return array|mixed|string
     */
    public function getAttrNameById($type, $id){
        return $this->getAttrById($type, 'name', $id);
    }

    /**
     * 获取数组格式的属性列表
     * @param string $type 类型
     * @return array
     */
    public function getList($type){
        $list = $this->getAttrs($type);
        return !empty($list) ? array_values($list) : array();
    }

    /**
     * 全局-支付类型
     * @return array
     */
    public function payTypeAttrs(){
        return array(
            'wxpay'  => array('id'=>1, 'name'=>'wxpay'),
            'alipay' => array('id'=>2, 'name'=>'alipay'),
        );
    }

    /**
     * 全局-微信分享类型
     * @return array
     */
    public function wxShareTypeAttrs(){
        return array(
            'timeline'    => array('id'=>1, 'name'=>'timeline'),
            'app_message' => array('id'=>2, 'name'=>'app_message'),
            'qq'          => array('id'=>3, 'name'=>'qq'),
            't_weibo'     => array('id'=>4, 'name'=>'t_weibo'),
            'q_zone'      => array('id'=>5, 'name'=>'q_zone'),
        );
    }

    /**
     * 全局-日志时间周期类型
     * @return array
     */
    public function logTimeTypeAttrs(){
        return array(
            'second' => array('id'=>1, 'name'=>'second'),
            'minute' => array('id'=>11,'name'=>'minute'),
            'hour'   => array('id'=>21,'name'=>'hour'),
            'day'    => array('id'=>31,'name'=>'day'),
            'week'   => array('id'=>41,'name'=>'week'),
            'month'  => array('id'=>51,'name'=>'month'),
            'year'   => array('id'=>61,'name'=>'year'),
        );
    }

    /**
     * 全局-平台类型
     * @return array
     */
    public function platTypeAttrs(){
        return array(
            'web_pc'     => array('id'=>1,  'name'=>'PC'),
            'web_mobile' => array('id'=>2,  'name'=>'Mobile'),
            'app'        => array('id'=>11, 'name'=>'APP'),
            'ios'        => array('id'=>12, 'name'=>'IOS'),
            'android'    => array('id'=>13, 'name'=>'Android'),
            'unknown'    => array('id'=>99, 'name'=>'Unknown'),
        );
    }

    /**
     * 全局-设备类型
     * @return array
     */
    public function deviceTypeAttrs(){
        return array(
            'ios'     => array('id'=>1, 'name'=>'ios'),
            'android' => array('id'=>2, 'name'=>'android'),
        );
    }

    /**
     * 全局-语言
     * @return array
     */
    public function i18nAttrs(){
        return array(
            'zh_CN' => array('id'=>1,  'name'=>'zh_CN', 'locale'=>'zh_CN'),
            'en_US' => array('id'=>11, 'name'=>'en_US', 'locale'=>'en_US'),
        );
    }

    /**
     * 全局-用户分组
     * @return array
     */
    public function userGroupAttrs(){
        return array(
            'owner'   => array('id'=>UserModel::$owner_group_id, 'name'=>'拥有者'),
            'base' => array('id'=>UserModel::$base_group_id, 'name'=>'成员'),
        );
    }

}
