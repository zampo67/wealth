<?php

class QuestionCompilationSubscriptionModel extends MBaseModel{
    protected $_table = '{{question_compilation_subscription}}';
    protected $_table_question_compilation = '{{question_compilation}}';
    protected $_table_user = '{{user}}';
    protected $_table_wx_user = '{{wx_user}}';
    protected $_wx_public_id = '';

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

    public function getListByUserId($user_id){
        $list = array();
        if(!empty($user_id) && is_numeric($user_id)){
            $list = $this->MFindAllBySql(
                "SELECT qc.id,qc.title,qc.list_image_url AS image_url,qcs.total_fee AS price
                 FROM {$this->_table} AS qcs
                 LEFT JOIN {$this->_table_question_compilation} AS qc ON qc.id=qcs.compilation_id
                 WHERE qcs.user_id=:user_id AND qcs.status='1' AND qcs.is_del='0'
                 ORDER BY id DESC"
            , array(':user_id' => $user_id));
            if(!empty($list)){
                foreach ($list as &$item){
                    $item['image_url'] = IMAGE_DOMAIN.$item['image_url'];
                    settype($item['price'], 'float');
                }
                unset($item);
            }else{
                $list = array();
            }
        }
        return $list;
    }

    public function checkIsSubscription($user_id, $compilation_id){
        if(empty($user_id) || !is_numeric($user_id) || empty($compilation_id) || !is_numeric($compilation_id)){
            return false;
        }
        $item = $this->MFind(array(
            'field' => 'end_time',
            'where' => array('user_id' => $user_id, 'compilation_id' => $compilation_id)
        ));
        if(!empty($item)){
            return (empty($item['end_time']) || $item['end_time']>time()) ? true : false;
        }else{
            return false;
        }
    }

    public function createIsSubscription($user_id, $compilation_id, $params=array()){
        if(empty($user_id) || !is_numeric($user_id) || empty($compilation_id) || !is_numeric($compilation_id)){
            return false;
        }

        if(empty($this->checkIsSubscription($user_id, $compilation_id))){
            $limit_time = QuestionCompilationModel::model()->getSubscriptionStartAndEndTime($params['charge_type_id'], $params['start_time'], $params['end_time']);
            if(!empty($limit_time)){
                $save_res = $this->MSave(array(
                    'user_id' => $user_id,
                    'compilation_id' => $compilation_id,
                    'total_fee' => isset($params['total_fee']) ? $params['total_fee'] : 0,
                    'start_time' => $limit_time['start_time'],
                    'end_time' => $limit_time['end_time'],
                    'subscription_type_id' => isset($params['subscription_type_id']) ? $params['subscription_type_id'] : 1,
                ));
                if(!empty($save_res)){
                    QuestionCompilationModel::model()->MPlusField('subscription_num', $compilation_id);
                    $this->sendSubscriptionSuccTemplateMsgToWxPublic($user_id, $compilation_id);
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function sendSubscriptionSuccTemplateMsgToWxPublic($user_id, $compilation_id){
        if(empty($user_id) || !is_numeric($user_id) || empty($compilation_id) || !is_numeric($compilation_id)){
            return false;
        }

        $user_info = UserModel::model()->MGetInfoById($user_id, 'wx_unionid');
        if(!empty($user_info['wx_unionid'])){
            $wx_user_info = WxUserModel::model()->MFind(array(
                'field' => 'openid,is_subscribe',
                'where' => array('unionid' => $user_info['wx_unionid']),
            ));

            if(!empty($wx_user_info['is_subscribe']) && !empty($wx_user_info['openid'])){
                $compilation_info = QuestionCompilationModel::model()->MGetInfoById($compilation_id, 'id,title,wx_tmp_msg_sub_succ_desc,wx_tmp_msg_sub_succ_remark,price,subscription_num+add_subscription_num AS subscription_sum');

                if(!empty($compilation_info)){
                    return IWeiXin::getInstance($this->_wx_public_id)->responseMessageTemplate(
                        $wx_user_info['openid'],
                        WxPublicModel::model()->getMessageTemplateId('compilation_subscription_success', $this->_wx_public_id),
                        array(
                            'first' => array(
                                "value" => I18n::getInstance()->getOther('wx_template_msg_ques_com_sub_succ_title'),
                                "color" => "#173177",
                            ),
                            'keyword1' => array(
                                "value" => $compilation_info['title'],
                                "color" => "#173177",
                            ),
                            'keyword2' => array(
                                "value" => I18n::getInstance()->getOther('people_sum', array('num' => $compilation_info['subscription_sum'])),
                                "color" => "#173177",
                            ),
                            'keyword3' => array(
                                "value" => I18n::getInstance()->getOther('price_yuan', array('price' => $compilation_info['price'])),
                                "color" => "#173177",
                            ),
                            'remark' => array(
                                "value" => $compilation_info['wx_tmp_msg_sub_succ_remark'],
                                "color" => "#00bff6",
                            ),
                        ),
                        Common::createResumeUrl('wx/questionSubs', array('albumId' => $compilation_id), 1)
                    );
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function sendCompilationInfoUpdateTemplateMsgToWxPublic($compilation_id){
        if(empty($compilation_id) || !is_numeric($compilation_id)){
            return false;
        }

        $compilation_info = QuestionCompilationModel::model()->MGetInfoById($compilation_id, 'id,title,wx_tmp_msg_info_update_desc,wx_tmp_msg_info_update_remark');

        if(!empty($compilation_info)){
            $user_list = $this->MFindAllBySql(
                "SELECT qc.user_id,u.wx_openid_public,wu.openid
                 FROM {$this->_table} AS qc
                 LEFT JOIN {$this->_table_user} AS u ON u.id=qc.user_id
                 LEFT JOIN {$this->_table_wx_user} AS wu ON wu.unionid=u.wx_unionid
                 WHERE qc.status='1' AND qc.is_del='0' AND qc.compilation_id=:compilation_id AND u.wx_unionid!=''
                     AND wu.openid!='' AND wu.is_subscribe='1'"
            , array(':compilation_id' => $compilation_id));

            if(!empty($user_list)){
                $send_openid_list = array();
                $template_id = WxPublicModel::model()->getMessageTemplateId('compilation_info_update', $this->_wx_public_id);
                $data_first = I18n::getInstance()->getOther('wx_template_msg_ques_com_info_update_title');
                foreach ($user_list as $user_item){
                    $openid = !empty($user_item['wx_openid_public']) ? $user_item['wx_openid_public'] :
                        ( !empty($user_item['openid']) ? $user_item['openid'] : '' ) ;

                    if(!empty($openid) && !isset($send_openid_list[$openid])){
                        IPHPResque::getInstance()->producer('WxPublic', array(
                            'job_action' => 'sendCompilationInfoUpdateTemplateMsg',
                            'openid' => $openid,
                            'template_id' => $template_id,
                            'data_first' => $data_first,
                            'data_keyword1' => $compilation_info['title'],
                            'data_keyword2' => $compilation_info['wx_tmp_msg_info_update_desc'],
                            'data_remark' => $compilation_info['wx_tmp_msg_info_update_remark'],
                            'id' => $compilation_info['id'],
                        ));
                        $send_openid_list[$openid] = 1;
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }

    public function sendCompilationInfoUpdateTemplateMsgToWxPublicByInfo($info){
        IWeiXin::getInstance($this->_wx_public_id)->responseMessageTemplate(
            $info['openid'],
            $info['template_id'],
            array(
                'first' => array(
                    "value" => $info['data_first'],
                    "color" => "#173177",
                ),
                'keyword1' => array(
                    "value" => $info['data_keyword1'],
                    "color" => "#173177",
                ),
                'keyword2' => array(
                    "value" => $info['data_keyword2'],
                    "color" => "#173177",
                ),
                'remark' => array(
                    "value" => $info['data_remark'],
                    "color" => "#00bff6",
                ),
            ),
            Common::createResumeUrl('wx/questionSubs', array('albumId' => $info['id']), 1)
        );
    }
    
}