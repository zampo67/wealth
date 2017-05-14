<?php

class QuestionModel extends MBaseModel{
    protected $_table = '{{question}}';
    protected $_table_question_compilation_item = '{{question_compilation_item}}';
    protected $_table_question_compilation = '{{question_compilation}}';
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
        $item = $this->MGetInfoById($id, 'price,voice_url');
        return !empty($item) ? array(
            'order_id' => VariablesModel::model()->getAttrs('payLinkType', 'question', 'id').'_'.$id.'_'.date("ymdHis").$user_id,
            'order_price' => (float)$item['price'],
            'order_name' => I18n::getInstance()->getTitle('product_question'),
            'voice_url' => !empty($item['voice_url']) ? IMAGE_DOMAIN.$item['voice_url'] : '',
        ) : false;
    }
    
    public function itemInfo($id){
        if(empty($id) || !is_numeric($id)){
            return false;
        }

        $item = $this->MFindBySql(
            "SELECT q.id,q.title,q.voice_url,q.voice_time,q.is_free,q.image_url,q.content
                    ,q.up+q.add_up AS like_sum,q.read_sum+q.read_sum_add AS read_sum
                    ,qc.id AS compilation_id,qc.title AS compilation_title
                    ,qc.charge_type_id,qc.price AS compilation_price
                    ,qc.add_get_new_num AS get_new_sum,qc.get_new_qrcode_id
             FROM {$this->_table} AS q
             LEFT JOIN {$this->_table_question_compilation_item} AS qci ON qci.question_id=q.id AND qci.status='1' AND qci.is_del='0'
             LEFT JOIN {$this->_table_question_compilation} AS qc ON qc.id=qci.compilation_id AND qc.is_del='0'
             WHERE q.id=:id AND q.status='1' and q.is_del='0'
             LIMIT 1"
        , array(':id' => $id));
        if(!empty($item)){
            if(empty($item['compilation_id'])){
                $item['compilation_id'] = 0;
                $item['compilation_title'] = '';
                $item['compilation_price'] = 0;
            }else{
                settype($item['compilation_price'], 'float');
            }

            if(!empty($item['voice_url'])){
                $item['voice_url'] = IMAGE_DOMAIN.$item['voice_url'];
            }else{
                $item['voice_time'] = '';
            }

            $item['image_url'] = !empty($item['image_url']) ? IMAGE_DOMAIN.$item['image_url'] : '';
            $item['get_new_qrcode_url'] = '';
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

    public function getMinute($second){
        if(empty($second)){
            return '';
        }
        if($second>60){
            $minute = floor($second/60);
            $second = $second%60;
        }else{
            $minute = '';
        }

        if($minute){
            $minute = ($minute>9) ? $minute.'\'' : '0'.$minute.'\'';
        }else{
            $minute = '00\'';
        }

        $second = ($second>9) ? $second : '0'.$second;

        return $minute.$second;
    }

}