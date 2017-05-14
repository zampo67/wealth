<?php
class LogRwxAnalyticsQuestionQuesSumModel extends MBaseModel{
    protected $_table = '{{log_rwx_analytics_question_ques_sum}}';
    protected $_table_log_rwx_record_question_card_read = '{{log_rwx_record_question_card_read}}';
    protected $_table_log_rwx_record_question_listen = '{{log_rwx_record_question_listen}}';
    protected $_table_log_rwx_record_question_share = '{{log_rwx_record_question_share}}';
    protected $_table_log_rwx_record_question_view = '{{log_rwx_record_question_view}}';
    protected $_table_question = '{{question}}';
    protected $_table_question_compilation = '{{question_compilation}}';
    protected $_table_question_compilation_item = '{{question_compilation_item}}';
    protected $_table_question_compilation_subscription = '{{question_compilation_subscription}}';
    protected $_table_question_listen = '{{question_listen}}';
    protected $_table_question_like = '{{question_like}}';

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

    public function saveQuesSum($time, $time_type){
        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);

        $check_res = $this->MFind(array(
            'field' => 'id',
            'where' => $limit_date,
        ));
        if(!empty($check_res)){
            return false;
        }

//        $sum_data = $this->MFindAllBySql(
//            "SELECT q.id AS question_id,q.price,q.is_recently_update
//                ,(
//                    SELECT COUNT(DISTINCT user_id)
//                    FROM {$this->_table_log_rwx_record_question_view}
//                    WHERE link_id=q.id AND link_type_id=1 AND ctime<{$limit_time['end_time']} AND user_id>0
//                ) AS view_user_total
//                ,(
//                    SELECT COUNT(DISTINCT user_id)
//                    FROM {$this->_table_log_rwx_record_question_view}
//                    WHERE link_id=q.id AND link_type_id=1 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND user_id>0
//                ) AS view_user_sum
//                ,(
//                    SELECT COUNT(DISTINCT user_id)
//                    FROM {$this->_table_log_rwx_record_question_listen}
//                    WHERE question_id=q.id AND ctime<{$limit_time['end_time']}
//                ) AS listen_user_total
//                ,(
//                    SELECT CONCAT( COUNT(DISTINCT user_id),'-',SUM(IF(is_reg_today_first='1', 1, 0)) )
//                    FROM {$this->_table_log_rwx_record_question_listen}
//                    WHERE question_id=q.id AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
//                ) AS listen_user_sum
//                ,(
//                    SELECT COUNT(DISTINCT user_id)
//                    FROM {$this->_table_log_rwx_record_question_card_read}
//                    WHERE question_id=q.id AND ctime<{$limit_time['end_time']}
//                ) AS read_user_total
//                ,(
//                    SELECT CONCAT( COUNT(DISTINCT user_id),'-',SUM(IF(is_reg_today_first='1', 1, 0)),'-',SUM(card_num) )
//                    FROM {$this->_table_log_rwx_record_question_card_read}
//                    WHERE question_id=q.id AND mtime>={$limit_time['start_time']} AND mtime<{$limit_time['end_time']}
//                ) AS read_user_sum
//                ,(
//                    SELECT COUNT(DISTINCT un.user_id)
//                    FROM (
//                     (
//                      SELECT user_id,question_id
//                      FROM {$this->_table_log_rwx_record_question_listen}
//                      WHERE ctime<{$limit_time['end_time']}
//                     ) UNION ALL (
//                      SELECT user_id,question_id
//                      FROM {$this->_table_log_rwx_record_question_card_read}
//                      WHERE ctime<{$limit_time['end_time']}
//                     )
//                    ) AS un
//                    WHERE un.question_id=q.id
//                ) AS listen_or_read_user_total
//                ,(
//                    SELECT CONCAT( COUNT(DISTINCT un.user_id),'-',SUM(IF(un.is_reg_today_first='1', 1, 0)) )
//                    FROM (
//                     (SELECT user_id,question_id,is_reg_today_first
//                      FROM {$this->_table_log_rwx_record_question_listen}
//                      WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
//                     ) UNION ALL (
//                      SELECT user_id,question_id,is_reg_today_first
//                      FROM {$this->_table_log_rwx_record_question_card_read}
//                      WHERE mtime>={$limit_time['start_time']} AND mtime<{$limit_time['end_time']}
//                      )
//                    ) AS un
//                    WHERE un.question_id=q.id
//                ) AS listen_or_read_user_sum
//                ,(
//                    SELECT CONCAT( SUM(total_fee),'-',COUNT(DISTINCT user_id),'-',COUNT(1) )
//                    FROM {$this->_table_question_listen}
//                    WHERE question_id=q.id AND total_fee>0 AND ctime<{$limit_time['end_time']}
//                ) AS pay_user_total
//                ,(
//                    SELECT CONCAT( SUM(total_fee),'-',COUNT(DISTINCT user_id),'-',COUNT(1) )
//                    FROM {$this->_table_question_listen}
//                    WHERE question_id=q.id AND total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
//                ) AS pay_user_sum
//                ,(
//                    SELECT COUNT(DISTINCT user_id)
//                    FROM {$this->_table_question_like}
//                    WHERE question_id=q.id AND ctime<{$limit_time['end_time']}
//                ) AS like_user_total
//                ,(
//                    SELECT COUNT(DISTINCT user_id)
//                    FROM {$this->_table_question_like}
//                    WHERE question_id=q.id AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
//                ) AS like_user_sum
//            FROM {$this->_table_question} AS q
//            WHERE q.status='1' AND q.is_del='0'"
//        );
        $sum_data = $this->MFindAllBySql(
            "SELECT q.id AS question_id,q.price,q.is_recently_update
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_log_rwx_record_question_view}
                    WHERE link_id=q.id AND link_type_id=1 AND ctime<{$limit_time['end_time']} AND user_id>0
                ) AS view_user_total
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_log_rwx_record_question_view}
                    WHERE link_id=q.id AND link_type_id=1 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} AND user_id>0
                ) AS view_user_sum
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_log_rwx_record_question_listen}
                    WHERE question_id=q.id AND ctime<{$limit_time['end_time']}
                ) AS listen_user_total
                ,(
                    SELECT CONCAT( COUNT(DISTINCT user_id),'-',SUM(IF(is_reg_today_first='1', 1, 0)) )
                    FROM {$this->_table_log_rwx_record_question_listen}
                    WHERE question_id=q.id AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
                ) AS listen_user_sum 
                ,(
                    SELECT CONCAT( SUM(total_fee),'-',COUNT(DISTINCT user_id),'-',COUNT(1) )
                    FROM {$this->_table_question_listen}
                    WHERE question_id=q.id AND total_fee>0 AND ctime<{$limit_time['end_time']}
                ) AS pay_user_total
                ,(
                    SELECT CONCAT( SUM(total_fee),'-',COUNT(DISTINCT user_id),'-',COUNT(1) )
                    FROM {$this->_table_question_listen}
                    WHERE question_id=q.id AND total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
                ) AS pay_user_sum
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_question_like}
                    WHERE question_id=q.id AND ctime<{$limit_time['end_time']}
                ) AS like_user_total
                ,(
                    SELECT COUNT(DISTINCT user_id)
                    FROM {$this->_table_question_like}
                    WHERE question_id=q.id AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
                ) AS like_user_sum
            FROM {$this->_table_question} AS q 
            WHERE q.status='1' AND q.is_del='0'"
        );
        if(!empty($sum_data)){
            $limit_date['ctime'] = time();
            $save_data = array();
            foreach ($sum_data as &$item){
                if(!empty($item['listen_user_sum'])){
                    $exp_arr = explode('-', $item['listen_user_sum']);
                    $item['listen_user_sum'] = isset($exp_arr[0]) ? $exp_arr[0] : 0;
                    $item['listen_user_reg_new'] = isset($exp_arr[1]) ? $exp_arr[1] : 0;
                }else{
                    $item['listen_user_sum'] = 0;
                    $item['listen_user_reg_new'] = 0;
                }

//                if(!empty($item['read_user_sum'])){
//                    $exp_arr = explode('-', $item['read_user_sum']);
//                    $item['read_user_sum'] = isset($exp_arr[0]) ? $exp_arr[0] : 0;
//                    $item['read_user_reg_new'] = isset($exp_arr[1]) ? $exp_arr[1] : 0;
//                    $item['read_user_max_sum'] = isset($exp_arr[2]) ? $exp_arr[2] : 0;
//                }else{
//                    $item['read_user_sum'] = 0;
//                    $item['read_user_reg_new'] = 0;
//                    $item['read_user_max_sum'] = 0;
//                }
//
//                if(!empty($item['listen_or_read_user_sum'])){
//                    $exp_arr = explode('-', $item['listen_or_read_user_sum']);
//                    $item['listen_or_read_user_sum'] = isset($exp_arr[0]) ? $exp_arr[0] : 0;
//                    $item['listen_or_read_user_reg_new'] = isset($exp_arr[1]) ? $exp_arr[1] : 0;
//                }else{
//                    $item['listen_or_read_user_sum'] = 0;
//                    $item['listen_or_read_user_reg_new'] = 0;
//                }

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

        if(!empty($handle_type) && !empty($start_time) && !empty($end_time)){
            $where = "l.ctime>=$start_time AND l.ctime<$end_time AND l.type_id=31";

            $sql = '';
            switch($handle_type){
                case 'pay_data':
                    $sql = "SELECT concat(year,'-',month,'-',day) AS tag,hour
                            ,SUM(pay_sum) AS ques_pay_sum,SUM(pay_user_sum) AS ques_pay_user_sum
                            FROM {$this->_table} AS l
                            WHERE {$where}
                            GROUP BY tag";
                    break;
                case 'user_listen':
                    $sql = "SELECT l.read_user_sum,l.read_user_max_sum,l.listen_or_read_user_sum,l.price
                            ,q.title,q.voice_time,q.card_sum,c.title AS comp_title
                            FROM {$this->_table} AS l
                            LEFT JOIN {$this->_table_question} AS q ON l.question_id=q.id
                            LEFT JOIN {$this->_table_question_compilation_item} as i ON i.question_id=l.question_id AND i.is_del='0'
                            LEFT JOIN {$this->_table_question_compilation} AS c ON i.compilation_id=c.id
                            WHERE {$where}";
                    break;
                case 'user_pay':
                    $sql = "SELECT l.pay_sum,l.price
                            ,q.title,q.voice_time,q.card_sum,c.title AS comp_title
                            FROM {$this->_table} AS l
                            LEFT JOIN {$this->_table_question} AS q ON l.question_id=q.id
                            LEFT JOIN {$this->_table_question_compilation_item} as i ON i.question_id=l.question_id AND i.is_del='0'
                            LEFT JOIN {$this->_table_question_compilation} AS c ON i.compilation_id=c.id
                            WHERE {$where}";
                    break;
            }

            return $this->MFindAllBySql($sql);
        }else{
            return array();
        }
    }

//    public function saveQuesSumBak($time, $time_type){
//        $limit_date = TimeFormat::getInstance()->getDbLogLimitDate($time, $time_type);
//        $limit_time = TimeFormat::getInstance()->getDbLogLimitTime($limit_date, $time_type);
//
//        $check_res = $this->MFind(array(
//            'field' => 'id',
//            'where' => $limit_date,
//        ));
//
//        if(!empty($check_res)){
//            return false;
//        }
//
//        $sum_data = $this->MFindAllBySql(
//            "SELECT q.id AS question_id,q.price,q.is_recently_update
//                ,IFNULL(quvt.view_user_total,0) AS view_user_total
//                ,IFNULL(quvs.view_user_sum,0) AS view_user_sum
//                ,IFNULL(qlist.listen_user_total,0) AS listen_user_total
//                ,IFNULL(qliss.listen_user_sum,0) AS listen_user_sum
//                ,IFNULL(qliss.listen_user_reg_new,0) AS listen_user_reg_new
//                ,IFNULL(qreat.read_user_total,0) AS read_user_total
//                ,IFNULL(qreas.read_user_sum,0) AS read_user_sum
//                ,IFNULL(qreas.read_user_reg_new,0) AS read_user_reg_new
//                ,IFNULL(qlart.listen_or_read_user_total,0) AS listen_or_read_user_total
//                ,IFNULL(qlars.listen_or_read_user_sum,0) AS listen_or_read_user_sum
//                ,IFNULL(qlars.listen_or_read_user_reg_new,0) AS listen_or_read_user_reg_new
//                ,IFNULL(qpayt.pay_user_total,0) AS pay_user_total
//                ,IFNULL(qpayt.pay_total,0) AS pay_total
//                ,IFNULL(qpays.pay_user_sum,0) AS pay_user_sum
//                ,IFNULL(qpays.pay_sum,0) AS pay_sum
//            FROM {$this->_table_question} AS q
//
//            LEFT JOIN (
//                SELECT link_id,COUNT(DISTINCT user_id) AS view_user_total
//                FROM {$this->_table_log_rwx_record_question_view}
//                WHERE link_id>0 AND link_type_id=1 AND ctime<{$limit_time['end_time']} GROUP BY link_id
//            ) AS quvt ON quvt.link_id=q.id
//            LEFT JOIN (
//                SELECT link_id,COUNT(DISTINCT user_id) AS view_user_sum
//                FROM {$this->_table_log_rwx_record_question_view}
//                WHERE link_id>0 AND link_type_id=1 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} GROUP BY link_id
//            ) AS quvs ON quvs.link_id=q.id
//
//            LEFT JOIN (
//                SELECT question_id,COUNT(DISTINCT user_id) AS listen_user_total
//                FROM {$this->_table_log_rwx_record_question_listen}
//                WHERE ctime<{$limit_time['end_time']} GROUP BY question_id
//            ) AS qlist ON qlist.question_id=q.id
//            LEFT JOIN (
//                SELECT question_id,COUNT(DISTINCT user_id) AS listen_user_sum
//                       ,SUM(IF(is_reg_today_first='1', 1, 0)) AS listen_user_reg_new
//                FROM {$this->_table_log_rwx_record_question_listen}
//                WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} GROUP BY question_id
//            ) AS qliss ON qliss.question_id=q.id
//
//            LEFT JOIN (
//                SELECT question_id,COUNT(DISTINCT user_id) AS read_user_total
//                FROM {$this->_table_log_rwx_record_question_card_read}
//                WHERE ctime<{$limit_time['end_time']} GROUP BY question_id
//            ) AS qreat ON qreat.question_id=q.id
//            LEFT JOIN (
//                SELECT question_id,COUNT(DISTINCT user_id) AS read_user_sum
//                       ,SUM(IF(is_reg_today_first='1', 1, 0)) AS read_user_reg_new
//                FROM {$this->_table_log_rwx_record_question_card_read}
//                WHERE mtime>={$limit_time['start_time']} AND mtime<{$limit_time['end_time']} GROUP BY question_id
//            ) AS qreas ON qreas.question_id=q.id
//
//            LEFT JOIN (
//                SELECT un.question_id,COUNT(DISTINCT un.user_id) AS listen_or_read_user_total
//                FROM (
//                  (SELECT user_id,question_id
//                   FROM {$this->_table_log_rwx_record_question_listen}
//                   WHERE ctime<{$limit_time['end_time']}
//                  ) UNION ALL (
//                   SELECT user_id,question_id
//                   FROM {$this->_table_log_rwx_record_question_card_read}
//                   WHERE ctime<{$limit_time['end_time']}
//                  )
//                ) AS un GROUP BY question_id
//            ) AS qlart ON qlart.question_id=q.id
//            LEFT JOIN (
//                SELECT un.question_id,COUNT(DISTINCT un.user_id) AS listen_or_read_user_sum
//                       ,SUM(IF(un.is_reg_today_first='1', 1, 0)) AS listen_or_read_user_reg_new
//                FROM (
//                  (SELECT user_id,question_id,is_reg_today_first
//                   FROM {$this->_table_log_rwx_record_question_listen}
//                   WHERE ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']}
//                  ) UNION ALL (
//                   SELECT user_id,question_id,is_reg_today_first
//                   FROM {$this->_table_log_rwx_record_question_card_read}
//                   WHERE mtime>={$limit_time['start_time']} AND mtime<{$limit_time['end_time']}
//                  )
//                ) AS un GROUP BY question_id
//            ) AS qlars ON qlars.question_id=q.id
//
//            LEFT JOIN (
//                SELECT question_id,COUNT(DISTINCT user_id) AS pay_user_total
//                       ,SUM(total_fee) AS pay_total
//                FROM {$this->_table_question_listen}
//                WHERE total_fee>0 AND ctime<{$limit_time['end_time']} GROUP BY question_id
//            ) AS qpayt ON qpayt.question_id=q.id
//            LEFT JOIN (
//                SELECT question_id,COUNT(DISTINCT user_id) AS pay_user_sum
//                       ,SUM(total_fee) AS pay_sum
//                FROM {$this->_table_question_listen}
//                WHERE total_fee>0 AND ctime>={$limit_time['start_time']} AND ctime<{$limit_time['end_time']} GROUP BY question_id
//            ) AS qpays ON qpays.question_id=q.id
//
//             WHERE q.status='1' AND q.is_del='0'"
//        );
//        return $sum_data;
//    }

}