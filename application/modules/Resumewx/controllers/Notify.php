<?php

class NotifyController extends BaseController {

    public function init(){
        parent::init();
        $this->setI18nPath();
    }

    private function wxNotifyResponse($log){
        Log::wxPay('notify_res', $log);
        exit;
    }
    
    //微信支付
    public function wxpayAction(){
        if(empty($GLOBALS['HTTP_RAW_POST_DATA'])){
            $this->wxNotifyResponse('data is empty');
        }
        Log::wxPay('notify_req', json_encode($GLOBALS['HTTP_RAW_POST_DATA']));

        // 检查签名
        Yaf\Loader::import('Wxpay/WxPay.NativeNotify.php');
        $notify = new WxPayNativeNotify();
        $result = $notify->Handle(false);
        if($result){
            $response = $notify->query_data;
            // 检查APP_ID
            if(empty($response['appid'])){
                $this->wxNotifyResponse('appid is empty');
            }
            if($response['appid'] != WEIXIN_PUBLIC_APPID){
                $this->wxNotifyResponse('wrong appid:'.$response['appid']);
            }

            // 检查附加信息是否存在
            if(empty($response['attach'])){
                $this->wxNotifyResponse('attach is empty');
            }

            // 检查附件信息中的值
            $attach = json_decode($response['attach'], true);
            if(empty($attach['type']) || empty($attach['id']) || !is_numeric($attach['id'])){
                $this->wxNotifyResponse('wrong attach:'.$response['attach']);
            }

            switch ($attach['type']){
                case 'resume':
                    // 检查信息是否存在
                    $info = ResumeModel::model()->MGetInfoById($attach['id'], 'id,user_id,i18n_id');
                    if(empty($info)){
                        $this->wxNotifyResponse('resume not exist:'.$attach['id']);
                    }
                    // 检查价格是否正确
                    $price = ResumeModel::model()->getProductPrice($info['i18n_id'], 'yuan');
                    if(empty($response['total_fee'])){
                        $this->wxNotifyResponse('total_fee is empty');
                    }
                    $response['total_fee'] = $response['total_fee'] * 0.01;
                    if($response['total_fee'] != $price){
                        $this->wxNotifyResponse('wrong price:'.$response['total_fee']);
                    }

                    //是否已经解锁
                    $max_activity_id = ResumeModel::model()->getTemplateMaxActivityId();
                    $self_is_unlock = LogTemplateUnlockModel::model()->MFind(array(
                        'field' => 'id',
                        'where' => array(
                            'activity_id' => $max_activity_id,
                            'resume_id' => $info['id'],
                        )
                    ), 0);
                    if(empty($self_is_unlock)){
                        //解锁
                        LogTemplateUnlockModel::model()->MSave(array(
                            'activity_id' => $max_activity_id,
                            'code_id' => VariablesModel::model()->getAttrs('resumeUnlockCodeByPay', 'wxpay_app', 'id'),
                            'resume_id' => $info['id'],
                            'user_id' => $info['user_id'],
                            'i18n_id' => $info['i18n_id'],
                            'type' => '1',
                            'status' => '3',
                        ));
                    }

                    $pay_save_data = array(
                        'user_id' => $info['user_id'],
                        'link_id' => $info['id'],
                        'link_type' => 'resume',
                    );
                    break;
//                case 'question':
//                    if(empty($attach['user_id']) || !is_numeric($attach['user_id'])){
//                        $this->wxNotifyResponse('wrong attach:'.$response['attach']);
//                    }
//                    // 检查信息是否存在
//                    $info = QuestionModel::model()->MFind(array(
//                        'field' => 'id',
//                        'where' => array('id' => $attach['id'])
//                    ), 0, 0);
//                    if(empty($info)){
//                        $this->wxNotifyResponse('question not exist:'.$attach['id']);
//                    }
//                    // 检查价格是否正确
//                    if(empty($response['total_fee'])){
//                        $this->wxNotifyResponse('total_fee is empty');
//                    }
//                    $response['total_fee'] = $response['total_fee'] * 0.01;
//
//                    // 写入已收听
//                    QuestionListenModel::model()->createIsListen($attach['user_id'], $info['id'], array('total_fee'=>$response['total_fee']));
//                    $pay_save_data = array(
//                        'user_id' => $attach['user_id'],
//                        'link_id' => $info['id'],
//                        'link_type' => 'question',
//                    );
//                    break;
                case 'compilation':
                    if(empty($attach['user_id']) || !is_numeric($attach['user_id'])){
                        $this->wxNotifyResponse('wrong attach:'.$response['attach']);
                    }

                    // 检查信息是否存在
                    $info = QuestionCompilationModel::model()->MFind(array(
                        'field' => 'id,charge_type_id,start_time,end_time',
                        'where' => array('id' => $attach['id'])
                    ), 0, 0);
                    if(empty($info)){
                        $this->wxNotifyResponse('compilation not exist:'.$attach['id']);
                    }
                    // 检查价格是否正确
                    if(empty($response['total_fee'])){
                        $this->wxNotifyResponse('total_fee is empty');
                    }
                    $response['total_fee'] = $response['total_fee'] * 0.01;

                    // 写入已订阅
                    QuestionCompilationSubscriptionModel::model()->createIsSubscription($attach['user_id'], $info['id'], array(
                            'total_fee' => $response['total_fee'],
                            'charge_type_id' => $info['charge_type_id'],
                            'start_time' => $info['start_time'],
                            'end_time' => $info['end_time'],
                    ));
                    $pay_save_data = array(
                        'user_id' => $attach['user_id'],
                        'link_id' => $info['id'],
                        'link_type' => 'compilation',
                    );
                    break;
                default:
                    $this->wxNotifyResponse('wrong attach type:'.$attach['type']);
                    break;
            }

            //支付记录
            $pay_log_check = LogPayWxModel::model()->MFind(array(
                'field' => 'id',
                'where' => array(
                    'appid' => $response['appid'],
                    'mch_id' => $response['mch_id'],
                    'transaction_id' => $response['transaction_id'],
                ),
            ));
            if(empty($pay_log_check)){
                LogPayWxModel::model()->MSave(array(
                    'user_id' => $pay_save_data['user_id'],
                    'link_id' => $pay_save_data['link_id'],
                    'link_type_id' => VariablesModel::model()->getAttrs('payLinkType', $pay_save_data['link_type'], 'id'),
                    'appid' => $response['appid'],
                    'mch_id' => $response['mch_id'],
                    'device_info' => !empty($response['device_info']) ? $response['device_info'] : '',
                    'openid' => !empty($response['openid']) ? $response['openid'] : '',
                    'trade_type' => $response['trade_type'],
                    'bank_type' => $response['bank_type'],
                    'total_fee' => $response['total_fee'],
                    'fee_type' => !empty($response['fee_type']) ? $response['fee_type'] : '',
                    'cash_fee' => !empty($response['cash_fee']) ? $response['cash_fee'] * 0.01 : 0,
                    'cash_fee_type' => !empty($response['cash_fee_type']) ? $response['cash_fee_type'] : '',
                    'coupon_fee' => !empty($response['coupon_fee']) ? $response['coupon_fee'] * 0.01 : 0,
                    'coupon_count' => !empty($response['coupon_count']) ? $response['coupon_count'] : 0,
                    'transaction_id' => $response['transaction_id'],
                    'out_trade_no' => $response['out_trade_no'],
                    'time_end' => !empty($response['time_end']) ? strtotime($response['time_end']) : 0,
                ));
            }
        }
        $this->wxNotifyResponse('success');
    }

}
