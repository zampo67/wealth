<?php


class ApiController extends BaseresumewxController {
    
    public function init(){
        $this->_check_token = 0;
        parent::init();
    }

    public function getWxPayCodeUrlAction(){
        $product_data = $this->_request();
        Log::debug('data', json_encode($product_data));

        Yaf\Loader::import('Wxpay/lib/WxPay.Api.php');
        $input = new WxPayUnifiedOrder();
        $input->SetBody($product_data['body']);
        $input->SetAttach($product_data['attach']);
        $input->SetOut_trade_no($product_data['out_trade_no']);
        $input->SetTotal_fee($product_data['total_fee']*100);
        $input->SetNotify_url($product_data['notify_url']);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($product_data['product_id']);
        $order = WxPayApi::unifiedOrder($input);
        Log::debug('start', json_encode($order));
        if(!empty($order["code_url"])){
            $this->send(array('code_url' => $order["code_url"]));
        }else{
            $this->sendFail(CODE_ACTION_NOT_ALLOWED, I18n::getInstance()->getErrorController('order_create_fail'));
        }
    }

}
