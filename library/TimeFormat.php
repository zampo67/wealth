<?php

/**
 * Created by PhpStorm.
 * User: James
 * Date: 15/01/2017
 * Time: 16:04
 */
class TimeFormat{
    protected static $instance='';

    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct(){

    }

    /**
     * 获取日期相关数据
     * @param string|int $time 日期或者时间戳
     * @param string $time_type 时间类型
     * @return array|bool
     */
    public function getDbLogLimitDate($time='', $time_type='hour'){
        if(!is_numeric($time)){
            $time = strtotime($time);
        }
        if(empty($time)){
            $time = time();
        }
        switch ($time_type){
            case 'hour':
                $time = strtotime('-1 hours', $time);
                $res = array(
                    'type_id' => VariablesModel::model()->getAttrs('logTimeType', $time_type, 'id'),
                    'year' => date('Y', $time),
                    'month' => date('m', $time),
                    'day' => date('d', $time),
                    'hour' => date('H', $time),
                );
                break;
            case 'day':
                $time = strtotime('-1 days', $time);
                $res = array(
                    'type_id' => VariablesModel::model()->getAttrs('logTimeType', $time_type, 'id'),
                    'year' => date('Y', $time),
                    'month' => date('m', $time),
                    'day' => date('d', $time),
                );
                break;
            default:
                $res = false;
                break;
        }
        return $res;
    }

    /**
     * 获取时间相关数据
     * @param array $date 日期数据
     * @param string $time_type 时间类型
     * @param int $time_interval 时间间隔
     * @return array|bool
     */
    public function getDbLogLimitTime($date, $time_type='hour', $time_interval=0){
        if(!empty($date) && is_array($date)){
            $res = array();
            switch ($time_type){
                case 'hour':
                    $res['end_time'] = strtotime("{$date['year']}/{$date['month']}/{$date['day']} {$date['hour']}:00:00") + $this->getTimeByInterval($time_type);
                    $res['start_time'] = $res['end_time'] - $this->getTimeByInterval($time_type, $time_interval);
                    break;
                case 'day':
                    $res['end_time'] = strtotime("{$date['year']}/{$date['month']}/{$date['day']} 00:00:00") + $this->getTimeByInterval($time_type);
                    $res['start_time'] = $res['end_time'] - $this->getTimeByInterval($time_type, $time_interval);
                    break;
                default:
                    $res = false;
                    break;
            }
            return $res;
        }else{
            return false;
        }
    }

    /**
     * 获取时间周期内的时间戳数
     * @param string $time_type 时间类型
     * @param int $time_interval 时间间隔
     * @return bool|int
     */
    public function getTimeByInterval($time_type='hour', $time_interval=1){
        if(empty($time_interval) || !is_numeric($time_interval) || $time_interval<=0){
            $time_interval = 1;
        }
        switch ($time_type){
            case 'hour':
                return $time_interval * 3600;
                break;
            case 'day':
                return $time_interval * 86400;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 获取某天的0点0分0秒时间戳
     * @param string|int $time 时间格式或时间戳
     * @return int
     */
    public function getDayZeroTime($time=''){
        if(empty($time)){
            $time = date('Y-m-d');
        }elseif(is_numeric($time)){
            $time = date('Y-m-d', $time);
        }
        return strtotime($time);
    }

}