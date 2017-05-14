<?php
/**
 * Created by PhpStorm.
 * User: James
 * Date: 31/03/2017
 * Time: 15:31
 */

class Queue{
    private $_topic = 'wx_public';
    private $_key = '';

    public function __construct(){
        $this->setKey();
    }

    public function setKey(){
        $this->_key = 'queue_'.$this->_topic;
    }

    public function producer($list){
        return (!empty($list) && is_array($list)) ? IRedis::getInstance()->rPush($this->_key, $list) : false;
    }
    
    public function consumer($num=100){
        return IRedis::getInstance()->lRange($this->_key, 0, $num-1);
    }

    public function delete(){
        return IRedis::getInstance()->lPop($this->_key);
    }

}