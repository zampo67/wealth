<?php

class IndexController extends BaseWealthController {
    
    public function init(){
        $this->_check_token = 0;
        parent::init();
    }

    public function indexAction(){
        p_e('fsdfsdfsdfs');
        $ls_token = Common::cookie('ls_token');
        if(empty($ls_token) && !empty($this->getCacheBrowserName())){
            Common::cookie('ls_token', ResumeWebLsTokenModel::model()->getToken());
        }
        $this->displayIndex();
    }

}
