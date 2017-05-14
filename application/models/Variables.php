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
     * 获取存储数据库的内容id
     * @param $content
     * @param $sign
     * @param $type_id
     * @return mixed
     */
    public function getVarId($content,$sign,$type_id){
        if(empty($content)){
            return 0;
        }

        $res = $this->MFind(array(
            'field' => 'id',
            'where' => array(
                'sign' => $sign,
                'type_id' => $type_id,
                'content' => $content
            )
        ));
        return !empty($res['id']) ? $res['id'] : $this->MSave(array(
            'sign' => $sign,
            'type_id' => $type_id,
            'content' => $content
        ));
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
     * 全局-站点类型
     * @return array
     */
    public function siteTypeAttrs(){
        return array(
            'resume'  => array('id'=>1, 'name'=>'resume'),
            'company' => array('id'=>2, 'name'=>'company'),
        );
    }

    /**
     * 全局-性别
     * @return array
     */
    public function sexAttrs(){
        return array(
            'male'   => array('id'=>1, 'name'=>I18n::getInstance()->getVariables('sex', 1)),
            'female' => array('id'=>2, 'name'=>I18n::getInstance()->getVariables('sex', 2)),
        );
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
     * 全局-支付产品类型
     * @return array
     */
    public function payLinkTypeAttrs(){
        return array(
            'resume'      => array('id'=>1, 'name'=>'resume'),
            'question'    => array('id'=>2, 'name'=>'question'),
            'compilation' => array('id'=>3, 'name'=>'compilation'),
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
     * 全局-注册类型
     * @return array
     */
    public function registerTypeAttrs(){
        return array(
            'email'  => array('id'=>1, 'name'=>'email'),
            'mobile' => array('id'=>2, 'name'=>'mobile'),
            'QQ'     => array('id'=>3, 'name'=>'QQ'),
            'wx'     => array('id'=>4, 'name'=>'wechat'),
        );
    }

    /**
     * 全局-注册模块类型
     * @return array
     */
    public function registerModuleTypeAttrs(){
        return array(
            'resume'   => array('id'=>1,  'name'=>'resume'),
            'question' => array('id'=>2,  'name'=>'question'),
        );
    }

    /**
     * 全局-注册平台类型
     * @return array
     */
    public function registerPlatTypeAttrs(){
        return $this->platTypeAttrs();
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
     * 全局-学历分类
     * @return array
     */
    public function degreeAttrs(){
        return array(
            'doctor'      => array('id'=>1,  'name'=>I18n::getInstance()->getVariables('degree', 1)),
            'master'      => array('id'=>2,  'name'=>I18n::getInstance()->getVariables('degree', 2)),
            'bachelor'    => array('id'=>3,  'name'=>I18n::getInstance()->getVariables('degree', 3)),
            'college'     => array('id'=>4,  'name'=>I18n::getInstance()->getVariables('degree', 4)),
            'secondary'   => array('id'=>5,  'name'=>I18n::getInstance()->getVariables('degree', 5)),
            'senior_high' => array('id'=>6,  'name'=>I18n::getInstance()->getVariables('degree', 6)),
            'other'       => array('id'=>99, 'name'=>I18n::getInstance()->getVariables('degree', 99)),
        );
    }

    /**
     * 全局-技能掌握水平
     * @return array
     */
    public static function skillTypeAttrs(){
        return array(
            '1' => array('id'=>1, 'name'=>I18n::getInstance()->getVariables('skill_type',1)),
            '2' => array('id'=>2, 'name'=>I18n::getInstance()->getVariables('skill_type',2)),
            '3' => array('id'=>3, 'name'=>I18n::getInstance()->getVariables('skill_type',3)),
            '4' => array('id'=>4, 'name'=>I18n::getInstance()->getVariables('skill_type',4)),
            '5' => array('id'=>5, 'name'=>I18n::getInstance()->getVariables('skill_type',5)),
        );
    }

    public static function eduExperienceTypeAttrs(){
        return array(
            'association' => array('id'=>'5', 'name'=>I18n::getInstance()->getVariables('eduExperience_type',5)),
            'duty'        => array('id'=>'6', 'name'=>I18n::getInstance()->getVariables('eduExperience_type',6)),
            'scholarship' => array('id'=>'1', 'name'=>I18n::getInstance()->getVariables('eduExperience_type',1)),
            'award'       => array('id'=>'7', 'name'=>I18n::getInstance()->getVariables('eduExperience_type',7)),
            'competition' => array('id'=>'2', 'name'=>I18n::getInstance()->getVariables('eduExperience_type',2)),
        );
    }

    public static function workTypeAttrs(){
        return array(
            'internship' => array('id'=>'1', 'name'=>I18n::getInstance()->getVariables('work_type_resume',1)),
            'parttime'   => array('id'=>'3', 'name'=>I18n::getInstance()->getVariables('work_type_resume',3)),
            'fulltime'   => array('id'=>'2', 'name'=>I18n::getInstance()->getVariables('work_type_resume',2)),
        );
    }

    public static function workTypeResumeAttrs(){
        return array(
            'fulltime'   => array('id'=>'2', 'name'=>I18n::getInstance()->getVariables('work_type_resume',2)),
            'internship' => array('id'=>'1', 'name'=>I18n::getInstance()->getVariables('work_type_resume',1)),
            'practice'   => array('id'=>'4', 'name'=>I18n::getInstance()->getVariables('work_type_resume',4)),
            'parttime'   => array('id'=>'3', 'name'=>I18n::getInstance()->getVariables('work_type_resume',3)),
        );
    }

    /**
     * 全局-学校分类
     * @return array
     */
    public function schoolTypeAttrs(){
        return array(
            '985'         => array('id'=>11, 'name'=>'985'),
            '211'         => array('id'=>12, 'name'=>'211'),
            'bachelor1'   => array('id'=>13, 'name'=>'一本'),
            'bachelor2'   => array('id'=>14, 'name'=>'二本'),
            'bachelor3'   => array('id'=>15, 'name'=>'三本'),
            'college'     => array('id'=>16, 'name'=>'专科'),
            'secondary'   => array('id'=>17, 'name'=>'中专'),
            'senior_high' => array('id'=>18, 'name'=>'高中'),
            'other'       => array('id'=>99, 'name'=>'其他'),
        );
    }

    /**
     * 全局-婚姻状况
     * @return array
     */
    public function maritalStatusAttrs(){
        return array(
            'married' => array('id'=>1, 'name'=>I18n::getInstance()->getVariables('marital_status', 1)),
            'single'  => array('id'=>2, 'name'=>I18n::getInstance()->getVariables('marital_status', 2)),
        );
    }

    /**
     * 全局-政治面貌
     * @return array
     */
    public function politicalStatusAttrs(){
        return array(
            'cpc'          => array('id'=>1, 'name'=>I18n::getInstance()->getVariables('political_status', 1)),
            'probationary' => array('id'=>4, 'name'=>I18n::getInstance()->getVariables('political_status', 4)),
            'youth'        => array('id'=>2, 'name'=>I18n::getInstance()->getVariables('political_status', 2)),
            'citizen'      => array('id'=>3, 'name'=>I18n::getInstance()->getVariables('political_status', 3)),
            'democratic'   => array('id'=>5, 'name'=>I18n::getInstance()->getVariables('political_status', 5)),
            'others'       => array('id'=>99,'name'=>I18n::getInstance()->getVariables('political_status', 99))
        );
    }

    /**
     * 简历端-解锁类型
     * @return array
     */
    public function resumeUnlockCodeByPayAttrs(){
        return array(
            'wxpay_public' => array('id'=>-1, 'name'=>'wxpay_public'),
            'wxpay_app'    => array('id'=>-2, 'name'=>'wxpay_app'),
            'alipay_app'   => array('id'=>-11,'name'=>'alipay_app'),
            'apple_iap'    => array('id'=>-21,'name'=>'apple_iap'),
        );
    }

    /**
     * 简历端-完成简历的平台类型
     * @return array
     */
    public function resumeFinishPlatTypeAttrs(){
        return $this->platTypeAttrs();
    }

    /**
     * 简历端-问答合集收费类型
     * @return array
     */
    public function resumeQuestionCompilationChargeTypeAttrs(){
        return array(
            'forever' => array('id'=>1, 'name'=>'forever'),
            'year'    => array('id'=>2, 'name'=>'year'),
        );
    }

    /**
     * 简历端-问答合集订阅类型
     * @return array
     */
    public function resumeQuestionCompilationSubscriptionTypeAttrs(){
        return array(
            'pay'     => array('id'=>1, 'name'=>'pay'),
            'get_new' => array('id'=>2, 'name'=>'get_new'),
        );
    }

    /**
     * 简历端-问答访问的url类型
     * @return array
     */
    public function resumeQuestionUrlTypeAttrs(){
        return array(
            'question_item'    => array('id'=>2, 'name'=>'question_item'),
            'compilation_list' => array('id'=>11,'name'=>'compilation_list'),
            'compilation_item' => array('id'=>12,'name'=>'compilation_item'),
            'lecturer_item'    => array('id'=>22,'name'=>'lecturer_item'),
            'user_order'       => array('id'=>51,'name'=>'user_order'),
            'site_wx_register' => array('id'=>81,'name'=>'site_wx_register'),
        );
    }

    /**
     * 简历端-问答相关的关联类型
     * @return array
     */
    public function resumeQuestionLinkTypeAttrs(){
        return array(
            'question'    => array('id'=>1, 'name'=>'question'),
            'compilation' => array('id'=>2, 'name'=>'compilation'),
            'lecturer'    => array('id'=>3, 'name'=>'lecturer'),
        );
    }

    /**
     * 简历端-问答相关的卡片类型
     * @return array
     */
    public function resumeQuestionCardTypeAttrs(){
        return array(
            'text'  => array('id'=>1, 'name'=>'text'),
            'image' => array('id'=>2, 'name'=>'image'),
        );
    }

    /**
     * 微信菜单类型
     * @return array
     */
    public function menuTypeAttrs(){
        return array(
            'view'  => array('id'=>'view', 'name'=>'view'),
            'click' => array('id'=>'click', 'name'=>'click'),
        );
    }

}
