<?php

class QuestionController extends BaseresumewxController {
    protected $user_id = 0;
    protected $_qrcode_url = IMAGE_DOMAIN.QRCODE_IMAGE_URL.'question/question.png';

    public function init(){
        parent::init();
        if($this->_isWeixin()){
            $this->setUserInfo();
        }
    }

    private function setUserInfo($is_send=0){
        if($this->checkBind($is_send)){
            $this->user_id = $this->_user['id'];
        }
    }

    private function setRecordView($url_type_id, $link_type_id=0, $link_id=0){
        if($this->_isWeixin() && !empty($this->_wx_user['id'])){
            LogRwxRecordQuestionViewModel::model()->itemSave(array(
                'wx_user_id' => $this->_wx_user['id'],
                'user_id' => $this->user_id,
                'url_type_id' => $url_type_id,
                'url_param' => !empty($this->_request('ls_wx_uri')) ? urldecode($this->_request('ls_wx_uri')) : $this->_server('REQUEST_URI'),
                'link_id' => $link_id,
                'link_type_id' => $link_type_id,
                'plat_type_id' => VariablesModel::model()->getAttrs('platType', 'web_mobile', 'id'),
            ));
        }
    }

    /**
     * 获取列表数据统一入口
     * @param string $handle_type 类型
     */
    private function getList($handle_type){
        $response = array('not_bind' => 1);
        switch ($handle_type){
            case 'compilation':
                $this->setRecordView(VariablesModel::model()->getAttrs('resumeQuestionUrlType', 'compilation_list', 'id'));

                $response['compilation'] = $response['banner'] = array(
                    'list' => array(),
                    'num' => 0,
                );

                $response['banner']['list'] = QuestionBannerModel::model()->getList();
                $response['banner']['num'] = count($response['banner']['list']);
                $response['compilation']['list'] = QuestionCompilationModel::model()->getList(array('user_id' => $this->user_id));
                $response['compilation']['num'] = count($response['compilation']['list']);

                $this->setWxShare(array('title' => I18n::getInstance()->getOther('wx_share_question_list')));
                break;
            case 'user_order':
                $this->setRecordView(VariablesModel::model()->getAttrs('resumeQuestionUrlType', 'user_order', 'id'));

                $response['compilation'] = array(
                    'list' => array(),
                    'num' => 0,
                );

                if($this->_isWeixin()){
                    $response['qrcode_url'] = '';

                    $response['compilation']['list'] = QuestionCompilationSubscriptionModel::model()->getListByUserId($this->user_id);
                    $response['compilation']['num'] = count($response['compilation']['list']);
                }else{
                    $response['qrcode_url'] = $this->_qrcode_url;
                }

                $this->setWxShare(array('title' => I18n::getInstance()->getOther('wx_share_question_list')));
                break;
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                break;
        }

        if(!empty($this->user_id) || !$this->_isWeixin()){
            $response['not_bind'] = 0;
        }
        $this->send($response);
    }

    /**
     * 获取详情数据统一入口
     * @param string $handle_type 类型
     * @param int $default_id 默认ID值
     */
    private function itemInfo($handle_type, $default_id=0){
        $id = $this->_get('id', $default_id);
        if(empty($id) || !is_numeric($id)){
            $this->sendFail(CODE_IS_EMPTY_FIELD);
        }

        $response = array('not_bind' => 1);
        switch ($handle_type){
            case 'compilation':
                $this->setRecordView(VariablesModel::model()->getAttrs('resumeQuestionUrlType', 'compilation_item', 'id'), VariablesModel::model()->getAttrs('resumeQuestionLinkType', 'compilation', 'id'), $id);

                $item = QuestionCompilationModel::model()->itemInfo($id);
                if(empty($item)){
                    $this->sendError();
                }

                if($this->_isWeixin()){
                    $response['qrcode_url'] = '';
                    // 检查是否已订阅合集
                    $is_subscription = QuestionCompilationSubscriptionModel::model()->checkIsSubscription($this->user_id, $id);
                    $this->setWxShare(array('title' => I18n::getInstance()->getOther('wx_share_question_item', array('title'=>$item['title']))));
                }else{
                    $response['qrcode_url'] = $this->_qrcode_url;
                    $is_subscription = false;
                }

                if(!empty($is_subscription)){
                    $question_options = array('compilation_subscription' => 1);
                    $item['is_subscription'] = 1;
                    $item['price'] = 0;
                }else{
                    $question_options = array('user_id' => $this->user_id);
                    $item['is_subscription'] = 0;
                }

                // 获取问题列表
                $item['question']['list'] = QuestionCompilationItemModel::model()->getQuestionListByCompilationId($id, $question_options);
                $item['question']['num'] = count($item['question']['list']);

                $response['item'] = $item;
                break;
            case 'question':
                $this->setRecordView(VariablesModel::model()->getAttrs('resumeQuestionUrlType', 'question_item', 'id'), VariablesModel::model()->getAttrs('resumeQuestionLinkType', 'question', 'id'), $id);

                if($this->_isWeixin()){
                    $response['qrcode_url'] = '';
                    $item = QuestionModel::model()->itemInfo($id);
                    if(empty($item)){
                        $this->sendError();
                    }

                    // 是否已听
//                    $is_listen = QuestionListenModel::model()->checkIsListen($this->user_id, $id);
//                    if(empty($is_listen) && !empty($item['compilation_id'])){
//                        $is_listen = QuestionCompilationSubscriptionModel::model()->checkIsSubscription($this->user_id, $item['compilation_id']);
//                    }
                    $item['is_subscription'] = QuestionCompilationSubscriptionModel::model()->checkIsSubscription($this->user_id, $item['compilation_id']);
                    if(empty($item['is_subscription'])){
                        if(empty($item['is_free'])){
                            $this->sendFail(CODE_ERROR_REDIRECT, '', array(
                                'module' => 'compilation_item',
                                'id' => $item['compilation_id']
                            ));
                        }
                        $item['is_subscription'] = 0;
                    }else{
                        $item['is_subscription'] = 1;
                    }

//                    $item['card'] = array('list' => array(), 'num' => 0);
//                    if(!empty($item['card_sum'])){
//                        $item['card']['list'] = QuestionCardModel::model()->getListByQuestionId($id);
//                        $item['card']['num'] = count($item['card']['list']);
//
//                        // 上次阅读记录
////                        $last_read = LogRwxRecordQuestionCardReadModel::model()->MFind(array(
////                            'field' => 'card_num',
////                            'where' => array(
////                                'user_id' => $this->user_id,
////                                'question_id' => $id,
////                            )
////                        ));
////                        $item['card_last_read_num'] = (!empty($last_read['card_num']) && $last_read['card_num']<=$item['card_sum']) ? $last_read['card_num'] : 0;
//                        $item['card_last_read_num'] = 0;
//                    }else{
//                        $item['card_last_read_num'] = 0;
//                    }

                    // 是否已赞
                    $is_like = QuestionLikeModel::model()->checkIsLike($this->user_id, $id);
                    $item['is_like'] = !empty($is_like) ? 1 : 0;
                    $item['app_download_url'] = RESUME_DOMAIN.'/page/linkRedirect?id=FIVYap2GoWbvAqs11QfFKw%3D%3D000002';

                    LogRwxRecordQuestionReadModel::model()->itemSave(array(
                        'user_id' => $this->user_id,
                        'question_id' => $id,
                    ));
                    $this->setWxShare(array('title' => I18n::getInstance()->getOther('wx_share_question_item', array('title'=>$item['title']))));
                }else{
                    $response['qrcode_url'] = $this->_qrcode_url;
                    $item = new \stdClass();
                }
                $response['item'] = $item;
                break;
            case 'lecturer':
                $this->setRecordView(VariablesModel::model()->getAttrs('resumeQuestionUrlType', 'lecturer_item', 'id'), VariablesModel::model()->getAttrs('resumeQuestionLinkType', 'lecturer', 'id'), $id);

                $item = QuestionUserModel::model()->itemInfo($id);
                if(empty($item)){
                    $this->sendError();
                }

                if($this->_isWeixin()){
                    $response['qrcode_url'] = '';
//                    $this->setWxShare(array('title' => I18n::getInstance()->getOther('wx_share_question_item', array('title'=>''))));
                }else{
                    $response['qrcode_url'] = $this->_qrcode_url;
                }

                $item['compilation']['list'] = QuestionCompilationModel::model()->getList(array('user_id' => $this->user_id, 'question_user_id' => $id));
                $item['compilation']['num'] = count($item['compilation']['list']);

                $response['item'] = $item;
                break;
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                break;
        }

        if(!empty($this->user_id) || !$this->_isWeixin()){
            $response['not_bind'] = 0;
        }
        $this->send($response);
    }

    private function itemSave($handle_type){
        $this->setUserInfo(1);
        $data = $this->_request();
        $save_data = $ext_data = array();

        $check_field = '';
        $check_is_status = 1;

        $response = array();
        $msg_key = 'success_save';

        switch ($handle_type){
            case 'question_like':
                $class = 'QuestionLikeModel';
                $this->verificationModelRules($class, $data);
                $question_check = QuestionModel::model()->MGetInfoById($data['question_id']);
                if(empty($question_check)){
                    $this->sendError();
                }

                $like_check = $class::model()->MFind(array(
                    'field' => 'id',
                    'where' => array(
                        'user_id' => $this->user_id,
                        'question_id' => $question_check['id'],
                        'type_id' => 1,
                    ),
                ));
                if(!empty($like_check)){
                    $this->sendAndExit($response, I18n::getInstance()->getErrorController($msg_key), 0);
                }

                $save_data = array(
                    'user_id' => $this->user_id,
                    'question_id' => $question_check['id'],
                    'type_id' => 1,
                );
                break;
            case 'callback_wx_share':
                $class = 'LogRwxRecordQuestionShareModel';
                $this->verificationModelRules($class, $data, array('disable'=>array('link_type_id')));

                $url_type_attrs = VariablesModel::model()->getAttrs('resumeQuestionUrlType');
                $link_type_attrs = VariablesModel::model()->getAttrs('resumeQuestionLinkType');
                switch ($data['url_type_id']){
                    case $url_type_attrs['question_item']['id']:
                        if(empty(QuestionModel::model()->MGetInfoById($data['link_id']))){
                            $this->sendError();
                        }
                        $data['link_type_id'] = $link_type_attrs['question']['id'];
                        break;
                    case $url_type_attrs['compilation_item']['id']:
                        if(empty(QuestionCompilationModel::model()->MGetInfoById($data['link_id']))){
                            $this->sendError();
                        }
                        $data['link_type_id'] = $link_type_attrs['compilation']['id'];
                        break;
                    default:
                        $data['link_id'] = $data['link_type_id'] = 0;
                        break;
                }

                $save_data = array(
                    'user_id' => $this->user_id,
                    'url_type_id' => $data['url_type_id'],
                    'link_id' => $data['link_id'],
                    'link_type_id' => $data['link_type_id'],
                    'share_type_id' => $data['share_type_id'],
                );
                break;
            case 'callback_question_listen':
                $class = 'LogRwxRecordQuestionListenModel';
                $this->verificationModelRules($class, $data);
                $question_check = QuestionModel::model()->MGetInfoById($data['question_id']);
                if(empty($question_check)){
                    $this->sendError();
                }

                $save_data = array(
                    'user_id' => $this->user_id,
                    'question_id' => $question_check['id'],
                );
                break;
//            case 'callback_card_read':
//                $check_field = ',question_id,card_num,card_ids';
//                $class = 'LogRwxRecordQuestionCardReadModel';
//                $this->verificationModelRules($class, $data);
//
//                $card_check = QuestionCardModel::model()->MFind(array(
//                    'field' => 'id,question_id,sort+1 AS sort',
//                    'where' => array('id' => $data['card_id'])
//                ), 0, 0);
//                if(empty($card_check)){
//                    $this->sendError();
//                }
//
//                $question_check = QuestionModel::model()->MGetInfoById($card_check['question_id'], 'id,price');
//                if(empty($question_check)){
//                    $this->sendError();
//                }
//                settype($question_check['price'], 'float');
//                if(empty($question_check['price'])){
//                    QuestionListenModel::model()->createIsListen($this->user_id, $card_check['question_id']);
//                }
//
//                $save_data = array(
//                    'user_id' => $this->user_id,
//                    'question_id' => $card_check['question_id'],
//                    'card_ids' => $card_check['id'],
//                    'card_num' => $card_check['sort'],
//                );
//                break;
            default:
                $this->sendFail(CODE_ACTION_NOT_ALLOWED);
                break;
        }

        if(!empty($data['id']) && is_numeric($data['id'])){
            $check_res = $class::model()->MFind(array(
                'field' => 'id'.$check_field,
                'where' => array('id' => $data['id'])
            ), $check_is_status);
            if(empty($check_res)) {
                $this->sendError();
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
            switch ($handle_type) {
                case 'question_like':
                    QuestionModel::model()->MPlusField('up', $save_data['question_id']);
                    break;
            }
            $this->send($response, I18n::getInstance()->getErrorController($msg_key), 0);
        }else{
            $this->sendFail(CODE_HANDLE_FAIL);
        }
    }

    public function obtainCompilationAction(){
        //默认返回
        $send_res= array(
            'is_success' => 0,
            'compilation_name' => '',
            'qrcode_url' => IMAGE_DOMAIN.'/static/images/common/qrcode_obtain_compilation.jpeg'
        );

        //检查解锁的id是否合规
        $question_getnew_id = $this->_get_decrypt('id','','link_obtain_compilation');
        if(empty($question_getnew_id) || !is_numeric($question_getnew_id)){
            $this->sendAndExit($send_res);
        }

        //检查解锁的信息是否存在
        WxQuestionGetnewModel::model()->setTableAndPublicId(4);
        $getnew_info = WxQuestionGetnewModel::model()->MFind(array(
            'field' => 'allow_use,use_user_id,question_compilation_id',
            'where' => array('id'=>$question_getnew_id),
        ));
        if(empty($getnew_info['question_compilation_id']) || empty($getnew_info['allow_use']) || $getnew_info['use_user_id']>0 ){
            $this->sendAndExit($send_res);
        }

        //是否登录
        if(!empty($this->_user['id'])){
            //检查要解锁的合集是否存在
            $question_compilation_info = QuestionCompilationModel::model()->MFind(array(
                'field' => 'id,title,charge_type_id,start_time,end_time',
                'where' => array(
                    'id' => $getnew_info['question_compilation_id']
                )
            ));
            if(empty($question_compilation_info)){
                $this->sendAndExit($send_res);
            }

            //解锁合集
            $unlock_compilation = QuestionCompilationSubscriptionModel::model()->createIsSubscription(
                $this->_user['id'],
                $getnew_info['question_compilation_id'],
                array(
                    'subscription_type_id'=>2,
                    'charge_type_id' => $question_compilation_info['charge_type_id'],
                    'start_time' => $question_compilation_info['start_time'],
                    'end_time' => $question_compilation_info['end_time'],
                )
            );
            if($unlock_compilation){
                WxQuestionGetnewModel::model()->MSave(array('id'=>$question_getnew_id,'use_user_id'=>$this->_user['id']));
                $send_res['is_success'] = 1;
                $send_res['compilation_name'] = !empty($question_compilation_info['title']) ? "「&nbsp;{$question_compilation_info['title']}&nbsp;」" : '';
                $this->send($send_res);
            }else{
                $this->sendAndExit($send_res);
            }
        }else{
            WxQuestionGetnewModel::model()->MSave(array('id'=>$question_getnew_id, 'status'=>0));
            $this->sendAndExit($send_res);
        }
    }

    // 接口-用户已购列表
    public function userOrderAction(){
        $this->getList('user_order');
    }

    // 接口-合集列表
    public function compilationAction(){
        $this->getList('compilation');
    }

    // 接口-合集详情
    public function compilationViewAction(){
        $this->itemInfo('compilation');
    }

    // 接口-问题详情
    public function questionViewAction(){
        $this->itemInfo('question');
    }

    // 接口-问题点赞
    public function questionLikeAction(){
        $this->itemSave('question_like');
    }

    // 接口-讲师详情
    public function lecturerViewAction(){
        $this->itemInfo('lecturer');
    }

    // 接口-微信分享回调
    public function callbackWxShareAction(){
        $this->itemSave('callback_wx_share');
    }

    // 接口-音频播放回调
    public function callbackQuestionListenAction(){
        $this->itemSave('callback_question_listen');
    }

    // 接口-卡片阅读回调
//    public function callbackCardReadAction(){
//        $this->itemSave('callback_card_read');
//    }

}