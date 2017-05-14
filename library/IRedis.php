<?php

class IRedis {
    protected static $instance='';
    private $redis;
    private $config;

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            $config = Yaf\Registry::get('config');
            self::$instance = new self($config->redis);
        }
        return self::$instance;
    }

    private function __construct($config){
        defined('REDIS_PREFIX') or define('REDIS_PREFIX', '');
        $this->config = $config;
        if(empty($this->redis)) {
            $name = $config['name'];
            $pconnect = $config['pconnect'];

            $redis = new \Redis();
            if ($pconnect) {
                $redis->pconnect($config['host'], $config['port'], $config['timeout'], $name);
            } else {
                $redis->connect($config['host'], $config['port'], $config['timeout']);
            }
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
            $this->redis = $redis;
        }
    }

    private function getKey($key){
        return REDIS_PREFIX . $key;
    }

    private function getEvalStringKey($func, $params){
        if(!is_array($params)){
            $params = array($params);
        }
        $eval_string = 'return $this->redis->'.$func.'(';
        foreach ($params as $item){
            $eval_string .= "'{$this->getKey($item)}',";
        }
        $eval_string = mb_substr($eval_string, 0, -1);
        $eval_string .= ');';
        return $eval_string;
    }

    private function getEvalStringValue($func, $params){
        if(!is_array($params)){
            $params = array($params);
        }
        $eval_string = 'return $this->redis->'.$func.'(';
        foreach ($params as $item){
            $eval_string .= "'{$item}',";
        }
        $eval_string = mb_substr($eval_string, 0, -1);
        $eval_string .= ');';
        return $eval_string;
    }

    /**
     * 清除redis数据
     * @return bool true
     */
    public function clear(){
        $this->redis->flushDB();
        return true;
    }

    /**
     * 检查给定key是否存在
     * @param string $key 键值
     * @return bool
     */
    public function exists($key){
        return $this->redis->exists($this->getKey($key));
    }

    /**
     * 删除某个顶级键
     * @param string $key 键值
     * @return bool true
     */
    public function delete($key){
        $this->redis->delete($this->getKey($key));
        return true;
    }

    /**
     * 存放数据
     * @param string $key 键值
     * @param array|string|int $data 数据
     * @return bool
     */
    public function set($key, $data){
        return $this->redis->set($this->getKey($key), \json_encode($data));
    }

    /**
     * 存放定时数据
     * @param string $key 键值
     * @param array|string|int $data 数据
     * @param int $expire 时限，单位秒
     * @return bool
     */
    public function setEx($key, $data, $expire=6000){
        return $this->redis->setEx($this->getKey($key), $expire, \json_encode($data));
    }

    /**
     * 获取数据
     * @param string $key 键值
     * @return array|string|int
     */
    public function get($key){
        $data = $this->redis->get($this->getKey($key));
        if (empty($data)) {
            return '';
        }
        return \json_decode($data, true);
    }

    /** --------------------  哈希类型  ---------------------------- **/

    /**
     * 批量存放hash数据
     * @param string $key 键值
     * @param array $data 数据
     * @return bool
     */
    public function hMSet($key, $data){
        foreach ($data as $k=>$v){
            $data[$k] = \json_encode($v);
        }
        return $this->redis->hMset($this->getKey($key), $data);
    }

    /**
     * 批量获取hash数据
     * @param string $key 键值
     * @param string $fields 字段
     * @return array
     */
    public function hMGet($key, $fields){
        $data = $this->redis->hMGet($this->getKey($key), $fields);
        foreach ($data as $k=>$v){
            $data[$k] = \json_decode($v, true);
        }
        return $data;
    }

    /**
     * 存放hash数据
     * @param string $key 键值
     * @param string $field 字段
     * @param array|string|int $value 数据
     * @return int
     */
    public function hSet($key, $field, $value){
        return $this->redis->hSet($this->getKey($key), $field, \json_encode($value));
    }

    /**
     * 获取hash数据
     * @param string $key 键值
     * @param string $field 字段
     * @return string|array
     */
    public function hGet($key, $field){
        $data = $this->redis->hGet($this->getKey($key), $field);
        if (empty($data)) {
            return '';
        }
        return \json_decode($data, true);
    }

    /**
     * 获取hash键对应的所有数据(字段，值)
     * @param string $key 键值
     * @return array
     */
    public function hGetAll($key){
        $data = $this->redis->hGetAll($this->getKey($key));
        if (empty($data)) {
            return '';
        }else{
            foreach ($data as $k=>$v){
                $data[$k] = \json_decode($v, true);
            }
            return $data;
        }
    }

    /**
     * 获取hash键对应的所有字段
     * @param string $key 键值
     * @return array
     */
    public function hKeys($key){
        $data = $this->redis->hKeys($this->getKey($key));
        return $data;
    }

    /**
     * 获取hash键对应的所有值
     * @param string $key 键值
     * @return array
     */
    public function hVals($key){
        $data = $this->redis->hVals($this->getKey($key));
        foreach ($data as $k=>$v){
            $data[$k] = \json_decode($v, true);
        }
        return $data;
    }

    /**
     * 删除hash键中的字段
     * @param string $key 键值
     * @param string|array $fields 字段
     * @return int
     */
    public function hDel($key, $fields){
        if(!is_array($fields)){
            $fields = array($fields);
        }
        array_unshift($fields, $this->getKey($key));
        return eval($this->getEvalStringValue('hDel', $fields));
    }

    /** --------------------  集合类型  ---------------------------- **/

    /**
     * 将一个或多个元素加入到集合key当中，已经存在于集合的元素将被忽略
     * @param string $key 键值
     * @param string|array $values 元素
     * @return mixed|int
     */
    public function sAdd($key, $values){
        if(!is_array($values)){
            $values = array($values);
        }
        array_unshift($values, $this->getKey($key));
        return eval($this->getEvalStringValue('sAdd', $values));
    }

    /**
     * 移除集合key中的一个或多个元素，不存在的元素会被忽略。
     * @param string $key 键值
     * @param string|array $values 元素
     * @return mixed|int
     */
    public function sRem($key, $values){
        if(!is_array($values)){
            $values = array($values);
        }
        array_unshift($fields, $this->getKey($key));
        return eval($this->getEvalStringValue('sRem', $values));
    }

    /**
     * 返回集合key中的所有成员
     * @param string $key 键值
     * @return array
     */
    public function sMembers($key){
        return $this->redis->sMembers($this->getKey($key));
    }

    /**
     * 判断元素是否是集合key的成员
     * @param string $key 键值
     * @param string $value 元素
     * @return bool
     */
    public function sIsMember($key, $value){
        return $this->redis->sIsMember($this->getKey($key), $value);
    }

    /**
     * 返回集合key的基数(集合中元素的数量)
     * @param string $key 键值
     * @return int
     */
    public function sCard($key){
        return $this->redis->sCard($this->getKey($key));
    }

    /**
     * 返回一个集合的全部成员，该集合是所有给定集合的交集
     * @param string|array $keys 键值
     * @return array
     */
    public function sInter($keys){
        return eval($this->getEvalStringKey('sInter', $keys));
    }

    /**
     * 返回一个集合的全部成员，该集合是所有给定集合的并集
     * @param string|array $keys 键值
     * @return array
     */
    public function sUnion($keys){
        return eval($this->getEvalStringKey('sUnion', $keys));
    }

    /**
     * 返回一个集合的全部成员，该集合是所有给定集合的差集
     * @param string|array $keys 键值
     * @return array
     */
    public function sDiff($keys){
        return eval($this->getEvalStringKey('sDiff', $keys));
    }

    /** --------------------  有序集合类型  ---------------------------- **/

    /**
     * 将一个或多个元素加入到有序集key当中，已经存在于集合的元素将被忽略
     * @param string $key 键值
     * @param int|float|array $score score值或者是多个score和member的数组
     * @param string $member 成员
     * @return mixed|int
     */
    public function zAdd($key, $score, $member=null){
        if(!is_array($score)){
            $score = array($score, $member);
        }
        array_unshift($score, $this->getKey($key));
        return eval($this->getEvalStringValue('zAdd', $score));
    }

    /**
     * 移除有序集key中的一个或多个元素，不存在的元素会被忽略。
     * @param string $key 键值
     * @param string|array $values 元素
     * @return mixed|int
     */
    public function zRem($key, $values){
        if(!is_array($values)){
            $values = array($values);
        }
        array_unshift($values, $this->getKey($key));
        return eval($this->getEvalStringValue('zRem', $values));
    }

    /**
     * 返回有序集key的基数(集合中元素的数量)
     * @param string $key 键值
     * @return int
     */
    public function zCard($key){
        return $this->redis->zCard($this->getKey($key));
    }

    /**
     * 返回有序集key中，score值在min和max之间(默认包括score值等于min或max)的成员数量
     * @param string $key 键值
     * @param int|float $min min
     * @param int|float $max max
     * @return array
     */
    public function zCount($key, $min, $max){
        return $this->redis->zCount($this->getKey($key), $min, $max);
    }

    /**
     * 返回有序集key中，成员member的score值
     * @param string $key 键值
     * @param string $member 成员
     * @return float
     */
    public function zScore($key, $member){
        return $this->redis->zScore($this->getKey($key), $member);
    }

    /**
     * 为有序集key的成员member的score值加上增量
     * @param string $key 键值
     * @param int|float $increment 增量
     * @param string $member 成员
     * @return string
     */
    public function zIncrBy($key, $increment, $member){
        return $this->redis->zIncrBy($this->getKey($key), $increment, $member);
    }

    /**
     * 返回有序集key中，第start个到第end个区间内的成员,其中成员的位置按score值递增(从小到大)来排序
     * @param string $key 键值
     * @param int|float $start start
     * @param int|float $end end
     * @param array $withscores 是否需要返回score值
     * @return array
     */
    public function zRange($key, $start, $end, $withscores=null){
        return $this->redis->zRange($this->getKey($key), $start, $end, $withscores);
    }

    /**
     * 返回有序集key中，第start个到第end个区间内的成员,其中成员的位置按score值递减(从大到小)来排序
     * @param string $key 键值
     * @param int|float $start start
     * @param int|float $end end
     * @param array $withscores 是否需要返回score值
     * @return array
     */
    public function zRevRange($key, $start, $end, $withscores=null){
        return $this->redis->zRevRange($this->getKey($key), $start, $end, $withscores);
    }

    /**
     * 返回有序集key中，所有score值介于min和max之间(包括等于min或max)的成员,有序集成员按score值递增(从小到大)次序排列
     * @param string $key 键值
     * @param int|float $min min
     * @param int|float $max max
     * @param array $options 可选项('withscores' => TRUE,'limit' => array(1, 1))
     * @return array
     */
    public function zRangeByScore($key, $min, $max, $options=array()){
        return $this->redis->zRangeByScore($this->getKey($key), $min, $max, $options);
    }

    /**
     * 返回有序集key中，所有score值介于min和max之间(包括等于min或max)的成员,有序集成员按score值递减(从大到小)次序排列
     * @param string $key 键值
     * @param int|float $min min
     * @param int|float $max max
     * @param array $options 可选项('withscores' => TRUE,'limit' => array(1, 1))
     * @return array
     */
    public function zRevRangeByScore($key, $min, $max, $options=array()){
        return $this->redis->zRevRangeByScore($this->getKey($key), $min, $max, $options);
    }

    /**
     * 返回有序集key中，所有成员的字典序介于min和max之间(包括等于min或max)的成员,有序集成员按score值递增(从小到大)次序排列
     * @param string $key 键值
     * @param int|float $min min
     * @param int|float $max max
     * @return array
     */
    public function zRangeByLex($key, $min, $max){
        return $this->redis->zRangeByLex($this->getKey($key), $min, $max);
    }

    /**
     * 返回有序集key中，所有成员的字典序介于min和max之间(包括等于min或max)的成员,有序集成员按score值递减(从大到小)次序排列
     * @param string $key 键值
     * @param int|float $min min
     * @param int|float $max max
     * @return array
     */
    public function zRevRangeByLex($key, $min, $max){
        return $this->redis->zRevRangeByLex($this->getKey($key), $min, $max);
    }

    /** --------------------  列表类型  ---------------------------- **/

    /**
     * 将一个或多个值value插入到列表key的表头
     * @param string $key 键值
     * @param string|array $values 值
     * @return mixed|int
     */
    public function lPush($key, $values){
        if(!is_array($values)){
            $values = array($values);
        }
        array_unshift($values, $this->getKey($key));
        return eval($this->getEvalStringValue('lPush', $values));
    }

    /**
     * 将一个或多个值value插入到列表key的表头，当且仅当key存在并且是一个列表
     * @param string $key 键值
     * @param string|array $value 值
     * @return mixed|int
     */
    public function lPushx($key, $value){
        return $this->redis->lPushx($this->getKey($key), $value);
    }

    /**
     * 将一个或多个值value插入到列表key的表尾
     * @param string $key 键值
     * @param string|array $values 值
     * @return mixed|int
     */
    public function rPush($key, $values){
        if(!is_array($values)){
            $values = array($values);
        }
        array_unshift($values, $this->getKey($key));
        return eval($this->getEvalStringValue('rPush', $values));
    }

    /**
     * 将一个或多个值value插入到列表key的表尾，当且仅当key存在并且是一个列表
     * @param string $key 键值
     * @param string|array $value 值
     * @return mixed|int
     */
    public function rPushx($key, $value){
        return $this->redis->rPushx($this->getKey($key), $value);
    }

    /**
     * 移除并返回列表key的头元素
     * @param string $key 键值
     * @return mixed|string|array
     */
    public function lPop($key){
        return $this->redis->lPop($this->getKey($key));
    }

    /**
     * 移除并返回列表key的尾元素
     * @param string $key 键值
     * @return mixed|string|array
     */
    public function rPop($key){
        return $this->redis->rPop($this->getKey($key));
    }

    /**
     * 移除并返回列表key的头元素,阻塞版本
     * @param string|array $keys 键值
     * @param int $timeout timeout时间
     * @return mixed|string|array
     */
    public function blPop($keys, $timeout=10){
        if(!is_array($keys)){
            $keys = array($this->getKey($keys));
        }else{
            foreach ($keys as &$k){
                $k = $this->getKey($k);
            }
            unset($k);
        }
        return $this->redis->blPop($keys, $timeout);
    }

    /**
     * 移除并返回列表key的尾元素,阻塞版本
     * @param string|array $keys 键值
     * @param int $timeout timeout时间
     * @return mixed|string|array
     */
    public function brPop($keys, $timeout=10){
        if(!is_array($keys)){
            $keys = array($this->getKey($keys));
        }else{
            foreach ($keys as &$k){
                $k = $this->getKey($k);
            }
            unset($k);
        }
        return $this->redis->brPop($keys, $timeout);
    }

    /**
     * 返回列表key的长度(key不存在返回0,key不是列表类型返回错误)
     * @param string $key 键值
     * @return int|bool
     */
    public function lLen($key){
        return $this->redis->lLen($this->getKey($key));
    }

    /**
     * 通过索引设置列表元素的值
     * @param string $key 键值
     * @param int $index 索引
     * @return String
     */
    public function lGet($key, $index){
        return $this->redis->lIndex($this->getKey($key), $index);
    }

    /**
     * 通过索引设置列表元素的值
     * @param string $key 键值
     * @param int $index 索引
     * @param string $value 值
     * @return bool
     */
    public function lSet($key, $index, $value){
        return $this->redis->lSet($this->getKey($key), $index, $value);
    }

    /**
     * 获取列表指定范围内的元素
     * @param string $key 键值
     * @param int $start 开始
     * @param int $end 结束
     * @return array
     */
    public function lRange($key, $start, $end){
        return $this->redis->lRange($this->getKey($key), $start, $end);
    }

    /**
     * 对一个列表进行修剪(trim),让列表只保留指定区间内的元素,不在指定区间之内的元素都将被删除
     * @param string $key 键值
     * @param int $start 开始
     * @param int $stop 结束
     * @return array
     */
    public function lTrim($key, $start, $stop){
        return $this->redis->lTrim($this->getKey($key), $start, $stop);
    }

    /**
     * 根据参数count的值，移除列表中与参数value相等的元素
     * count > 0: 从表头开始向表尾搜索，移除与value相等的元素，数量为count
     * count < 0: 从表尾开始向表头搜索，移除与value相等的元素，数量为count的绝对值
     * count = 0: 移除表中所有与value相等的值
     * @param string $key 键值
     * @param string $value 值
     * @param int $count 数量
     * @return int
     */
    public function lRem($key, $value, $count){
        return $this->redis->lRem($this->getKey($key), $value, $count);
    }

    /**
     * 将值value插入到列表key当中，位于值pivot之前或之后
     * @param string $key 键值
     * @param string $position 位置(Redis::AFTER/Redis::BEFORE)
     * @param string $pivot pivot值
     * @param string $value 值
     * @return int
     */
    public function lInsert($key, $position, $pivot, $value){
        return $this->redis->lInsert($this->getKey($key), $position, $pivot, $value);
    }

}