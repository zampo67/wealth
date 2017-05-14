<?php

class MBaseModel {
    protected $_table;
    private static $_models=array();

    public function __construct(){

    }

    /**
     * 实例化方法
     * @param string $className 类名
     * @return mixed
     */
    public static function model($className){
        if(isset(self::$_models[$className])){
            return self::$_models[$className];
        } else {
            $model = self::$_models[$className] = new $className();
            return $model;
        }
    }

    public function MGetInfoById($id, $field='id'){
        if(empty($id) || !is_numeric($id)){
            return false;
        }
        return $this->MFind(array(
            'field' => $field,
            'where' => array('id'=>$id),
        ));
    }

    /**
     * 获取单个数据
     * @param array $options 条件
     * @param int $status 是否判断status
     * @param int $is_del 是否判断is_del
     * @return mixed|array
     */
    public function MFind($options=array(), $status=1, $is_del=1){
        if(is_numeric($options)){
            $id = $options;
            $options = array();
            $options['where'] = array(
                'id' => $id,
            );
        }
        $field = isset($options['field']) ? $options['field'] : '*';
        $group = isset($options['group']) ? 'GROUP BY '.$options['group'] : '';
        $order = isset($options['order']) ? 'ORDER BY '.$options['order'] : '';
        $param = array();
        if(!empty($options['where']) && is_array($options['where'])){
            list($where, $param) = $this->parseWhere($options['where']);
            if($status == 1){
                $where[] = "status=:status";
                $param[':status'] = '1';
            }
            if($is_del == 1){
                $where[] = "is_del=:is_del";
                $param[':is_del'] = '0';
            }
            $where = implode(' AND ', $where);
        }else{
            $where = isset($options['where']) ? $options['where'] : '1=1';
            if($status == 1){
                $where .= " AND status='1'";
            }
            if($is_del == 1){
                $where .= " AND is_del='0'";
            }
        }
        $sql = "SELECT {$field} FROM ".$this->_table." WHERE {$where} {$group} {$order}";
        return $this->MFindBySql($sql, $param);
    }

    /**
     * 解析where数组为字符串
     * @param array $option 条件
     * @param string $prefix 前缀
     * @return string
     */
    public function parseWhere($option, $prefix=''){
        return Db::getInstance()->parseWhere($option, $prefix);
    }

    /**
     * 获取多条数据
     * @param array $options 条件
     * @param int $all_num 是否需要总条数
     * @param int $status 是否判断status
     * @param int $is_del 是否判断is_del
     * @return mixed|array
     */
    public function MFindAll($options=array(), $all_num=0, $status=1, $is_del=1){
        $field = isset($options['field']) ? $options['field'] : '*';
        $field = ($all_num == 1) ? 'SQL_CALC_FOUND_ROWS '.$field : $field;
        $group = isset($options['group']) ? 'GROUP BY '.$options['group'] : '';
        $order = isset($options['order']) ? $options['order'] : 'id DESC';
        $limit = isset($options['limit']) ? 'LIMIT '.$options['limit'] : '';
        $param = array();
        if(!empty($options['where']) && is_array($options['where'])){
            list($where, $param) = $this->parseWhere($options['where']);
            if($status == 1){
                $where[] = "status=:status";
                $param[':status'] = '1';
            }
            if($is_del == 1){
                $where[] = "is_del=:is_del";
                $param[':is_del'] = '0';
            }
            $where = implode(' AND ', $where);
        }else{
            $where = isset($options['where']) ? $options['where'] : '1=1';
            if($status == 1){
                $where .= " AND status='1'";
            }
            if($is_del == 1){
                $where .= " AND is_del='0'";
            }
        }
        $sql = "SELECT {$field} FROM ".$this->_table." WHERE {$where} {$group} ORDER BY {$order} {$limit}";
        return $this->MFindAllBySql($sql, $param);
    }

    /**
     * 通过sql获取单条数据
     * @param string $sql sql语句
     * @param array $param 参数
     * @return mixed|array
     */
    public function MFindBySql($sql, $param=array()){
        return Db::getInstance()->fetch($sql, $param);
    }

    /**
     * 通过sql获取多条数据
     * @param string $sql sql语句
     * @param array $param 参数
     * @return mixed|array
     */
    public function MFindAllBySql($sql, $param=array()){
        return Db::getInstance()->fetchAll($sql, $param);
    }

    /**
     * 获取总条数
     * @return mixed
     */
    public function MGetCount(){
        $sql = "SELECT FOUND_ROWS() AS count";
        $res = Db::getInstance()->fetch($sql);
        return $res['count'];
    }

    /**
     * 执行sql语句
     * @param string $sql sql语句
     * @param array $param 参数
     * @return mixed
     */
    public function MExecute($sql, $param=array()){
        return Db::getInstance()->execute($sql, $param);
    }

    /**
     * 保存数据
     * @param array $data 数据
     * @param int $ctime 是否添加ctime
     * @param int $mtime 是否添加mtime
     * @return bool|int
     */
    public function MSave($data, $ctime=1, $mtime=1){
        if(!empty($data)){
            $now_time = time();
            if(!empty($data['id']) && is_numeric($data['id'])){
                //修改
                $id = $data['id'];
                unset($data['id']);
                if($mtime == 1){
                    $data['mtime'] = $now_time;
                }
                $res = Db::getInstance()->update($this->_table, $data, array('id'=>$id));
                return !empty($res) ? $id : false;
            }else{
                //新增
                if($ctime == 1){
                    $data['ctime'] = $now_time;
                }
                if($mtime == 1){
                    $data['mtime'] = $now_time;
                }
                if(isset($data['id'])){
                    unset($data['id']);
                }
                $res = Db::getInstance()->insert($this->_table, $data);
                return !empty($res) ? Db::getInstance()->Insert_ID() : false;
            }
        }else{
            return false;
        }
    }

    /**
     * 插入多条数据
     * @param array $data 数据
     * @return bool
     */
    public function MInsertMulti($data){
        return (!empty($data) && is_array($data)) ? Db::getInstance()->insert($this->_table, $data) : false;
    }

    /**
     * 更新数据
     * @param array $data 数据
     * @param array $options 条件
     * @param int $status 是否判断status
     * @param int $is_del 是否判断is_del
     * @return bool
     */
    public function MUpdate($data, $options,$status=1,$is_del=1){
        if(empty($data) || empty($options)){
            return false;
        }
        if(is_numeric($options)){
            $where = array(
                'id' => $options,
            );
        }else{
            $where = $options;
        }

        if(is_array($where)){
            if($status==1){
                $where['status'] = '1';
            }
            if($is_del == 1){
                $where['is_del'] = '0';
            }
        }else{
            if($status==1){
                $where .= " AND status='1'";
            }
            if($is_del == 1){
                $where .= " AND is_del='0'";
            }
        }

        $data['mtime'] = time();
        return Db::getInstance()->update($this->_table, $data, $where);
    }

    /**
     * 逻辑删除数据
     * @param array $where 条件
     * @return bool
     */
    public function MDel($where){
        if(empty($where)){
            return false;
        }
        if(is_numeric($where)){
            $id = $where;
            $where = array();
            $where['id'] = $id;
        }
        $where['is_del'] = '0';
        return Db::getInstance()->update($this->_table, array(
            'dtime' => time(),
            'is_del' => '1',
        ), $where);
    }

    /**
     * 物理删除数据
     * @param array $where 条件
     * @return bool
     */
    public function MDestroy($where){
        if(empty($where)){
            return false;
        }
        return Db::getInstance()->delete($this->_table, $where);
    }

    /**
     * 某字段值+1
     * @param string $field 字段
     * @param array $options 条件
     * @return bool
     */
    public function MPlusField($field, $options){
        return !empty($field) ? $this->MPlusMulti(array($field=>1), $options) : false;
    }

    /**
     * 多个字段增加数值
     * @param $data
     * @param $options
     * @return bool
     */
    public function MPlusMulti($data, $options){
        if(empty($data) || empty($options)){
            return false;
        }
        if(is_numeric($options)){
            $where = 'id=:id';
            $param = array(':id'=>$options);
        }else{
            list($where, $param) = $this->parseWhere($options);
            $where = implode(' AND ', $where);
        }
        $field = '';
        foreach ($data as $k=>$v){
            $field[] = "{$k}={$k}+$v";
        }
        $field = implode(',', $field);
        $sql = "UPDATE ".$this->_table." SET {$field} WHERE {$where}";
        return Db::getInstance()->execute($sql, $param);
    }

    /**
     * 多个字段减少数值
     * @param $data
     * @param $options
     * @return bool
     */
    public function MMinusMulti($data, $options){
        if(empty($data) || empty($options)){
            return false;
        }
        if(is_numeric($options)){
            $where = 'id=:id';
            $param = array(':id'=>$options);
        }else{
            list($where, $param) = $this->parseWhere($options);
            $where = implode(' AND ', $where);
        }
        $field = '';
        foreach ($data as $k=>$v){
            $field[] = "{$k}={$k}-$v";
        }
        $field = implode(',', $field);
        $sql = "UPDATE ".$this->_table." SET {$field} WHERE {$where}";
        return Db::getInstance()->execute($sql, $param);
    }

    /**
     * 某字段值-1
     * @param string $field 字段
     * @param array $options 条件
     * @return bool
     */
    public function MMinusField($field, $options){
        return !empty($field) ? $this->MMinusMulti(array($field=>1), $options) : false;
    }

    public function MGetEmptyFields(){
        $sql = "SHOW COLUMNS FROM ".$this->_table;
        $list = Db::getInstance()->executeS($sql);
        $res = array();
        foreach ($list as $l){
            $res[$l['Field']] = $l['Default'];
        }
        return $res;
    }

    /**
     * 向日期添加指定的时间间隔计算
     * @param string|int $date 合法的日期表达式
     * @param int $unit 间隔类型,如WEEK/MONTH/YEAR
     * @param int $num 时间间隔
     * @return bool|string
     */
    public function MGetDateAdd($date, $unit, $num=1){
        $row = $this->MFindBySql("SELECT DATE_ADD('{$date}', INTERVAL {$num} {$unit}) AS date_add");
        return !empty($row['date_add']) ? $row['date_add'] : false;
    }

    /**
     * 日期减去指定的时间间隔计算
     * @param string|int $date 合法的日期表达式
     * @param int $unit 间隔类型,如WEEK/MONTH/YEAR
     * @param int $num 时间间隔
     * @return bool|string
     */
    public function MGetDateSub($date, $unit, $num=1){
        $row = $this->MFindBySql("SELECT DATE_SUB('{$date}', INTERVAL {$num} {$unit}) AS date_sub");
        return !empty($row['date_sub']) ? $row['date_sub'] : false;
    }

    /**
     * 获取表名
     * @param string $table 表名
     */
    public function setTable($table){
        $this->_table = '{{'.$table.'}}';
    }

    /**
     * 设置表名
     * @return string 表名
     */
    public function getTable(){
        return str_replace(array('{{','}}'),'',$this->_table);
    }

    /**
     * Model规则
     * @return array
     */
    public function rules(){
        return array();
    }

    public function MCheckEmptyRules($value, $type){
        switch ($type){
            case 'empty':
            default:
                return empty($value);
                break;
            case 'empty_string':
                return $value == '';
                break;
        }
    }

    /**
     * 验证数据
     * @param array $data 需验证的数据(引用传递,所以调用时必须要定义一个变量)
     * @param array $options 配置项(enable=>启用验证的字段,disable=>关闭验证的字段)
     * @param array $rule 覆盖规则
     * @return array 错误码和错误信息
     */
    public function MVerificationRules(&$data, $options=array(), $rule=array()){
        // 获取规则
        $rules = (!empty($rule) && is_array($rule)) ? $rule : $this->rules();
        // 有需要开启的部分字段则只验证开启的字段
        if(!empty($options['enable'])){
            if(is_string($options['enable'])){
                $options['enable'] = array($options['enable']);
            }
            if(is_array($options['enable'])){
                $rules = array_intersect_key($rules, array_flip($options['enable']));
            }
        }
        // 有需要关闭验证的部分字段则去除关闭的字段的验证
        if(!empty($options['disable'])){
            if(is_string($options['disable'])){
                $options['disable'] = array($options['disable']);
            }
            if(is_array($options['disable'])){
                $rules = array_diff_key($rules, array_flip($options['disable']));
            }
        }

        $module = $this->getTable();
        $code = $msg = '';
        foreach ($rules as $f=>$r){
            $value = '';
            if(isset($data[$f])){
                if(!empty($r['filter_func']) && is_array($r['filter_func'])){
                    foreach ($r['filter_func'] as $func){
                        $data[$f] = call_user_func($func, $data[$f]);
                    }
                }
                $value = $data[$f];
            }
            foreach ($r as $k=>$v){
                switch ($k){
                    case 'empty':
                    case 'empty_string':
                        if($this->MCheckEmptyRules($value, $k)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $empty_type = 'empty'.(!empty($v['type']) ? '_'.$v['type'] : '');
                                switch ($empty_type){
                                    case 'empty':
                                    default:
                                        $msg = I18n::getInstance()->getErrorEmpty($module, $f);
                                        break;
                                    case 'empty_input':
                                        $msg = I18n::getInstance()->getErrorEmptyInput($module, $f);
                                        break;
                                    case 'empty_select':
                                        $msg = I18n::getInstance()->getErrorEmptySelect($module, $f);
                                        break;
                                }
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'min_length':
                        if(!empty($value)){
                            $length = Common::strlenFull($value);
                            if($length < $v['length']){
                                if(!empty($v['msg'])){
                                    $msg = $v['msg'];
                                }elseif(empty($v['user_error'])){
                                    $msg = I18n::getInstance()->getErrorMinLength($module, $f, $v['length']);
                                }else{
                                    $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                                }
                                $code = !empty($v['code']) ? $v['code'] : $f;
                            }
                        }
                        break;
                    case 'max_length':
                        if(!empty($value)){
                            $length = Common::strlenFull($value);
                            if($length > $v['length']){
                                if(!empty($v['msg'])){
                                    $msg = $v['msg'];
                                }elseif(empty($v['user_error'])){
                                    $msg = I18n::getInstance()->getErrorMaxLength($module, $f, $v['length']);
                                }else{
                                    $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                                }
                                $code = !empty($v['code']) ? $v['code'] : $f;
                            }
                        }
                        break;
                    case 'min_num':
                        if(!empty($value) && count($value) < $v['num']){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorMinNum($module, $f, $v['num']);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'max_num':
                        if(!empty($value) && count($value) > $v['num']){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorMaxNum($module, $f, $v['num']);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'in_array':
                        if(!empty($value) && !in_array($value, $v['array'])){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'array':
                        if(!empty($value) && !is_array($value)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'string':
                        if(!empty($value) && !is_string($value)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'number':
                        if(!empty($value) && !is_numeric($value)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'float':
                        if(!empty($value)){
                            $count = 0;
                            $temp = explode('.', $value);
                            if(count($temp) > 1){
                                $decimal = end($temp);
                                $count = strlen($decimal);
                            }

                            if($count > $v['length']){
                                if(!empty($v['msg'])){
                                    $msg = $v['msg'];
                                }elseif(empty($v['user_error'])){
                                    $msg = I18n::getInstance()->getErrorCommon($k);
                                }else{
                                    $msg = I18n::getInstance()->getError($module, $f . '_' . $k);
                                }
                                $code = !empty($v['code']) ? $v['code'] : $f;
                            }
                        }
                        break;
                    case 'mobile':
                        if(!empty($value) && !Common::isMobile($value)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'email':
                        if(!empty($value) && !Common::isEmail($value)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'wechat':
                        if(!empty($value) && !Common::isWechat($value)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon($k);
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    case 'year':
                        if(!empty($value)){
                            $value = intval($value);
                            if($value<1970 || $value>2030){
                                if(!empty($v['msg'])){
                                    $msg = $v['msg'];
                                }elseif(empty($v['user_error'])){
                                    $msg = I18n::getInstance()->getErrorCommon($k);
                                }else{
                                    $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                                }
                                $code = !empty($v['code']) ? $v['code'] : $f;
                            }
                        }
                        break;
                    case 'month':
                        if(!empty($value)){
                            $value = intval($value);
                            if($value<1 || $value>12){
                                if(!empty($v['msg'])){
                                    $msg = $v['msg'];
                                }elseif(empty($v['user_error'])){
                                    $msg = I18n::getInstance()->getErrorCommon($k);
                                }else{
                                    $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                                }
                                $code = !empty($v['code']) ? $v['code'] : $f;
                            }
                        }
                        break;
                    case 'user_func':
                        $res = call_user_func(array('self', $v['function']));
                        if(empty($res)){
                            if(!empty($v['msg'])){
                                $msg = $v['msg'];
                            }elseif(empty($v['user_error'])){
                                $msg = I18n::getInstance()->getErrorCommon('default');
                            }else{
                                $msg = I18n::getInstance()->getError($module, $f.'_'.$k);
                            }
                            $code = !empty($v['code']) ? $v['code'] : $f;
                        }
                        break;
                    default :
                        break;
                }
                if(!empty($code)){
                    return array('code'=>$code,'msg'=>$msg);
                }
            }
        }
        return array('code'=>$code,'msg'=>$msg);
    }

}