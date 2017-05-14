<?php


class PageController extends BaseresumewxController {
    
    public function init(){
        $this->_check_token = 0;
        parent::init();
    }

    public function indexAction(){
        $this->setHtmlHead();
        $this->setWxShare();
        $this->displayPartial('index', array(
            'wx_config' => IWeiXin::getInstance()->getJsApiSignPackage($this->_request('ls_wx_uri')),
            'wx_share' => $this->_tpl_vars['wx_share'],
        ));
    }

}
