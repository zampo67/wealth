<?php

class QuestionCardModel extends MBaseModel{
    protected $_table = '{{question_card}}';

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

    public function getListByQuestionId($question_id, $options=array()){
        $find_options = array(
            'field' => 'id,type_id,content',
            'where' => array('question_id' => $question_id),
            'order' => 'sort ASC',
        );

        if(!empty($options['limit']) && is_numeric($options['limit'])){
            $find_options['limit'] = $options['limit'];
        }

        $list = $this->MFindAll($find_options, 1);
        if(!empty($list)){
            $minutes = $this->getCardMinutes($this->MGetCount());
            foreach ($list as $k=>&$item){
                $item['minutes'] = $minutes;
                if($k % 4 == 3){
                    $minutes --;
                }
                if($item['type_id'] == VariablesModel::model()->getAttrId('resumeQuestionCardType', 'image')){
                    $item['content'] = IMAGE_DOMAIN.$item['content'];
                }
            }
            return $list;
        }else{
            return array();
        }
    }

    public function getCardMinutes($card_sum){
        return !empty($card_sum) ? ceil($card_sum/4) : '';
    }
    
}