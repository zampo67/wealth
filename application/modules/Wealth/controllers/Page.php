<?php

class PageController extends BaseresumeController {
    
    public function init(){
        $this->_check_token = 0;
        parent::init();
    }

    public function linkRedirectAction(){
        if($this->getCacheBrowserName()){
            $link_id = $this->_get_decrypt('id', '', 'link_redirect');
            $link_info = LinkRedirectModel::model()->MGetInfoById($link_id, 'id,redirect_url');
            if(!empty($link_info)){
                LogRecordLinkRedirectModel::model()->MSave(array(
                    'link_redirect_id' => $link_info['id'],
                    'ip' => Tools::getRemoteAddr(),
                ));
                LinkRedirectModel::model()->MPlusField('redirect_sum', $link_info['id']);
                $this->redirect($link_info['redirect_url']);
            }
        }
    }

}
