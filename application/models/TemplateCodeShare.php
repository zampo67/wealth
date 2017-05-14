<?php

class TemplateCodeShareModel extends MBaseModel{
    protected $_table = '{{template_code_share}}';

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

    public function getCode($resume_id){
        $res = $this->MFind(array('field'=>'code','where'=>array('resume_id'=>$resume_id)));
        if(empty($res['code'])){
            return self::model()->createCode($resume_id, 1);
        }else{
            return $res['code'];
        }
    }

    public function createCode($params=array(), $length=4, $level=0){
        if($level == 10){
            return false;
        }
        $str = 'abcdefghijkmnpqrstuvwxyz123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0, $code = ''; $i < $length; $i++) {
            $code .= Tools::substr($str, mt_rand(0, Tools::strlen($str) - 1), 1);
        }
        $save_data = array('code' => $code);
        $fields = array('resume_id', 'valid_num', 'is_permanent', 'i18n_id');
        foreach ($fields as $f){
            if(isset($params[$f])){
                $save_data[$f] = $params[$f];
            }
        }
        $res = $this->MSave($save_data);
        if($res){
            return $code;
        }else{
            return self::model()->createCode($params, $length, $level+1);
        }
    }

}