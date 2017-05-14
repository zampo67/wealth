<?php

class LogUserSumModel extends MBaseModel {
    protected $_table = '{{log_user_sum}}';
    protected $_table_user = '{{user}}';
    protected $_table_resume = '{{resume}}';
    protected $_table_company = '{{company_baseinfo}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function getDate($time=''){
        if(empty($time)){
            $time = time();
        }

        $res = array(
            'year' => date('Y',$time),
            'month' => date('m',$time),
            'day' => date('d',$time),
            'hour' => date('H',$time),
        );
        return $res;
    }

    public function getReportData($options){
        $time_type_id = (!empty($options['time_type_id'])) ? $options['time_type_id'] : 0;
        $start_time = (!empty($options['start_time'])) ? $options['start_time'] : 0;
        $end_time = (!empty($options['end_time'])) ? $options['end_time'] : 0;

        if(!empty($time_type_id) && !empty($start_time) && !empty($end_time)){
            $where = "ctime>$start_time";
            $today_start = mktime(0,0,0);
            $time_str = '%Y/%m/%d';
            $flag = true;
            switch($time_type_id){
                case 1: // 最近七天
                case 2: // 最近30天
                    $where .= " AND hour=0";
                    break;
                case 3: // 最近半年
                    $where .= " AND day=1 AND hour=0";
                    $time_str = '%Y/%m';
                    break;
                case 99: // 自定义时间
                    $where .= " AND hour=0 AND ctime<=$end_time";
                    if($today_start > $end_time ){
                        $flag = false;
                    }
                    break;
            }

            if($flag) {
                $hour = (int)date('H');
                $where = "({$where}) OR (ctime>{$today_start} AND hour=$hour)";
            }

            $sql = "SELECT register_sum,register_add,resume_sum_1,resume_sum_11
                    ,resume_add_1,resume_add_11,FROM_UNIXTIME(ctime,'{$time_str}') AS tag
                    FROM ".$this->_table."
                    WHERE {$where}";
            return $this->MFindAllBySql($sql);
        }else{
            return array();
        }
    }

    /**
     * 获取头部展示数据
     */
    public function getReportDataTips(){
        $start = mktime(0,0,0);
        $hour = (int) date('H');
        $sql = "SELECT register_sum,register_add,resume_sum_1
                ,resume_sum_11,resume_add_1,resume_add_11
                FROM {$this->_table}
                WHERE ctime={$start} OR (ctime>{$start} AND hour={$hour})";

        $list = $this->MFindAllBySql($sql);
        $data = array();
        if(!empty($list)) {
            $count = count($list) - 1;
                $data['register_add'] = $list[0]['register_add'];
                $data['resume_add_1'] = $list[0]['resume_add_1'];
                $data['resume_add_11'] = $list[0]['resume_add_11'];
                if ($count == 0) {
                    $data['register_sum'] = $list[0]['register_sum'];
                    $data['resume_sum_1'] = $list[0]['resume_sum_1'];
                    $data['resume_sum_11'] = $list[0]['resume_sum_11'];
                } else {
                    $data['register_sum'] = $list[$count]['register_sum'];
                    $data['resume_sum_1'] = $list[$count]['resume_sum_1'];
                    $data['resume_sum_11'] = $list[$count]['resume_sum_11'];
                }

        }
        return $data;
    }

    public function getReportDataHour($options){
        $start = mktime(0,0,0);
        $start_time = (!empty($options['start_time'])) ? $options['start_time'] : $start;
        $end_time = $start_time + 86400;

        $sql = "SELECT register_sum,register_add,resume_sum_1,resume_sum_11,resume_add_1,resume_add_11,IF(`hour`=0,24,`hour`) AS `hour`
                FROM {$this->_table}
                WHERE ctime>$start_time AND ctime<=$end_time";

        return $this->MFindAllBySql($sql);
    }
}
