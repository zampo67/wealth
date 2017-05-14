<?php

/**
 * 多语言支持
 * Created by James.
 * User: James
 * Date: 08/11/2016
 * Time: 13:55
 */
class I18n{
    protected static $instance='';
    protected $_i18n_path = '';
    protected $_locale = 'zh_CN';
    protected $_file_path = '';
    protected $_data = array();

    /**
     * I18n constructor.设置默认语言
     */
    private function __construct(){
        defined('I18N_PATH') or define('I18N_PATH', '');
        $this->_i18n_path = I18N_PATH;
        $this->setData();
    }

    /**
     * 实例化方法
     * @return I18n|string
     */
    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取资源文件(json)
     * @param string $i18n
     * @return string
     */
    private function getFilePath($i18n){
        return $this->_i18n_path.$i18n.'.json';
    }

    /**
     * 设置数据
     */
    private function setData(){
        $file_path = $this->getFilePath($this->_locale);
        if(file_exists($file_path)){
            $this->_data = json_decode(file_get_contents($file_path), true);
        }
    }

    /**
     * 获取有module层级的值
     * @param string $type 类型
     * @param string $module 模块
     * @param string|null $key 键值
     * @return array|string
     */
    private function getModuleValue($type, $module, $key=null){
        if(!is_null($key)){
            return isset($this->_data[$type][$module][$key]) ? $this->_data[$type][$module][$key] : '';
        }else{
            return isset($this->_data[$type][$module]) ? $this->_data[$type][$module] : array();
        }
    }

    /**
     * 获取值
     * @param string $type 类型
     * @param string|null $key 键值
     * @return array|string
     */
    private function getValue($type, $key=null){
        if(!is_null($key)){
            return isset($this->_data[$type][$key]) ? $this->_data[$type][$key] : '';
        }else{
            return isset($this->_data[$type]) ? $this->_data[$type] : array();
        }
    }

    /**
     * 获取当前的语言
     * @return string
     */
    public function getI18n(){
        return $this->_locale;
    }

    /**
     * 设置语言
     * @param string $i18n
     * @return bool
     */
    public function setI18n($i18n){
        if(file_exists($this->getFilePath($i18n))){
            $this->_locale = $i18n;
            $this->setData();
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取title值
     * @param string|null $key 键值
     * @return array|string
     */
    public function getTitle($key=null){
        return $this->getValue('title', $key);
    }

    /**
     * 通过module_key获取title值
     * @param string $key 键值
     * @return array|string
     */
    public function getTitleByModuleKey($key){
        return $this->getValue('title', 'module_'.$key);
    }

    /**
     * 获取button值
     * @param string|null $key 键值
     * @return array|string
     */
    public function getButton($key=null){
        return $this->getValue('button', $key);
    }

    /**
     * 获取other值
     * @param string|null $key 键值
     * @param array $option 要替换的值
     * @return array|string
     */
    public function getOther($key=null, $option=array()){
        $value = $this->getValue('other', $key);
        if(!empty($option) && is_array($option) && is_string($value)){
            $search = $replace = array();
            foreach ($option as $k=>$v){
                $search[] = '${'.$k.'}';
                $replace[] = $v;
            }
            $value = str_replace($search, $replace, $value);
        }
        return $value;
    }

    /**
     * 获取label值
     * @param string $module 模块
     * @param string|null $key 键值
     * @return array|string
     */
    public function getLabel($module, $key=null){
        return $this->getModuleValue('label', $module, $key);
    }

    /**
     * 获取placeholder值
     * @param string $module 模块
     * @param string|null $key 键值
     * @return array|string
     */
    public function getPlaceholder($module, $key=null){
        return $this->getModuleValue('placeholder', $module, $key);
    }

    /**
     * 获取error值
     * @param string $module 模块
     * @param string|null $key 键值
     * @return array|string
     */
    public function getError($module, $key=null){
        return $this->getModuleValue('error', $module, $key);
    }

    /**
     * 获取Variables值
     * @param $module
     * @param null $key
     * @return array|string
     */
    public function getVariables($module, $key=null){
        return $this->getModuleValue('variables', $module, $key);
    }

    /**
     * 获取code相关的error值
     * @param string $key 键值
     * @return array|string
     */
    public function getErrorCode($key){
        return $this->getError('code', $key);
    }

    /**
     * 获取公共的error值
     * @param string $key 键值
     * @param array $search 要替换的值
     * @param array $replace 替换后的值
     * @return array|string
     */
    public function getErrorCommon($key, $search=array(), $replace=array()){
        if(!empty($search) && !empty($replace)){
            return str_replace($search, $replace, $this->getError('common', $key));
        }else{
            return $this->getError('common', $key);
        }
    }

    /**
     * 获取控制器层的error值
     * @param string $key 键值
     * @return array|string
     */
    public function getErrorController($key){
        return $this->getError('controller', $key);
    }

    /**
     * 获取最小长度的error值
     * @param string $module 模块
     * @param string $key 键值
     * @param int $length 长度
     * @return array|string
     */
    public function getErrorMinLength($module, $key, $length){
        return $this->getErrorCommon('min_length', array('${label}', '${value}'), array($this->getLabel($module, $key), $length));
    }

    /**
     * 获取最大长度的error值
     * @param string $module 模块
     * @param string $key 键值
     * @param int $length 长度
     * @return array|string
     */
    public function getErrorMaxLength($module, $key, $length){
        return $this->getErrorCommon('max_length', array('${label}', '${value}'), array($this->getLabel($module, $key), $length));
    }

    /**
     * 获取最小数量的error值
     * @param string $module 模块
     * @param string $key 键值
     * @param int $num 数量
     * @return array|string
     */
    public function getErrorMinNum($module, $key, $num){
        return $this->getErrorCommon('min_num', array('${label}', '${value}'), array($this->getLabel($module, $key), $num));
    }

    /**
     * 获取最大数量的error值
     * @param string $module 模块
     * @param string $key 键值
     * @param int $num 数量
     * @return array|string
     */
    public function getErrorMaxNum($module, $key, $num){
        return $this->getErrorCommon('max_num', array('${label}', '${value}'), array($this->getLabel($module, $key), $num));
    }

    /**
     * 获取为空的error值
     * @param string $module 模块
     * @param string $key 键值
     * @return array|string
     */
    public function getErrorEmpty($module, $key){
        return $this->getErrorCommon('empty', array('${label}'), array($this->getLabel($module, $key)));
    }

    /**
     * 获取输入为空的error值
     * @param string $module 模块
     * @param string $key 键值
     * @return array|string
     */
    public function getErrorEmptyInput($module, $key){
        return $this->getErrorCommon('empty_input', array('${label}'), array($this->getLabel($module, $key)));
    }

    /**
     * 获取选择为空的error值
     * @param string $module 模块
     * @param string $key 键值
     * @return array|string
     */
    public function getErrorEmptySelect($module, $key){
        return $this->getErrorCommon('empty_select', array('${label}'), array($this->getLabel($module, $key)));
    }

}