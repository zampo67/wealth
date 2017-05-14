<?php

class QuestionCompilationModel extends MBaseModel{
    protected $_table = '{{question_compilation}}';
    protected $_table_question_compilation_subscription = '{{question_compilation_subscription}}';
    protected $_table_question_user = '{{question_user}}';

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

    public function getOrderInfo($id, $user_id){
        $item = $this->MGetInfoById($id, 'price,charge_type_id,start_time,end_time');
        return !empty($item) ? array(
            'order_id' => VariablesModel::model()->getAttrs('payLinkType', 'compilation', 'id').'_'.$id.'_'.date("ymdHis").$user_id,
            'order_price' => (float)$item['price'],
            'order_name' => I18n::getInstance()->getTitle('product_compilation'),
            'charge_type_id' => $item['charge_type_id'],
            'start_time' => $item['start_time'],
            'end_time' => $item['end_time'],
        ) : false;
    }

    public function getList($options=array()){
        $field = $join = $where = '';
        $param = array();

        if(!empty($options['user_id']) && is_numeric($options['user_id'])){
            $now_time = time();
            $field .= ",qcs.id AS subscription_id";
            $join .= "LEFT JOIN {$this->_table_question_compilation_subscription} AS qcs
                     ON qcs.compilation_id=qc.id AND qcs.user_id=:user_id AND (qcs.end_time=0 OR qcs.end_time>{$now_time})";
            $param[':user_id'] = $options['user_id'];
        }

        if(!empty($options['question_user_id']) && is_numeric($options['question_user_id'])){
            $field .= ",'' AS secondary_title";
            $where .= ' AND qc.question_user_id=:question_user_id';
            $param[':question_user_id'] = $options['question_user_id'];
        }else{
            $field .= ",IF(qu.id>0,CONCAT(qu.user_name,' | ',qu.headline),'') AS secondary_title";
            $join .= "LEFT JOIN {$this->_table_question_user} AS qu ON qu.id=qc.question_user_id";
        }

        $list = $this->MFindAllBySql(
            "SELECT qc.id,qc.title,qc.list_image_url AS image_url,qc.price,qc.has_free_item
                    ,qc.description,qc.subscription_num+qc.add_subscription_num AS subscription_sum
                    {$field}
             FROM {$this->_table} AS qc {$join}
             WHERE qc.status='1' AND qc.is_del='0' {$where}
             ORDER BY sort DESC"
        , $param);
        if(!empty($list)){
            foreach ($list as &$item){
                settype($item['price'], 'float');
                $item['image_url'] = IMAGE_DOMAIN.$item['image_url'];

                if($item['subscription_sum'] > 10000){
                    $item['subscription_sum'] = I18n::getInstance()->getOther('number_wan', array('num' => floor($item['subscription_sum']/10000)));
                }
                if(!empty($item['subscription_id'])){
                    $item['is_subscription'] = 1;
                }else{
                    settype($item['price'], 'float');
                    $item['is_subscription'] = 0;
                    $item['subscription_id'] = 0;
                }
                unset($item['subscription_id']);
            }
            unset($item);
            return $list;
        }else{
            return array();
        }
    }

    public function itemInfo($id){
        if(empty($id) || !is_numeric($id)){
            return false;
        }

        $where_status = "AND qc.status='1'";
        if($id == 14){
            $where_status = '';
        }

        $item = $this->MFindBySql(
            "SELECT qc.id,qc.title,qc.secondary_title,qc.item_image_url AS image_url,qc.price
                    ,qc.introduction_all,qc.introduction_height
                    ,qc.subscription_num+qc.add_subscription_num AS subscription_sum
                    ,qc.add_get_new_num AS get_new_sum,qc.get_new_qrcode_id,qc.charge_type_id
                    ,qu.id AS lecturer_user_id,qu.user_name AS lecturer_user_name
                    ,qu.headline AS lecturer_headline,qu.headimg_url AS lecturer_headimg_url
             FROM {$this->_table} AS qc
             LEFT JOIN {$this->_table_question_user} AS qu ON qu.id=qc.question_user_id
             WHERE qc.id=:id {$where_status} AND qc.is_del='0'"
        , array(':id' => $id));
        if(!empty($item)){
            if(empty($item['lecturer_user_id'])){
                $item['lecturer_user_id'] = 0;
                $item['lecturer_user_name'] = '';
                $item['lecturer_headline'] = '';
                $item['lecturer_headimg_url'] = '';
            }elseif(!empty($item['lecturer_headimg_url'])){
                $item['lecturer_headimg_url'] = IMAGE_DOMAIN.$item['lecturer_headimg_url'];
            }
            if(empty($item['introduction_height'])){
                $item['introduction_height'] = 100;
            }
            $item['image_url'] = IMAGE_DOMAIN.$item['image_url'];
            $item['get_new_qrcode_url'] = '';
            settype($item['price'], 'float');
            
            if(!empty($item['get_new_qrcode_id'])){
                $qrcode_info = WxQrcodeModel::model()->MGetInfoById($item['get_new_qrcode_id'], 'ticket');
                if(!empty($qrcode_info['ticket'])){
                    $item['get_new_qrcode_url'] = IWeiXin::getInstance()->showQrcode($qrcode_info['ticket']);
                }
            }
            unset($item['get_new_qrcode_id']);
            return $item;
        }else{
            return false;
        }
    }
    
    public function getSubscriptionStartAndEndTime($charge_type_id, $start, $end){
        switch ($charge_type_id){
            case 1:
                return array(
                    'start_time' => 0,
                    'end_time' => 0,
                );
                break;
            case 2:
                $year = date('Y');
                if(date('nd') < $start){
                    $year --;
                }

                if(mb_strlen($start) < 4){
                    $start = '0'.$start;
                }
                $start_time = strtotime($year.$start);
                $end_time = strtotime('+1 year', $start_time) - 1;
                return array(
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                );
                break;
            default:
                return false;
                break;
        }
    }

}