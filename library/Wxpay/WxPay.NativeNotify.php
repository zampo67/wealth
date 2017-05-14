<?php
Yaf\Loader::import('Wxpay/lib/WxPay.Api.php');
Yaf\Loader::import('Wxpay/lib/WxPay.Notify.php');

class WxPayNativeNotify extends WxPayNotify{
    public $query_data = array();

    //查询订单
    public function Queryorder($transaction_id){
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        Log::wxPay("query", json_encode($result));

        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            $this->query_data = $result;
            return true;
        }else{
            return false;
        }
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg){
        Log::wxPay("call back", json_encode($data));

        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            Log::wxPay("error msg", $msg);
            return false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            Log::wxPay("error msg", $msg);
            return false;
        }
        return true;
    }
}
