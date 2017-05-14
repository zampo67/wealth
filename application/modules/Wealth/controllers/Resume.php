<?php

class ResumeController extends BaseresumeController {
    protected $user_id = 0;
    protected $resume_id = 0;
    protected $resume_i18n_id = 0;

    public function init(){
        parent::init();
        $this->checkGuide();
        $this->user_id = $this->_user['id'];
        $this->resume_id = $this->_resume['id'];
        $this->resume_i18n_id = $this->_resume['i18n_id'];
    }

    /**
     * 获取列表数据统一接口
     */
    public function getList(){
        $handle_type = $this->_get('handle_type');

        $response = array();
        switch ($handle_type){
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                break;
        }
        $this->send($response);
    }

    /**
     * 获取详情数据统一接口
     */
    public function itemInfo(){
        $id = $this->_get('id');
        if(empty($id) || !is_numeric($id)){
            $this->sendFail(CODE_IS_EMPTY_FIELD);
        }
        $handle_type = $this->_get('handle_type');

        $response = array();
        switch ($handle_type){
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                break;
        }
        $this->send($response);
    }

    /**
     * 更新详情数据字段统一接口
     */
    public function itemUpdateField(){
        $id = $this->_get('id');
        if(empty($id) || !is_numeric($id)){
            $this->sendFail(CODE_IS_EMPTY_FIELD);
        }
        $handle_type = $this->_get('handle_type');

        $check_field = '';
        $check_is_status = 1;

        $response = array();
        $msg_key = 'success_update';

        switch ($handle_type){
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                $class = '';
                break;
        }

        $check_res = $class::model()->MFind(array(
            'field' => 'id'.$check_field,
            'where' => array('id' => $id)
        ), $check_is_status);
        if(empty($check_res)) {
            $this->sendFail(CODE_NOT_FOUND);
        }
    }

    /**
     * 获取编辑页数据统一接口
     */
    public function itemEdit(){
        $id = $this->_get('id', 0);
        $handle_type = $this->_get('handle_type');

        $response = array();
        switch ($handle_type){
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                break;
        }
        $this->send($response);
    }

    /**
     * 保存数据统一接口
     */
    public function itemSaveAction(){
        $handle_type = $this->_get('handle_type');
        $data = $this->_request();
        $save_data = $ext_data = array();

        $check_field = '';
        $check_is_status = 1;

        $response = array();
        $msg_key = 'success_save';

        switch ($handle_type){
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                $class = '';
                break;
        }

        if(!empty($data['id']) && is_numeric($data['id'])){
            $check_res = $class::model()->MFind(array(
                'field' => 'id'.$check_field,
                'where' => array('id' => $data['id'])
            ), $check_is_status);
            if(empty($check_res)) {
                $this->sendFail(CODE_NOT_FOUND);
            }

            //编辑
            switch ($handle_type){
                default :
                    $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                    break;
            }
            $save_data['id'] = $data['id'];
        }else{
            //新增
            switch ($handle_type){
                default:
                    break;
            }
        }

        $save_res = $class::model()->itemSave($save_data, $ext_data);
        if(!empty($save_res)){
            $this->send($response, I18n::getInstance()->getErrorController($msg_key));
        }else{
            $this->sendFail(CODE_HANDLE_FAIL);
        }
    }

    /**
     * 删除数据统一接口
     */
    public function itemDeleteAction(){
        $id = $this->_get('id', 0);
        if(empty($id) || !is_numeric($id)){
            $this->sendFail(CODE_IS_EMPTY_FIELD);
        }
        $handle_type = $this->_get('handle_type');

        $check_field = '';
        $check_is_status = 1;

        $response = $ext_data = array();
        $msg_key = 'success_delete';

        switch ($handle_type){
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                $class = '';
                break;
        }

        $check_res = $class::model()->MFind(array(
            'field' => 'id'.$check_field,
            'where' => array('id' => $id)
        ), $check_is_status);
        if(empty($check_res)){
            $this->sendFail(CODE_NOT_FOUND);
        }

        $delete_res = $class::model()->itemDelete($id, $ext_data);
        if(!empty($delete_res)){
            $this->send($response, I18n::getInstance()->getErrorController($msg_key));
        }else{
            $this->sendFail(CODE_DELETE_FAIL);
        }
    }

    /**
     * 获取简历信息
     */
    public function getResumeInfoAction(){
        $template_id = $this->_get('template_id');
        if(empty($template_id) ){
            $template_id = $this->_resume['template_id'];
        }

        $res = $this->getResumeDataForDisplay($this->_resume['id'],$template_id);

       $this->send($res);
    }

}
