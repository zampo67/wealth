<?php
class LogRwxAnalyticsQuestionCompSumModel extends MBaseModel{
    protected $_table = '{{log_rwx_analytics_question_comp_sum}}';
    protected $_table_log_rwx_record_question_share = '{{log_rwx_record_question_share}}';
    protected $_table_log_rwx_record_question_view = '{{log_rwx_record_question_view}}';
    protected $_table_question_compilation = '{{question_compilation}}';
    protected $_table_question_compilation_subscription = '{{question_compilation_subscription}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function MFind($options = array()){
        return parent::MFind($options, 0, 0);
    }

    public function MFindAll($options = array(), $all_num=0){
        return parent::MFindAll($options, $all_num, 0, 0);
    }

    public function MSave($data, $ctime = 1){
        return parent::MSave($data, $ctime, 0);
    }

    public function saveCompSum($time, $time_type){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);

        $check_res = $this->MFind(array(
            'field' => 'id',
            'where' => $limit_date,
        ));
        if(!empty($check_res)){
            return false;
        }

        $sum_data = $this->MFindAllBySql(
            "SELECT q.id AS compilation_id,q.price
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_log_rwx_record_question_view}
                    WHERE link_id=q.id AND link_type_id=2 AND ctime<{$limit_time['end_time']} AND user_id>0
                ) AS view_user_total
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_log_rwx_record_question_view}
                    WHERE link_id=q.id AND link_type_id=2 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND user_id>0
                ) AS view_user_sum
                ,(
                    SELECT CONCAT( SUM(total_fee),'-',COUNT(DISTINCT user_id),'-',COUNT(1) )
                    FROM {$this->_table_question_compilation_subscription}
                    WHERE compilation_id=q.id AND total_fee>0 AND ctime<{$limit_time['end_time']}
                ) AS pay_user_total
                ,(
                    SELECT CONCAT( SUM(total_fee),'-',COUNT(DISTINCT user_id),'-',COUNT(1) )
                    FROM {$this->_table_question_compilation_subscription}
                    WHERE compilation_id=q.id AND total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
                ) AS pay_user_sum
            FROM {$this->_table_question_compilation} AS q 
            WHERE q.status='1' AND q.is_del='0'"
        );
        if(!empty($sum_data)){
            $limit_date['ctime'] = time();
            $save_data = array();
            foreach ($sum_data as &$item){
                if(!empty($item['pay_user_total'])){
                    $exp_arr = explode('-', $item['pay_user_total']);
                    $item['pay_total'] = isset($exp_arr[0]) ? $exp_arr[0] : 0;
                    $item['pay_user_total'] = isset($exp_arr[1]) ? $exp_arr[1] : 0;
                    $item['pay_time_total'] = isset($exp_arr[2]) ? $exp_arr[2] : 0;
                }else{
                    $item['pay_total'] = 0;
                    $item['pay_user_total'] = 0;
                    $item['pay_time_total'] = 0;
                }

                if(!empty($item['pay_user_sum'])){
                    $exp_arr = explode('-', $item['pay_user_sum']);
                    $item['pay_sum'] = isset($exp_arr[0]) ? $exp_arr[0] : 0;
                    $item['pay_user_sum'] = isset($exp_arr[1]) ? $exp_arr[1] : 0;
                    $item['pay_time_sum'] = isset($exp_arr[2]) ? $exp_arr[2] : 0;
                }else{
                    $item['pay_sum'] = 0;
                    $item['pay_user_sum'] = 0;
                    $item['pay_time_sum'] = 0;
                }

                $save_data[] = array_merge($item, $limit_date);
            }
            return $this->MInsertMulti($save_data);
        }else{
            return false;
        }
    }

    public function getReportData($options){
        $start_time = (!empty($options['start_time'])) ? $options['start_time'] : 0;
        $end_time = (!empty($options['end_time'])) ? $options['end_time'] : 0;
        $handle_type = (!empty($options['handle_type'])) ? $options['handle_type'] : '';

        if (!empty($handle_type) && !empty($start_time) && !empty($end_time)) {
            $where = "ctime>=$start_time AND ctime<$end_time AND type_id=31";

            $sql = '';
            switch ($handle_type) {
                case 'pay_data':
                    $sql = "SELECT concat(year,'-',month,'-',day) AS tag,hour
                    ,SUM(pay_sum) AS comp_pay_sum,SUM(pay_user_sum) AS comp_pay_user_sum
                    FROM {$this->_table}
                    WHERE {$where}
                    GROUP BY tag";
                    break;
            }
            return $this->MFindAllBySql($sql);
        } else {
            return array();
        }
    }

}