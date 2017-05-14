<?php

class CacheController extends BasescriptController {

    public function init(){
        parent::init();
    }

    public function setResumeCompanyWordAction(){
        $list = CompanyModel::model()->MFindAll(array(
            'field' => 'id,name,logo_url',
            'where' => array('is_rec' => '1'),
            'order' => 'id ASC',
        ));
        if(!empty($list)){
            $key_hash = 'company_list_hash';
            $key_set = 'company_list_set_';
            $list_set = array();

            foreach ($list as $item){
                $length = mb_strlen($item['name'], 'UTF-8');
                for ($i=0; $i<$length; $i++){
                    $word = md5(mb_substr($item['name'], $i, 1, 'UTF-8'));
                    if(!isset($list_set[$word])){
                        $list_set[$word] = array();
                    }
                    $list_set[$word][] = $item['id'];
                }
            }
            if(!empty($list_set)){
                foreach ($list_set as $k=>$v){
                    IRedis::getInstance()->sAdd($key_set.$k, $v);
                }
            }
            IRedis::getInstance()->hMSet($key_hash, Common::arrayColumnToKey($list, 'id'));
        }
    }

    public function updateQQUnionid(){
        $list = UserModel::model()->MFindAllBySql(
            "SELECT id,qq_openid 
             FROM {{user}}
             WHERE qq_openid!='' AND qq_unionid=''
             LIMIT 20"
        );
        if(!empty($list)){
            foreach ($list as $item){
                $unionid = QQOAuth::getUnionidByOpenid($item['qq_openid']);
                if(!empty($unionid)){
                    UserModel::model()->MSave(array(
                        'id' => $item['id'],
                        'qq_unionid' => $unionid,
                    ));
                }
            }
        }
    }

    public function compilationSumRand(){
        $list = QuestionCompilationModel::model()->MFindAll(array(
            'field' => 'id',
        ), 0, 0, 0);
        if(!empty($list)){
            foreach ($list as $item){
                $subscription_sum_add = rand(400, 500);
                QuestionCompilationModel::model()->MSave(array(
                    'id' => $item['id'],
                    'subscription_num' => 0,
                    'add_subscription_num' => $subscription_sum_add,
                    'add_get_new_num' => $subscription_sum_add-100,
                ));
            }
        }
    }

    public function questionSumRand(){
        $list = QuestionModel::model()->MFindAll(array(
            'field' => 'id,read_sum_add,add_up,is_free',
        ), 0, 0, 0);
        if(!empty($list)){
            foreach ($list as $item){
                $read_sum_add = rand(400, 500);
                if(!empty($item['is_free'])){
                    $read_sum_add += 300;
                }
                $like_sum_add = rand(40, 50);
                QuestionModel::model()->MSave(array(
                    'id' => $item['id'],
                    'read_sum' => 0,
                    'read_sum_add' => $read_sum_add,
                    'up' => 0,
                    'add_up' => $like_sum_add,
                ));
            }
        }
    }

}
