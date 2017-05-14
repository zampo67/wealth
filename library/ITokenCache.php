<?php

/**
 * ls_token相关临时缓存类
 * User: James
 * Date: 18/04/2017
 * Time: 18:07
 */
class ITokenCache{
    protected static $instance = '';
    private $_cache_key = '';

    private function __construct(){

    }

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 检查当前实例的cache_key是否为空
     * @return bool
     */
    public function checkCacheKey(){
        return !empty($this->_cache_key) ? true : false;
    }

    /**
     * 设置cache_key
     * @param $cache_key
     */
    public function setCacheKey($cache_key){
        $this->_cache_key = $cache_key;
    }

    /**
     * 设置缓存数据
     * @param string|int $key 键
     * @param string|int|array $value 值
     * @param int $expire 过期时间
     * @return bool
     */
    public function set($key, $value, $expire=3600){
        if(!empty($this->_cache_key)){
            $cache = IRedis::getInstance()->get($this->_cache_key);
            if(empty($cache)){
                $cache = array();
            }
            $cache[$key] = $value;
            return IRedis::getInstance()->setEx($this->_cache_key, $cache, $expire);
        }else{
            return false;
        }
    }

    /**
     * 获取缓存数据
     * @param string|int|null $key 键
     * @return array|bool|int|mixed|string
     */
    public function get($key=null){
        if(!empty($this->_cache_key)){
            $cache = IRedis::getInstance()->get($this->_cache_key);
            if(empty($cache)){
                $cache = array();
            }
            return is_null($key) ? $cache : (isset($cache[$key]) ? $cache[$key] : '');
        }else{
            return false;
        }
    }

}