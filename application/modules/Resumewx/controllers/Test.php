<?php


class TestController extends BaseresumewxController {
    
    public function init(){
        $this->_check_token = 0;
        parent::init();
        if($this->_isWeixin()){
            $this->checkLogin(2);
        }
    }

    public function indexAction(){
//        p($this->getRequest()->getEnv());
//        p($this->getRequest()->getRequestUri());
//        p($this->getModuleName());
//        p($this->getRequest()->getControllerName());
//        p_e($this->getRequest()->getActionName());
//        for ($i=40; $i>=0; $i--){
//            LogRwxAnalyticsQuestionSumModel::model()->saveViewUser(strtotime("-{$i} days"), 'day');
//        }
//        for ($i=10; $i>=0; $i--){
//            $timestamp = strtotime(date('Y/m/d',strtotime("-{$i} days")));
//            for ($j=0; $j<24; $j++){
//                LogRwxAnalyticsQuestionSumModel::model()->saveViewUser($timestamp + $j*3600 + 1, 'hour');
//            }
//        }

//        for ($i=40; $i>=0; $i--){
//            LogRwxAnalyticsQuestionSumModel::model()->saveUserLoseDaily(strtotime("-{$i} days"), 7);
//        }

//        for ($i=40; $i>=0; $i--){
//            LogRwxAnalyticsQuestionSumModel::model()->saveUserRetentionDaily(strtotime("-{$i} days"), array(1,7,30));
//        }

//        LogRwxAnalyticsQuestionCompSumModel::model()->saveCompSum(strtotime("-0 days"), 'day');
//        LogRwxAnalyticsQuestionSumModel::model()->saveUserRetentionDaily(time(), array(1,7,30));
//        LogRwxAnalyticsQuestionSumModel::model()->saveUserLoseDaily(strtotime('-2 days'), 7);
        p_e($this->_user);
    }

}
