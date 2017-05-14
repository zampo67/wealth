<?php
class LogRwxAnalyticsQuestionSumModel extends MBaseModel{
    protected $_table = '{{log_rwx_analytics_question_sum}}';
    protected $_table_log_rwx_record_question_card_read = '{{log_rwx_record_question_card_read}}';
    protected $_table_log_rwx_record_question_listen = '{{log_rwx_record_question_listen}}';
    protected $_table_log_rwx_record_question_read = '{{log_rwx_record_question_read}}';
    protected $_table_log_rwx_record_question_share = '{{log_rwx_record_question_share}}';
    protected $_table_log_rwx_record_question_view = '{{log_rwx_record_question_view}}';
    protected $_table_question_compilation_subscription = '{{question_compilation_subscription}}';
    protected $_table_question_listen = '{{question_listen}}';

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

    public function saveUserLoseDaily($time, $time_interval){
        return $this->saveUserLose($time, 'day', $time_interval);
    }

    public function saveUserRetentionDaily($time, $time_interval){
        return $this->saveUserRetention($time, 'day', $time_interval);
    }

    /**
     * 根据时间周期计算 用户流失
     * @param string|int $time 时间
     * @param string $time_type 时间周期类型
     * @param int $time_interval 时间间隔
     * @return bool|int
     */
    public function saveUserLose($time, $time_type, $time_interval){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        if(!is_array($time_interval)){
            $time_interval = array($time_interval);
        }
        $save_data = array();
        foreach ($time_interval as $ti){
            $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type, $ti);

            $active_time['end_time'] = $limit_time['start_time'];
            $active_time['start_time'] = $active_time['end_time'] - TimeFormat::getInstance()->getTimeByInterval($time_type);
            $user_active_list = $this->MFindAllBySql(
                "SELECT DISTINCT user_id
                 FROM {$this->_table_log_rwx_record_question_view}
                 WHERE ctime>={$active_time['start_time']} AND ctime<{$active_time['end_time']} AND user_id>0"
            );
            if(!empty($user_active_list)){
                $user_active_list = array_column($user_active_list, 'user_id');
                list($where, $param) = $this->parseWhere(array('user_id'=>$user_active_list));

                $user_limit_list = $this->MFindAllBySql(
                    "SELECT DISTINCT user_id
                     FROM {$this->_table_log_rwx_record_question_view}
                     WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND {$where[0]} AND user_id>0"
                , $param);
                if(!empty($user_limit_list)){
                    $user_lose_list = array_diff($user_active_list, array_column($user_limit_list, 'user_id'));
                }else{
                    $user_lose_list = $user_active_list;
                }
                $save_data['user_lose_'.$ti] = !empty($user_lose_list) ? count($user_lose_list) : 0;
            }
        }

        if(!empty($save_data)){
            $check_res = $this->MFind(array(
                'field' => 'id',
                'where' => $limit_date,
            ));
            if(!empty($check_res)){
                $save_data['id'] = $check_res['id'];
            }else{
                $save_data = array_merge($save_data, $limit_date);
            }
            return $this->MSave($save_data);
        }else{
            return false;
        }
    }

    /**
     * 根据时间周期计算 用户留存
     * @param string|int $time 时间
     * @param string $time_type 时间周期类型
     * @param int $time_interval 时间间隔
     * @return bool|int
     */
    public function saveUserRetention($time, $time_type, $time_interval){
        if(!is_array($time_interval)){
            $time_interval = array($time_interval);
        }
        $active_time = TimeFormat::getInstance()->getDbLogLimitTime(TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type), $time_type);
        foreach ($time_interval as $ti){
            $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time - TimeFormat::getInstance()->getTimeByInterval($time_type, $ti), $time_type);
            $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
            $user_list = $this->MFindAllBySql(
                "SELECT DISTINCT uv1.user_id
                 FROM {$this->_table_log_rwx_record_question_view} AS uv1
                 LEFT JOIN {$this->_table_log_rwx_record_question_view} AS uv2 ON uv1.user_id=uv2.user_id
                 WHERE uv1.ctime>={$limit_time['start_time']} AND uv1.ctime<{$limit_time['end_time']} AND uv1.is_first='1' AND uv1.user_id>0
                      AND uv2.ctime>={$active_time['start_time']} AND uv2.ctime<{$active_time['end_time']} AND uv2.user_id>0"
            );

            if(!empty($user_list)){
                $check_res = $this->MFind(array(
                    'field' => 'id',
                    'where' => $limit_date,
                ));
                if(!empty($check_res)){
                    $save_data['id'] = $check_res['id'];
                    $save_data['user_retention_'.$ti] = count($user_list);
                    $this->MSave($save_data);
                }
            }
        }
        return true;
    }

    /**
     * 根据时间周期计算 所有访问数/周期内访问数/新增访问数
     * @param string|int $time 时间
     * @param string $time_type 时间周期类型
     * @return bool|int
     */
    public function saveViewUser($time, $time_type){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
        $save_data = array();

        $check_res = $this->MFind(array(
            'field' => 'id',
            'where' => $limit_date,
        ));

        if(!empty($check_res)){
            $save_data['id'] = $check_res['id'];
        }else{
            $save_data = $limit_date;
        }

        $wx_user_total = $this->MFindBySql(
            "SELECT COUNT(DISTINCT wx_user_id) AS view_wx_user_total
             FROM {$this->_table_log_rwx_record_question_view}
             WHERE ctime<{$limit_time['end_time']}"
        );
        $wx_user_sum = $this->MFindBySql(
            "SELECT COUNT(DISTINCT wx_user_id) AS view_wx_user_sum
                    ,SUM(IF(user_id=0 AND is_first='1', 1, 0)) AS view_wx_user_new
             FROM {$this->_table_log_rwx_record_question_view}
             WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}"
        );
        $user_total = $this->MFindBySql(
            "SELECT COUNT(DISTINCT user_id) AS view_user_total
             FROM {$this->_table_log_rwx_record_question_view}
             WHERE ctime<{$limit_time['end_time']} AND user_id>0"
        );
        $user_sum = $this->MFindBySql(
            "SELECT COUNT(DISTINCT user_id) AS view_user_sum
                    ,SUM(IF(is_first='1', 1, 0)) AS view_user_new
             FROM {$this->_table_log_rwx_record_question_view}
             WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND user_id>0"
        );

        $save_data['view_wx_user_total'] = !empty($wx_user_total['view_wx_user_total']) ? $wx_user_total['view_wx_user_total'] : 0;
        $save_data['view_wx_user_sum'] = !empty($wx_user_sum['view_wx_user_sum']) ? $wx_user_sum['view_wx_user_sum'] : 0;
        $save_data['view_wx_user_new'] = !empty($wx_user_sum['view_wx_user_new']) ? $wx_user_sum['view_wx_user_new'] : 0;
        $save_data['view_user_total'] = !empty($user_total['view_user_total']) ? $user_total['view_user_total'] : 0;
        $save_data['view_user_sum'] = !empty($user_sum['view_user_sum']) ? $user_sum['view_user_sum'] : 0;
        $save_data['view_user_new'] = !empty($user_sum['view_user_new']) ? $user_sum['view_user_new'] : 0;
        return $this->MSave($save_data);
    }

    /**
     * 根据时间周期计算 所有收听数/周期内收听数/新增注册收听数
     * @param string|int $time 时间
     * @param string $time_type 时间周期类型
     * @return bool|int
     */
    public function saveListenUser($time, $time_type){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
        $save_data = array();

        $check_res = $this->MFind(array(
            'field' => 'id',
            'where' => $limit_date,
        ));

        if(!empty($check_res)){
            $save_data['id'] = $check_res['id'];
        }else{
            $save_data = $limit_date;
        }

        $user_total = $this->MFindBySql(
            "SELECT COUNT(DISTINCT user_id) AS listen_user_total
             FROM {$this->_table_log_rwx_record_question_listen}
             WHERE ctime<{$limit_time['end_time']} AND user_id>0"
        );
        $user_sum = $this->MFindBySql(
            "SELECT COUNT(DISTINCT user_id) AS listen_user_sum
                    ,SUM(IF(is_reg_today_first='1', 1, 0)) AS listen_user_reg_new
             FROM {$this->_table_log_rwx_record_question_listen}
             WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND user_id>0"
        );

        $save_data['listen_user_total'] = !empty($user_total['listen_user_total']) ? $user_total['listen_user_total'] : 0;
        $save_data['listen_user_sum'] = !empty($user_sum['listen_user_sum']) ? $user_sum['listen_user_sum'] : 0;
        $save_data['listen_user_reg_new'] = !empty($user_sum['listen_user_reg_new']) ? $user_sum['listen_user_reg_new'] : 0;
        return $this->MSave($save_data);
    }

    /**
     * 根据时间周期计算 所有阅读数/周期内阅读数/新增注册阅读数
     * @param string|int $time 时间
     * @param string $time_type 时间周期类型
     * @return bool|int
     */
    public function saveReadUser($time, $time_type){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
        $save_data = array();

        $check_res = $this->MFind(array(
            'field' => 'id',
            'where' => $limit_date,
        ));

        if(!empty($check_res)){
            $save_data['id'] = $check_res['id'];
        }else{
            $save_data = $limit_date;
        }

        $user_total = $this->MFindBySql(
            "SELECT COUNT(DISTINCT user_id) AS read_user_total
             FROM {$this->_table_log_rwx_record_question_read}
             WHERE ctime<{$limit_time['end_time']} AND user_id>0"
        );
        $user_sum = $this->MFindBySql(
            "SELECT COUNT(DISTINCT user_id) AS read_user_sum
                    ,SUM(IF(is_reg_today_first='1', 1, 0)) AS read_user_reg_new
             FROM {$this->_table_log_rwx_record_question_read}
             WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND user_id>0"
        );

        $save_data['read_user_total'] = !empty($user_total['read_user_total']) ? $user_total['read_user_total'] : 0;
        $save_data['read_user_sum'] = !empty($user_sum['read_user_sum']) ? $user_sum['read_user_sum'] : 0;
        $save_data['read_user_reg_new'] = !empty($user_sum['read_user_reg_new']) ? $user_sum['read_user_reg_new'] : 0;
        return $this->MSave($save_data);
    }

//    /**
//     * 根据时间周期计算 所有收听或阅读数/周期内收听或阅读数/新增注册收听或阅读数
//     * @param string|int $time 时间
//     * @param string $time_type 时间周期类型
//     * @return bool|int
//     */
//    public function saveListenOrReadUser($time, $time_type){
//        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
//        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
//        $save_data = array();
//
//        $check_res = $this->MFind(array(
//            'field' => 'id',
//            'where' => $limit_date,
//        ));
//
//        if(!empty($check_res)){
//            $save_data['id'] = $check_res['id'];
//        }else{
//            $save_data = $limit_date;
//        }
//
//        $user_total = $this->MFindBySql(
//            "SELECT COUNT(DISTINCT un.user_id) AS listen_or_read_user_total
//             FROM (
//              (SELECT user_id
//               FROM {$this->_table_log_rwx_record_question_listen}
//               WHERE ctime<{$limit_time['end_time']}
//              ) UNION ALL (
//               SELECT user_id
//               FROM {$this->_table_log_rwx_record_question_card_read}
//               WHERE ctime<{$limit_time['end_time']})
//              ) AS un"
//        );
//        $user_sum = $this->MFindBySql(
//            "SELECT COUNT(DISTINCT un.user_id) AS listen_or_read_user_sum
//                    ,SUM(IF(un.is_reg_today_first='1', 1, 0)) AS listen_or_read_user_reg_new
//             FROM (
//              (SELECT user_id,is_reg_today_first
//               FROM {$this->_table_log_rwx_record_question_listen}
//               WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
//              ) UNION ALL (
//               SELECT user_id,is_reg_today_first
//               FROM {$this->_table_log_rwx_record_question_card_read}
//               WHERE mtime>={$limit_time['start_time']} AND mtime<{$limit_time['end_time']})
//              ) AS un"
//        );
//
//        $save_data['listen_or_read_user_total'] = !empty($user_total['listen_or_read_user_total']) ? $user_total['listen_or_read_user_total'] : 0;
//        $save_data['listen_or_read_user_sum'] = !empty($user_sum['listen_or_read_user_sum']) ? $user_sum['listen_or_read_user_sum'] : 0;
//        $save_data['listen_or_read_user_reg_new'] = !empty($user_sum['listen_or_read_user_reg_new']) ? $user_sum['listen_or_read_user_reg_new'] : 0;
//        return $this->MSave($save_data);
//    }

    /**
     * 根据时间周期计算 所有收费数/周期内收费数/所有付费用户数/周期内付费用户数
     * @param string|int $time 时间
     * @param string $time_type 时间周期类型
     * @return bool|int
     */
    public function savePayUser($time, $time_type){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
        $save_data = array();

        $check_res = $this->MFind(array(
            'field' => 'id',
            'where' => $limit_date,
        ));

        if(!empty($check_res)){
            $save_data['id'] = $check_res['id'];
        }else{
            $save_data = $limit_date;
        }

        //计算总用户付费数
        $total = $this->MFindBySql(
            "SELECT COUNT(DISTINCT un.user_id) AS pay_user_total
             FROM (
              (SELECT user_id
               FROM {$this->_table_question_compilation_subscription}
               WHERE total_fee>0 AND ctime<{$limit_time['end_time']}
              ) UNION ALL (
               SELECT user_id
               FROM {$this->_table_question_listen}
               WHERE total_fee>0 AND ctime<{$limit_time['end_time']})
             ) AS un"
        );
        $sum = $this->MFindBySql(
            "SELECT COUNT(DISTINCT un.user_id) AS pay_user_sum
             FROM (
              (SELECT user_id
               FROM {$this->_table_question_compilation_subscription}
               WHERE total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
              ) UNION ALL (
               SELECT user_id
               FROM {$this->_table_question_listen}
               WHERE total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']})
             ) AS un"
        );

        //计算问题总付费数
        $total_ques = $this->MFindBySql(
            "SELECT SUM(total_fee) AS pay_total
                    ,COUNT(DISTINCT user_id) AS pay_user_total
                    ,COUNT(1) AS pay_time_total
             FROM {$this->_table_question_listen}
             WHERE total_fee>0 AND ctime<{$limit_time['end_time']}"
        );
        $sum_ques = $this->MFindBySql(
            "SELECT SUM(total_fee) AS pay_sum
                    ,COUNT(DISTINCT user_id) AS pay_user_sum
                    ,COUNT(1) AS pay_time_sum
             FROM {$this->_table_question_listen}
             WHERE total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}"
        );

        //计算合集总付费数
        $total_comp = $this->MFindBySql(
            "SELECT SUM(total_fee) AS pay_total
                    ,COUNT(DISTINCT user_id) AS pay_user_total
                    ,COUNT(1) AS pay_time_total
             FROM {$this->_table_question_compilation_subscription}
             WHERE total_fee>0 AND ctime<{$limit_time['end_time']}"
        );
        $sum_comp = $this->MFindBySql(
            "SELECT SUM(total_fee) AS pay_sum
                    ,COUNT(DISTINCT user_id) AS pay_user_sum
                    ,COUNT(1) AS pay_time_sum
             FROM {$this->_table_question_compilation_subscription}
             WHERE total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}"
        );

        $save_data['pay_user_total'] = !empty($total['pay_user_total']) ? $total['pay_user_total'] : 0;
        $save_data['pay_user_sum'] = !empty($sum['pay_user_sum']) ? $sum['pay_user_sum'] : 0;

        $save_data['ques_pay_total'] = !empty($total_ques['pay_total']) ? $total_ques['pay_total'] : 0;
        $save_data['ques_pay_sum'] = !empty($sum_ques['pay_sum']) ? $sum_ques['pay_sum'] : 0;
        $save_data['ques_pay_user_total'] = !empty($total_ques['pay_user_total']) ? $total_ques['pay_user_total'] : 0;
        $save_data['ques_pay_user_sum'] = !empty($sum_ques['pay_user_sum']) ? $sum_ques['pay_user_sum'] : 0;
        $save_data['ques_pay_time_total'] = !empty($total_ques['pay_time_total']) ? $total_ques['pay_time_total'] : 0;
        $save_data['ques_pay_time_sum'] = !empty($sum_ques['pay_time_sum']) ? $sum_ques['pay_time_sum'] : 0;

        $save_data['comp_pay_total'] = !empty($total_comp['pay_total']) ? $total_comp['pay_total'] : 0;
        $save_data['comp_pay_sum'] = !empty($sum_comp['pay_sum']) ? $sum_comp['pay_sum'] : 0;
        $save_data['comp_pay_user_total'] = !empty($total_comp['pay_user_total']) ? $total_comp['pay_user_total'] : 0;
        $save_data['comp_pay_user_sum'] = !empty($sum_comp['pay_user_sum']) ? $sum_comp['pay_user_sum'] : 0;
        $save_data['comp_pay_time_total'] = !empty($total_comp['pay_time_total']) ? $total_comp['pay_time_total'] : 0;
        $save_data['comp_pay_time_sum'] = !empty($sum_comp['pay_time_sum']) ? $sum_comp['pay_time_sum'] : 0;
        return $this->MSave($save_data);
    }

    public function getReportData($options){
        $start_time = (!empty($options['start_time'])) ? $options['start_time'] : 0;
        $end_time = (!empty($options['end_time'])) ? $options['end_time'] : 0;
        $handle_type = (!empty($options['handle_type'])) ? $options['handle_type'] : '';

        if(!empty($handle_type) && !empty($start_time) && !empty($end_time)){
            $where = "ctime>=$start_time AND ctime<$end_time AND type_id=31";

            $field = '';
            switch($handle_type){
                case 'user_growth':
                    $field = ',view_user_total,view_user_new,user_lose_7';
                    break;
                case 'user_active':
                    $field = ',view_user_total,view_user_sum,view_user_new';
                    break;
                case 'user_retention':
                    $field = ',view_user_sum,user_retention_1,user_retention_7,user_retention_30';
                    break;
                case 'user_listen':
                        $field = ',listen_or_read_user_total,listen_or_read_user_sum,view_user_sum,listen_or_read_user_reg_new,view_user_new';
                    break;
                case 'user_pay':
                    $field = ',pay_total,pay_user_total,pay_sum,pay_user_sum,view_user_sum';
                    break;
            }

            $sql = "SELECT concat(year,'-',month,'-',day) AS tag,hour
                    {$field}
                    FROM {$this->_table}
                    WHERE {$where}";
            return $this->MFindAllBySql($sql);
        }else{
            return array();
        }
    }

    public function getReportDataTips($handle_type){
        $start_time = mktime(0,0,0);
        $where = "ctime>=$start_time AND type_id=31";

        $field = '';
        switch($handle_type){
            case 'user_growth':
                $field = ',view_user_total,view_user_new,user_lose_7';
                break;
            case 'user_listen':
                $field = ',listen_or_read_user_total,listen_or_read_user_sum';
                break;
            case 'user_pay':
                $field = ',pay_total,pay_user_total';
                break;
        }

        $sql = "SELECT concat(year,'-',month,'-',day) AS tag,hour
                {$field}
                FROM {$this->_table}
                WHERE {$where}";

        return $this->MFindBySql($sql);
    }

}