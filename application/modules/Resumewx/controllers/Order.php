<?php

class OrderController extends BaseresumewxController{
    protected $user_id = 0;

    public function init(){
        parent::init();
        $this->checkBind();
        $this->user_id = $this->_user['id'];
    }

    public function getWxpayOrderAction(){
        $id = $this->_get('id');
        $product_type = $this->_get('product_type');
        if(empty($id) || !is_numeric($id)){
            $this->sendFail(CODE_NOT_ALLOWED_FORMAT);
        }
        $response = array();

        switch ($product_type){
//            case 'question':
//                $order_info = QuestionModel::model()->getOrderInfo($id, $this->user_id);
//                if(empty($order_info)){
//                    $this->sendError();
//                }
//
//                // 价格为0,直接支付成功
//                if(empty($order_info['order_price'])){
//                    QuestionListenModel::model()->createIsListen($this->user_id, $id, array('total_fee'=>$order_info['order_price']));
//                    $response['next'] = 'pay_success';
//                    $response['product'] = array('voice_url' => $order_info['voice_url']);
//                    $this->sendAndExit($response);
//                }
//
//                $product_data = array(
//                    'body' => $order_info['order_name'],
//                    'out_trade_no' => $order_info['order_id'],
//                    'total_fee' => $order_info['order_price'],
//                    'attach' => json_encode(array(
//                        'type' => $product_type,
//                        'id' => $id,
//                        'user_id' => $this->user_id,
//                    )),
//                );
//                break;
            case 'compilation':
                $order_info = QuestionCompilationModel::model()->getOrderInfo($id, $this->user_id);
                if(empty($order_info)){
                    $this->sendError();
                }

                // 价格为0,直接支付成功
                if(empty($order_info['order_price'])){
                    QuestionCompilationSubscriptionModel::model()->createIsSubscription($this->user_id, $id, array(
                        'total_fee' => $order_info['order_price'],
                        'charge_type_id' => $order_info['charge_type_id'],
                        'start_time' => $order_info['start_time'],
                        'end_time' => $order_info['end_time'],
                    ));
                    $response['next'] = 'pay_success';
                    $response['product'] = new stdClass();
                    $this->sendAndExit($response);
                }

                $product_data = array(
                    'body' => $order_info['order_name'],
                    'out_trade_no' => $order_info['order_id'],
                    'total_fee' => $order_info['order_price'],
                    'attach' => json_encode(array(
                        'type' => $product_type,
                        'id' => $id,
                        'user_id' => $this->user_id,
                    )),
                );
                break;
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                $product_data = array();
                break;
        }

        Log::wxPay('product', json_encode($product_data));
        // 实例化微信统一下单
        Yaf\Loader::import('Wxpay/lib/WxPay.JsApiPay.php');
        $input = new WxPayUnifiedOrder();
        $input->SetBody($product_data['body']);
        $input->SetAttach($product_data['attach']);
        $input->SetOut_trade_no($product_data['out_trade_no']);
        $input->SetTotal_fee($product_data['total_fee']*100);
        $input->SetNotify_url(WEIXIN_PAY_NOTIFY_URL);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($this->_openid);
        $order = WxPayApi::unifiedOrder($input);
        Log::wxPay('start', json_encode($order));
        if(isset($order['return_msg']) && $order['return_msg'] == 'OK'){
            // 实例化微信JSAPI支付实现类
            $tools = new JsApiPay();
            $params = json_decode($tools->GetJsApiParameters($order), true);
            $response['next'] = 'wx_pay';
            $response['wx_pay'] = $params;
            $this->send($response);
        }else{
            $this->sendFail(CODE_ACTION_NOT_ALLOWED, I18n::getInstance()->getErrorController('order_create_fail'));
        }
    }

}
