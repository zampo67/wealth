<?php

class CrontabController extends BasescriptController {

    public function init(){
        parent::init();
    }

    private function delSessionByActiveTime($prefix=''){
        if(!empty($prefix)){
            ISession::getInstance()->setKeyPrefix($prefix);
            ISession::getInstance()->delSessionByActiveTime(30 * 86400);
        }
    }

    /**
     * 每日-resume-删除长时间未使用的session
     */
//    public function dailyDelSessionResume(){
//        Log::script('start', 'dailyDelSessionResume');
//        $this->delSessionByActiveTime('r');
//        Log::script(' end ', 'dailyDelSessionResume');
//    }

    /**
     * 每日-resumewx-删除长时间未使用的session
     */
    public function dailyDelSessionResumewxAction(){
        $this->delSessionByActiveTime('rwx');
    }

    /**
     * 每分钟-队列消费
     */
    public function minuteQueueConsumerAction(){
        $queue = new Queue();
        $list = $queue->consumer(20);
        if(!empty($list)){
            foreach ($list as $item){
                $info = json_decode($item, true);
                if(!empty($info['openid'])){
                    Log::script('queue', 'openid:'.$info['openid']);
                    QuestionCompilationSubscriptionModel::model()->sendCompilationInfoUpdateTemplateMsgToWxPublicByInfo($info);
                    $queue->delete();
                }
            }
        }
    }

}
