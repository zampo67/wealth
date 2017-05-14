<?php

class ResumeModel extends MBaseModel{
    protected $_table = '{{resume}}';
    protected $_table_resume_edu = '{{resume_edu}}';
    public static $default_headimg_url = '/static/images/common/web_headimg_default.jpg';

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
            'username' => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 16),
            ),
            'sex' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array' => VariablesModel::model()->getAttrId('sex')),
            ),
            'location_prov_id' => array(
                'empty' => array('type' => 'select'),
            ),
            'location_city_id' => array(
                'empty' => array('type' => 'select'),
            ),
            'mobile' => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'mobile' => array(),
            ),
            'email' => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'select'),
                'email' => array(),
            ),
            'qq' => array(
                'filter_func' => array('trim'),
                'max_length' => array('length' => 20),
                'number' => array(),
            ),
            'wechat' => array(
                'filter_func' => array('trim'),
                'max_length' => array('length' => 20),
                'wechat' => array(),
            ),
            'marital_status' => array(
                'in_array' => array('array' => VariablesModel::model()->getAttrId('marital_status')),
            ),
            'political_status' => array(
                'in_array' => array('array' => VariablesModel::model()->getAttrId('political_status')),
            ),
            'nationality' => array(
                'filter_func' => array('trim'),
                'max_length' => array('length' => 12),
            ),
            'height' => array(
                'max_length' => array('length' => 3),
                'number' => array(),
            ),
            'weight' => array(
                'max_length' => array('length' => 3),
                'number' => array(),
            ),
        );
    }

    public function templateAttrs($key=''){
        $data =  array(
            'template2'  => array('id'=>2,  'label'=>'经典模块简历','is_lock'=>0,'activity_id'=>1),
            'template5'  => array('id'=>5,  'label'=>'乌托单页简历','is_lock'=>1,'activity_id'=>1),
            'template11' => array('id'=>11, 'label'=>'欧风商务简历','is_lock'=>1,'activity_id'=>3),
            'template12' => array('id'=>12, 'label'=>'缤纷都市简历','is_lock'=>1,'activity_id'=>3),
            'template1'  => array('id'=>1,  'label'=>'简洁蓝色简历','is_lock'=>1,'activity_id'=>1),
            'template3'  => array('id'=>3,  'label'=>'清新双色简历','is_lock'=>1,'activity_id'=>1),
            'template4'  => array('id'=>4,  'label'=>'优雅简约简历','is_lock'=>1,'activity_id'=>1),
            'template9'  => array('id'=>9,  'label'=>'个性未来简历','is_lock'=>1,'activity_id'=>2),
            'template10' => array('id'=>10, 'label'=>'立体时尚简历','is_lock'=>1,'activity_id'=>2),
            'template6'  => array('id'=>6,  'label'=>'轻便简洁简历','is_lock'=>1,'activity_id'=>1),
            'template7'  => array('id'=>7,  'label'=>'灰蓝精致简历','is_lock'=>1,'activity_id'=>1),
            'template8'  => array('id'=>8,  'label'=>'多彩校园简历','is_lock'=>1,'activity_id'=>1),
        );

        if(!empty($key) && !empty($data[$key])){
            return $data[$key];
        }

        return $data;
    }

    /**
     * 通过id获取简历基础数据
     * @param $resume_id
     * @param string $field
     * @return array|mixed
     */
    public function getInfoById($resume_id,$field=''){
        $field = !empty($field) ? $field : '*';

        if(is_array($field)){
            $field = implode(',',$field);
        }

        return $this->Mfind(array(
            'field' => $field,
            'where' => array(
                'id' => $resume_id
            )
        ));
    }

    /**
     * 获取模板活动ID的最大值
     * @return int
     */
    public function getTemplateMaxActivityId(){
        return max(array_column($this->templateAttrs(),'activity_id'));
    }

    public function getProductOrderId($id){
        return 'resume'.$id.date("YmdHis");
    }

    public function getProductPrice($i18n_id=1, $format='fen'){
        switch($i18n_id){
            case 1:
            default:
                $price = 1990;
                break;
            case 11:
                $price = 6900;
                break;
        }
        switch ($format){
            case 'fen':
            default:
                return $price;
                break;
            case 'yuan':
                return round( ($price / 100), 2);
                break;
        }
    }

    /**
     * 获取简历基础数据
     * @param $resume_id
     * @return array|mixed
     */
    public function getTemplateInfo($resume_id){
        return $this->MFind(array(
            'field' => 'username,sex,birth_time,introduction,nationality,marital_status,political_status,height'
                        .',weight,origin_prov_name,origin_city_name,location_prov_name,location_city_name'
                        .',headimg_id,headimgurl,personal_links,mobile,email,wechat,qq,target_salary,i18n_id',
            'where' => array(
                'id' => $resume_id
            )
        ));
    }
}
