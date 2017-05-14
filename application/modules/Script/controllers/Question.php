<?php

class QuestionController extends BasescriptController {

    public function init(){
        parent::init();
    }

    /**
     * 每小时-计算总的 访问 相关数据
     */
    public function hourlyViewUserAction(){
        $analytics_time = time();
        LogRwxAnalyticsQuestionSumModel::model()->saveViewUser($analytics_time, 'hour');
    }

    /**
     * 每日-计算总的 流失/留存 相关数据
     */
    public function dailyUserLoseAndRetentionAction(){
        $analytics_time = time();
        LogRwxAnalyticsQuestionSumModel::model()->saveUserLoseDaily($analytics_time, 7);
        LogRwxAnalyticsQuestionSumModel::model()->saveUserRetentionDaily($analytics_time, array(1, 7, 30));
    }

    /**
     * 每日-计算总的 访问/支付 相关数据
     */
    public function dailyViewAndPayUserAction(){
        $analytics_time = time();
        LogRwxAnalyticsQuestionSumModel::model()->saveViewUser($analytics_time, 'day');
        LogRwxAnalyticsQuestionSumModel::model()->savePayUser($analytics_time, 'day');
    }

    /**
     * 每日-计算总的 收听/阅读/收听或阅读 相关数据
     */
    public function dailyListenAndReadUserAction(){
        $analytics_time = time();
        LogRwxAnalyticsQuestionSumModel::model()->saveListenUser($analytics_time, 'day');
        LogRwxAnalyticsQuestionSumModel::model()->saveReadUser($analytics_time, 'day');
//        LogRwxAnalyticsQuestionSumModel::model()->saveListenOrReadUser($analytics_time, 'day');
    }
    
    /**
     * 每日-计算每个问题的 访问/收听/阅读/收听或阅读/支付 相关数据
     */
    public function dailyQuesSumAction(){
        $analytics_time = time();
        LogRwxAnalyticsQuestionQuesSumModel::model()->saveQuesSum($analytics_time, 'day');
    }

    /**
     * 每日-计算每个合集的 访问/支付 相关数据
     */
    public function dailyCompSumAction(){
        $analytics_time = time();
        LogRwxAnalyticsQuestionCompSumModel::model()->saveCompSum($analytics_time, 'day');
    }

//    public function heheAction(){
//        for ($i=4; $i>=0; $i--){
//            $analytics_time = strtotime("-{$i} days");
//            LogRwxAnalyticsQuestionSumModel::model()->savePayUser($analytics_time, 'day');
//        }
//    }

}
