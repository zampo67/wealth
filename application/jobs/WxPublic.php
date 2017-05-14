<?php
/**
 * Created by PhpStorm.
 * User: James
 * Date: 19/04/2017
 * Time: 15:43
 */

class WxPublic{

    public function setUp(){
        // ... Set up environment for this job
    }

    public function perform(){
        if(!empty($this->args['job_action'])){
            $action = $this->args['job_action'];
            unset($this->args['job_action']);
            if(method_exists($this, $action)){
                $this->$action();
            }
        }
    }

    public function sendCompilationInfoUpdateTemplateMsg(){
        QuestionCompilationSubscriptionModel::model()->sendCompilationInfoUpdateTemplateMsgToWxPublicByInfo($this->args);
    }

    public function tearDown(){
        // ... Remove environment for this job
    }
    
}
