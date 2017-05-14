<?php


class IndexController extends BaseresumewxController {
    
    public function init(){
        $this->_check_token = 0;
        parent::init();
        if($this->_isWeixin()){
            $this->checkLogin(2);
        }
    }

    public function indexAction(){
//        if($this->_get('hahahehe') != 1){
//            $this->displayUpgrading();
//        }

        //兼容旧版链接
        $base_uri = '/wx';
        $request_uri = parse_url($this->_server('REQUEST_URI'));
        switch ($request_uri['path']){
            case $base_uri:
            case $base_uri.'/':
            case $base_uri.'/question/hot':
            case $base_uri.'/question/hot/':
            case $base_uri.'/question/category':
            case $base_uri.'/question/category/':
            case $base_uri.'/question/list':
            case $base_uri.'/question/list/':
                $this->redirect($base_uri.'/index');
                break;
            case $base_uri.'/question/view':
            case $base_uri.'/question/view/':
                $this->redirect($base_uri.'/questionFoo?questionId='.$this->_get('id'));
                break;
            case $base_uri.'/question/listen':
            case $base_uri.'/question/listen/':
                $this->redirect($base_uri.'/questionMy');
                break;
        }

        $ls_token = $this->commonCookie('ls_token');
        if(empty($ls_token) && !empty($this->commonCookie('browser_name'))){
            $this->commonCookie('ls_token', ResumeWebLsTokenModel::model()->getToken());
        }
        $this->displayIndex();
    }

}
