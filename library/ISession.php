<?php

class ISession{
    private $_key_cache = 'session_cache';
    private $_key_active = 'session_active';
    private $_key_user_link = 'session_user_link';

    protected static $instance='';
    public static $_session_id;

    private function __construct(){

    }

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 检查当前实例的session_id是否为空
     * @return bool
     */
    public function checkSessionId(){
        return !empty(self::$_session_id) ? true : false;
    }

    /**
     * 设置session_id或者获取session_id
     * @param string $session_id session_id
     * @param string $prefix 前缀
     * @return mixed|string
     */
    public function sessionId($session_id='', $prefix='a'){
        if(empty($session_id)){
            self::$_session_id = str_replace('.','s',uniqid($prefix, true));
        }else{
            self::$_session_id = $session_id;
        }
        $this->setKeyPrefix($prefix);
        return self::$_session_id;
    }

    /**
     * 设置最新激活时间
     * @return bool|int|mixed
     */
    public function setActive(){
        if($this->checkSessionId() && !empty($this->getData())){
            return IRedis::getInstance()->zAdd($this->_key_active, time(), self::$_session_id);
        }else{
            return false;
        }
    }

    /**
     * 根据用户ID获取用户跟session的关联
     * @param int $user_id 用户id
     * @return array
     */
    public function getUserLink($user_id){
        $sessions = IRedis::getInstance()->hGet($this->_key_user_link, $user_id);
        return !empty($sessions) ? $sessions : array();
    }

    /**
     * 根据用户ID设置用户跟session的关联
     * @param int $user_id 用户id
     * @param string $sess_id session_id
     * @return int
     */
    public function setUserLink($user_id, $sess_id){
        $sessions = $this->getUserLink($user_id);
        $sessions[] = $sess_id;
        return IRedis::getInstance()->hSet($this->_key_user_link, $user_id, $sessions);
    }

    /**
     * 根据用户ID删除用户跟session的关联
     * @param int $user_id 用户id
     * @param string|array $sess_id session_id
     * @return bool
     */
    public function delUserLink($user_id, $sess_id){
        $sessions = $this->getUserLink($user_id);
        if(!empty($sessions)){
            if(!is_array($sess_id)){
                $sess_id = array($sess_id);
            }
            $sessions = array_diff($sessions, $sess_id);
            if(!empty($sessions)){
                $sessions = array_values($sessions);
                IRedis::getInstance()->hSet($this->_key_user_link, $user_id, $sessions);
            }else{
                IRedis::getInstance()->hDel($this->_key_user_link, $user_id);
            }
        }
        return true;
    }

    /**
     * 根据用户ID删除用户所有的session
     * @param int $user_id 用户id
     * @return bool
     */
    public function delSessionByUserId($user_id){
        $sessions = $this->getUserLink($user_id);
        if(!empty($sessions)){
            IRedis::getInstance()->zRem($this->_key_active, $sessions);
            IRedis::getInstance()->hDel($this->_key_cache, $sessions);
        }
        IRedis::getInstance()->hDel($this->_key_user_link, $user_id);
        return true;
    }

    /**
     * 根据最新激活时间删除长时间未激活的session
     * @param $time
     * @return bool
     */
    public function delSessionByActiveTime($time){
        $limit_time = time() - $time;
        // 获取需要删除的session
        $sessions_del = IRedis::getInstance()->zRangeByScore($this->_key_active, 0, $limit_time);
        if(!empty($sessions_del)){
            $list_cache = IRedis::getInstance()->hMGet($this->_key_cache, $sessions_del);
            $list_user_link = IRedis::getInstance()->hGetAll($this->_key_user_link);
            $list_user_link_set = $list_user_link_del = array();

            // 将要删除的session根据用户ID分组
            $list_user_link_diff = array();
            foreach ($sessions_del as $sess_id){
                if(isset($list_cache[$sess_id]['user']['id'])){
                    if(!isset($list_user_link_diff[$list_cache[$sess_id]['user']['id']])){
                        $list_user_link_diff[$list_cache[$sess_id]['user']['id']] = array();
                    }
                    $list_user_link_diff[$list_cache[$sess_id]['user']['id']][] = $sess_id;
                }
            }
            unset($list_cache);

            // 循环分组后用户,处理用户跟session关联
            if(!empty($list_user_link_diff)){
                foreach ($list_user_link_diff as $uid=>$diff_list){
                    if(!empty($list_user_link[$uid])){
                        $user_session = array_values(array_diff($list_user_link[$uid], $diff_list));
                        if(!empty($user_session)){
                            $list_user_link_set[$uid] = $user_session;
                        }else{
                            $list_user_link_del[] = $uid;
                        }
                        unset($user_session);
                    }
                }
            }
            unset($list_user_link);
            unset($list_user_link_diff);

            // 更新用户和session的关联
            if(!empty($list_user_link_set)){
                IRedis::getInstance()->hMSet($this->_key_user_link, $list_user_link_set);
            }
            unset($list_user_link_set);

            $limit_del = 10000;

            // 删除用户和session的关联
            if(!empty($list_user_link_del)){
                $list_user_link_del_count = count($list_user_link_del);
                if($list_user_link_del_count > $limit_del){
                    $max = ceil($list_user_link_del_count / $limit_del);
                    for ($i=0; $i<$max; $i++){
                        $list_user_link_del_part = array_slice($list_user_link_del, $i*$limit_del, $limit_del);
                        if(!empty($list_user_link_del_part)){
                            IRedis::getInstance()->hDel($this->_key_user_link, $list_user_link_del_part);
                        }
                        unset($list_user_link_del_part);
                    }
                }else{
                    IRedis::getInstance()->hDel($this->_key_user_link, $list_user_link_del);
                }
            }
            unset($list_user_link_del);

            // 删除session数据和激活时间数据
            $sessions_del_count = count($sessions_del);
            if($sessions_del_count > $limit_del){
                $max = ceil($sessions_del_count / $limit_del);
                for ($i=0; $i<$max; $i++){
                    $sessions_del_part = array_slice($sessions_del, $i*$limit_del, $limit_del);
                    if(!empty($sessions_del_part)){
                        IRedis::getInstance()->zRem($this->_key_active, $sessions_del_part);
                        IRedis::getInstance()->hDel($this->_key_cache, $sessions_del_part);
                    }
                    unset($sessions_del_part);
                }
            }else{
                IRedis::getInstance()->zRem($this->_key_active, $sessions_del);
                IRedis::getInstance()->hDel($this->_key_cache, $sessions_del);
            }
        }
        unset($sessions_del);
        return true;
    }

    /**
     * 用户登录
     * @param array $data 数据
     * @param string $prefix 前缀
     * @param int $user_id 用户ID
     * @return string
     */
    public function login($data, $prefix='a', $user_id=0){
        $this->sessionId('', $prefix);
        $this->set($data);
        $this->setActive();
        if(!empty($user_id)){
            $this->setUserLink($user_id, self::$_session_id);
        }
        return self::$_session_id;
    }

    /**
     * 退出登录
     * @param int $user_id 用户ID
     * @return bool
     */
    public function logout($user_id=0){
        if($this->checkSessionId()){
            IRedis::getInstance()->zRem($this->_key_active, self::$_session_id);
            IRedis::getInstance()->hDel($this->_key_cache, self::$_session_id);
            if(!empty($user_id)){
                $this->delUserLink($user_id, self::$_session_id);
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取session数据
     * @param string $key session数据的key
     * @return array|bool|mixed|string
     */
    public function get($key=''){
        if($this->checkSessionId()){
            $data = $this->getData();
            return !empty($key) ? ( isset($data[$key]) ? $data[$key] : false ) : $data;
        }else{
            return false;
        }
    }

    /**
     * 设置session数据
     * @param string|array $arr 数据的key,或者是多个数据
     * @param string $value 数据的值
     * @return bool
     */
    public function set($arr, $value=''){
        if($this->checkSessionId()){
            $data = $this->getData();
            if(is_array($arr)){
                $data = array_merge($data, $arr);
            }else{
                $data[$arr] = $value;
            }
            return $this->setData($data);
        }else{
            return false;
        }
    }

    /**
     * 根据key删除对应的session数据
     * @param string| $key 数据的key
     * @return bool
     */
    public function delete($key){
        if($this->checkSessionId()){
            $data = $this->getData();
            if (isset($data[$key])) {
                unset($data[$key]);
            }
            return $this->setData($data);
        }else{
            return false;
        }
    }

    /**
     * 获取session数据
     * @return array|string
     */
    private function getData(){
        $data = IRedis::getInstance()->hGet($this->_key_cache, self::$_session_id);
        return !empty($data) ? $data : array();
    }

    /**
     * 设置session数据
     * @param array $data 数据
     * @return int
     */
    private function setData($data){
        return IRedis::getInstance()->hSet($this->_key_cache, self::$_session_id, $data);
    }

    /**
     * 获取session在缓存中的key
     * @return string
     */
    public function getKeyCache(){
        return $this->_key_cache;
    }

    /**
     * 获取session激活数据在缓存中的key
     * @return string
     */
    public function getKeyActive(){
        return $this->_key_active;
    }

    /**
     * 获取session跟用户的关联数据在缓存中的key
     * @return string
     */
    public function getKeyUserLink(){
        return $this->_key_user_link;
    }

    /**
     * 都缓存的key设置前缀
     * @param string $prefix 前缀
     * @return bool
     */
    public function setKeyPrefix($prefix=''){
        if(!empty($prefix) && (stripos($this->_key_cache, $prefix.'_')===false)){
            $this->_key_cache = $prefix.'_'.$this->_key_cache;
            $this->_key_active = $prefix.'_'.$this->_key_active;
            $this->_key_user_link = $prefix.'_'.$this->_key_user_link;
        }
        return true;
    }

}