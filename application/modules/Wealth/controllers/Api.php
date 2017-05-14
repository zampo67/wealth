<?php

class ApiController extends BaseresumeController {
    public function init(){
        $this->_check_token = 0;
        parent::init();
        $this->checkLAN();
    }

    public function sendEmailAction(){
        $handle_type = $this->_request('handle_type');
        $email = $this->_request('email');
        $res = false;

        switch($handle_type){
            case 'resume_bind_user_email':
                $user_id = $this->_request('user_id');
                $res = Common::sendMailVerify($email,$user_id);
                break;
        }

        if($res){
            $this->send(array('is_send'=>1));
        }else{
            $this->send(array('is_send'=>0));
        }
    }

    public function templateDataAction(){
        $resume_id = $this->_request('id',70355);
        $template_id = $this->_request('template_id',1);

        $resume = new ResumeTemplate($resume_id,$template_id);

        $data = $resume->getTemplateData();

        $this->send($data);
    }
}