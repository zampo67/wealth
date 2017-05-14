<?php

class ResumeWebLsTokenModel extends MBaseModel {
    protected $_table = '{{resume_web_ls_token}}';
    protected $_cache_hkey = 'r_web_ls_token';

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

    public function MFind($options){
        return parent::MFind($options, 0, 0);
    }

    public function MSave($data){
        return parent::MSave($data, 1, 0);
    }

    public function getToken($level=1){
        $token = $this->createToken();
        if($this->checkToken($token)){
            if($level>10){
                return false;
            }else{
                $level++;
                $this->getToken($level);
            }
        }else{
            $this->MSave(array('token'=>$token));
            $this->setCache($token);
            return $token;
        }
    }

    public function checkToken($token){
        if(empty(IRedis::getInstance()->hGet($this->_cache_hkey, $token))){
            $item = $this->MFind(array(
                'field' => 'id',
                'where' => array('token'=>$token),
            ));
            if(!empty($item)){
                $this->setCache($token);
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }

    public function createToken(){
        return rand(1000, 9999).substr(microtime(), 2, 2).time().substr(microtime(), 4, 2);
    }

    public function setCache($token){
        IRedis::getInstance()->hSet($this->_cache_hkey, $token, 1);
    }

}