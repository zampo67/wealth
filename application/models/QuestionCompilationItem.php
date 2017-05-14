<?php

class QuestionCompilationItemModel extends MBaseModel{
    protected $_table = '{{question_compilation_item}}';
    protected $_table_question = '{{question}}';
    protected $_table_question_listen = '{{question_listen}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function rules(){
        return array(

        );
    }

    public function getQuestionListByCompilationId($compilation_id, $options=array()){
        $list = array();
        if(!empty($compilation_id) && is_numeric($compilation_id)){
            $field = $join = $where = '';
            $param = array(':compilation_id' => $compilation_id);

//            if(isset($options['compilation_subscription']) && $options['compilation_subscription']==1){
//                $field = ",1 AS listen_id";
//            }elseif(!empty($options['user_id']) && is_numeric($options['user_id'])){
//                $field = ",ql.id AS listen_id";
//                $join = "LEFT JOIN {$this->_table_question_listen} AS ql
//                     ON ql.question_id=q.id AND ql.user_id=:user_id";
//                $param[':user_id'] = $options['user_id'];
//            }

            $list = $this->MFindAllBySql(
                "SELECT q.id,q.title,q.read_sum+q.read_sum_add as read_sum,q.is_free,q.is_recently_update{$field}
                 FROM {$this->_table} AS qci
                 LEFT JOIN {$this->_table_question} AS q ON q.id=qci.question_id {$join}
                 WHERE qci.compilation_id=:compilation_id AND qci.status='1' AND qci.is_del='0'
                      AND q.status='1' AND q.is_del='0' {$where}
                 ORDER BY qci.sort DESC"
            , $param);
            if(empty($list)){
                $list = array();
            }
//            if(!empty($list)){
//                foreach ($list as &$item){
//                    $item['voice_time'] = !empty($item['voice_url']) ? QuestionModel::model()->getMinute($item['voice_time']) : '';
//
//                    if(!empty($item['listen_id'])){
//                        $item['is_listen'] = 1;
//                    }else{
//                        $item['is_listen'] = 0;
//                        $item['listen_id'] = 0;
//                    }
//
//                    unset($item['voice_url']);
//                    unset($item['listen_id']);
//                }
//                unset($item);
//            }else{
//                $list = array();
//            }
        }
        return $list;
    }

}