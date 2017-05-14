<?php

class NotifyController extends BaseController {
    protected $_public_id = '';

    public function init(){
        parent::init();
        $this->setI18nPath();
    }

    public function wx1Action() {
        if(empty(IWeiXin::getInstance($this->_public_id)->postObj)){
            exit;
        }

        $openid = Common::parseXMLByTagName(IWeiXin::getInstance($this->_public_id)->postStr, 'FromUserName');
        switch (IWeiXin::getInstance($this->_public_id)->postObj->MsgType){
            case 'text' :

                break;
            case 'event' :
                switch(IWeiXin::getInstance($this->_public_id)->postObj->Event){
                    case 'subscribe' :
                        $this->updateUserInfo($openid);
                        break;
                    case 'unsubscribe' :
                        $this->updateUserInfo($openid);
                        break;
                    case 'CLICK':

                        break;
                    case 'SCAN':

                        break;
                    case 'VIEW':

                        break;
                }
                break;
            default :
                break;
        }
    }

    public function wx4Action() {
        $this->_public_id = 4;
        if(empty(IWeiXin::getInstance($this->_public_id)->postObj)){
            exit;
        }

        $openid = Common::parseXMLByTagName(IWeiXin::getInstance($this->_public_id)->postStr, 'FromUserName');
        switch (IWeiXin::getInstance($this->_public_id)->postObj->MsgType){
            case 'text' :
                $content = IWeiXin::getInstance($this->_public_id)->postObj->Content;
                $this->sendKeyword($content,$openid);
                break;
            case 'event' :
                switch(IWeiXin::getInstance($this->_public_id)->postObj->Event){
                    case 'subscribe' :
                        $response_type = 1;
                        $eventKey = IWeiXin::getInstance($this->_public_id)->postObj->EventKey;
                        if (!empty($eventKey)){
                            // 将记录插入数据库
                            $str_arr = explode('_',$eventKey);
                            if(!empty($str_arr[1])){
                                $response_type = 2;
                                $eventKey = intval($str_arr[1]);
                            }
                        }

                        $user_info = $this->updateUserInfo($openid);

                        switch ($response_type){
                            case 1:default:
                            $this->sendSub();
                            IWeiXin::getInstance($this->_public_id)->responseMessageCustom($openid, 'DoAzAMq2aZ9LYG8KD45EbZK8VuDMT80i12YW6VOOihM', 'image');
                            break;
                            case 2:
                                $this->sendQrcode($eventKey,$openid,$user_info);
                                break;
                        }
                        LogScanTimeModel::model()->setTableByPublicId($this->_public_id);
                        LogScanTimeModel::model()->save($openid,'1');
                        break;
                    case 'unsubscribe' :
                        $this->updateUserInfo($openid);
                        LogScanTimeModel::model()->setTableByPublicId($this->_public_id);
                        LogScanTimeModel::model()->save($openid);
                        break;
                    case 'CLICK':
                        $key = Common::parseXMLByTagName(IWeiXin::getInstance($this->_public_id)->postStr, 'EventKey');
                        $this->sendClick($key);
                        break;
                    case 'SCAN':
                        $eventKey = IWeiXin::getInstance($this->_public_id)->postObj->EventKey;
                        if (!empty($eventKey)){
                            // 将记录插入数据库
                            $eventKey = intval($eventKey);
                            if (!empty($eventKey)) {
                                $this->sendQrcode($eventKey,$openid);
                            }
                        }
                        break;
                    case 'VIEW':

                        break;
                }
                break;
            default :
                break;
        }
    }

    /**
     * 微信多条回复
     * @param $data
     * @param $openid
     */
    private function handleDataCustom($data,$openid){
        if(empty($data['source_type']) || empty($data['send_data'])){
            return;
        }
        switch($data['source_type']){
            case 1:
                $articles = array();
                foreach($data['send_data'] as $i=>$d){
                    $articles[$i]['title'] = $d['title'];
                    $articles[$i]['description'] = $d['description'];
                    $articles[$i]['picurl'] = IMAGE_DOMAIN.$d['file_path'];
                    $articles[$i]['url'] = $d['url'];
                }

                if(!empty($articles)){
                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom($openid,$articles,'news');
                }
                break;
            case 2:
                IWeiXin::getInstance($this->_public_id)->responseMessageCustom($openid,$data['send_data'],'image');
                break;
            case 3:
                IWeiXin::getInstance($this->_public_id)->responseMessageCustom($openid,$data['send_data'],'text');
                break;
        }
    }

    /**
     * 微信单条图片
     * @param $data
     */
    private function handleData($data){
        if(empty($data['source_type']) || empty($data['send_data'])){
            return;
        }
        switch($data['source_type']){
            case 1:
                $articles = array();
                foreach($data['send_data'] as $i=>$d){
//                    $articles[$i]['title'] = str_replace('{{nickname}}',$this->_user['nickname'],$d['title']);
                    $articles[$i]['title'] = $d['title'];
                    $articles[$i]['description'] = $d['description'];
                    $articles[$i]['picurl'] = IMAGE_DOMAIN.$d['file_path'];
                    $articles[$i]['url'] = $d['url'];
                }

                if(!empty($articles)){
                    IWeiXin::getInstance($this->_public_id)->responseMessageArt($articles);
                }
                break;
            case 2:
                IWeiXin::getInstance($this->_public_id)->responseMessageImg($data['send_data']);
                break;
            case 3:
                IWeiXin::getInstance($this->_public_id)->responseTextMessage($data['send_data']);
                break;
        }
    }

    private function sendQrcode($qrcode_id,$openid,$user_info=array()){
        if(empty($qrcode_id) || empty($openid)){
            return;
        }

        $flag = true;
        if($qrcode_id == 68){
            $this->sendGetnewImg($openid, $user_info);
            WxSendQrcodeModel::model()->setPublicId($this->_public_id);
            $data = WxSendQrcodeModel::model()->getSend($qrcode_id);
            if (empty($data)) {
                return;
            }
            foreach ($data as $d) {
                $this->handleDataCustom($d, $openid);
            }
        }else {
            if ($qrcode_id > 100000000) {
                $this->sendGetnewSubscribeForQuestion($qrcode_id, $openid, $user_info);
                $flag = false;
            } elseif ($qrcode_id > 100000) {
                $this->sendGetnewSubscribe($qrcode_id, $openid, $user_info);
                $flag = false;
            } else {
                $check = WxQrcodeModel::model()->MFind(array(
                    'field' => 'type_id,link_id,title',
                    'where' => array(
                        'id' => $qrcode_id
                    )
                ));

                if(!empty($check)) {
                    if($check['type_id']==1 && $check['link_id']){ //type_id=1系统生成用于知页新知拉新
                        //扫描回复
                        $compilation_name = $check['title'];

                        IWeiXin::getInstance($this->_public_id)->responseTextMessage(I18n::getInstance()->getOther('notify_wx_getnew_compilation_scan_notice',array('compilation_name'=>$compilation_name)));
                        $this->sendGetnewImgForQuestion($check['link_id'],$openid, $user_info);
                    }else {
                        WxSendQrcodeModel::model()->setPublicId($this->_public_id);
                        $data = WxSendQrcodeModel::model()->getSend($qrcode_id);
                        if (empty($data)) {
                            return;
                        }
                        foreach ($data as $d) {
                            $this->handleDataCustom($d, $openid);
                        }
                    }
                }
            }
        }
        if($flag){
            LogWxQrcodeModel::model()->setTableByPublicId($this->_public_id);
            LogWxQrcodeModel::model()->save($openid,$qrcode_id,1);
            WxQrcodeModel::model()->MPlusField('scan_num',$qrcode_id);
        }
    }

    private function sendClick($key){
        WxMenuModel::model()->setPublicId($this->_public_id);
        $data = WxMenuModel::model()->getSend($key);

        $this->handleData($data);
    }

    /**
     * 用户关注自动回复
     */
    private function sendSub(){
        WxSendSubModel::model()->setPublicId($this->_public_id);
        $data = WxSendSubModel::model()->getSend();

        $this->handleData($data);
    }

    /**
     * 通过关键词获取回复内容
     * 完全匹配>左匹配>右匹配>模糊匹配
     */
    private function sendKeyword($content,$openid){
        if(empty($content) || empty($openid)){
            return;
        }

        WxSendKeywordModel::model()->setPublicId($this->_public_id);
        $data = WxSendKeywordModel::model()->getSend($content);

        if(empty($data)){
            return;
        }
        foreach($data as $d){
            $this->handleDataCustom($d,$openid);
        }
    }

    private function updateUserInfo($openid){
        WxUserModel::model()->setTableByPublicId($this->_public_id);
        return WxUserModel::model()->saveByOpenid($openid);
    }

    /**
     * 扫描新知二维码拉新图
     * @param $question_compilation_id
     * @param $openid
     * @param array $user_info
     * @return bool
     */

    private function sendGetnewImgForQuestion($question_compilation_id,$openid,$user_info=array()){
        if(empty($question_compilation_id)){
            return false;
        }
        $question_compilation_info = QuestionCompilationModel::model()->MFind($question_compilation_id);
        if(empty($question_compilation_info)){
            return false;
        }
        if(empty($user_info)){
            $user_info = $this->updateUserInfo($openid);
        }
        if(empty($user_info)){
            return false;
        }

        $user_info['question_compilation_id'] = $question_compilation_id;

        WxQuestionGetnewModel::model()->setTableAndPublicId($this->_public_id);
        $media_id = WxQuestionGetnewModel::model()->getMediaIdByWxUserInfo($user_info);
        if(!empty($media_id)){
            $res = IWeiXin::getInstance($this->_public_id)->responseMessageCustom($user_info['openid'], $media_id, 'image');
            if(!empty($res->errcode) && $res->errcode == -1){
                $media_id = WxQuestionGetnewModel::model()->getMediaIdByWxUserInfo($user_info, 1);
                if(!empty($media_id)){
                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom($user_info['openid'], $media_id, 'image');
                }
            }
        }

    }

    /**
     * 回复拉新图片
     * @param string $openid 用户openid
     * @param array $user_info 用户信息
     * @return bool
     */
    private function sendGetnewImg($openid, $user_info=array()){
        if(empty($user_info)){
            $user_info = $this->updateUserInfo($openid);
        }
        if(empty($user_info)){
            return false;
        }
        WxActivityGetnewModel::model()->setTableAndPublicId($this->_public_id);
        $media_id = WxActivityGetnewModel::model()->getMediaIdByWxUserInfo($user_info);
        if(!empty($media_id)){
            $res = IWeiXin::getInstance($this->_public_id)->responseMessageCustom($user_info['openid'], $media_id, 'image');
            if(!empty($res->errcode) && $res->errcode == -1){
                $media_id = WxActivityGetnewModel::model()->getMediaIdByWxUserInfo($user_info, 1);
                if(!empty($media_id)){
                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom($user_info['openid'], $media_id, 'image');
                }
            }
        }
    }

    private function sendGetnewSubscribeForQuestion($scene_id, $openid, $user_info=array()){
        if(empty($user_info)){
            $user_info = $this->updateUserInfo($openid);
        }
        if(empty($user_info)){
            return false;
        }

        //关注用户信息
        $subscribe_wx_user_id = $user_info['id'];
        if(empty($subscribe_wx_user_id)){
            return false;
        }
        //拉新用户信息
        $question_getnew_id= $scene_id - 100000000;

        if(!is_numeric($question_getnew_id) || $question_getnew_id<=0){
            return false;
        }
        WxQuestionGetnewModel::model()->setTableAndPublicId($this->_public_id);
        $question_getnew_info = WxQuestionGetnewModel::model()->MFind(array(
            'field' => 'wx_user_id,question_compilation_id',
            'where' => array(
                'id' => $question_getnew_id
            ),
        ));
        if(empty($question_getnew_info['wx_user_id']) || empty($question_getnew_info['question_compilation_id'])){
            return false;
        }

        $wx_user_id = $question_getnew_info['wx_user_id'];
        $question_compilation_id = $question_getnew_info['question_compilation_id'];

        $question_compilation_info = QuestionCompilationModel::model()->MFind(array(
            'field' => 'id,title',
            'where' => array(
                'id' => $question_compilation_id
            )
        ));
        if(empty($question_compilation_info)){
            return false;
        }

        $compilation_name = $question_compilation_info['title'];


        if($wx_user_id == $subscribe_wx_user_id){
            return IWeiXin::getInstance($this->_public_id)->responseTextMessage(I18n::getInstance()->getOther('notify_wx_getnew_compilation_is_self'));
        }

        WxUserModel::model()->setTableAndPublicId($this->_public_id);
        $wx_user_info = WxUserModel::model()->MGetInfoById($wx_user_id, 'id,openid,nickname');
        if(empty($wx_user_info['openid'])){
            return false;
        }

        //检查是否已经关联过
        WxQuestionGetnewSubscribeModel::model()->setTableAndPublicId($this->_public_id);
        $check_is_link = WxQuestionGetnewSubscribeModel::model()->checkIsLink($wx_user_id, $subscribe_wx_user_id);
        if(!empty($check_is_link)){
            IWeiXin::getInstance($this->_public_id)->responseTextMessage(I18n::getInstance()->getOther('notify_wx_getnew_compilation_has_link'));
            IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                $user_info['openid'],
                'bZJNKaGMK32ipIeL1MUoNL3LjKOCBNPWl0LJamXoNW8',
                'image'
            );
            return true;
        }else{
            //获取拉新用户已关联的数量
            $link_num = WxQuestionGetnewSubscribeModel::model()->getLinkNum($wx_user_id,$question_compilation_id);
            if($link_num >= 3){
//                $this->sendSub();
                IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                    $user_info['openid'],
                    I18n::getInstance()->getOther('notify_wx_getnew_compilation_success',
                        array('nickname'=>$wx_user_info['nickname'],'compilation_name'=>$compilation_name))
                );
                IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                    $user_info['openid'],
                    'bZJNKaGMK32ipIeL1MUoNL3LjKOCBNPWl0LJamXoNW8',
                    'image'
                );
                return true;
            }else{
                //创建关联
                $link_save_res = WxQuestionGetnewSubscribeModel::model()->saveLink($wx_user_id, $subscribe_wx_user_id,$question_compilation_id);

                if(!empty($link_save_res)){
                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                        $user_info['openid'],
                        I18n::getInstance()->getOther('notify_wx_getnew_compilation_success',
                            array('nickname'=>$wx_user_info['nickname'],'compilation_name'=>$compilation_name))
                    );

                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                        $user_info['openid'],
                        'bZJNKaGMK32ipIeL1MUoNL3LjKOCBNPWl0LJamXoNW8',
                        'image'
                    );

                    //满足条件,推送解锁码
                    if($link_num + 1 >= 3){
                        IWeiXin::getInstance($this->_public_id)->responseMessageCustom($wx_user_info['openid'],
                            I18n::getInstance()->getOther('notify_wx_getnew_compilation_notice_to_img_user_done',
                                array('nickname'=>$user_info['nickname'],'compilation_name'=>$compilation_name)));

                        WxQuestionGetnewModel::model()->setTableAndPublicId($this->_public_id);
                        $allow_use_res = WxQuestionGetnewModel::model()->MSave(array(
                            'id' => $question_getnew_id,
                            'allow_use' => '1'
                        ));
                        if(!empty($allow_use_res)){
                            $allow_use_url = Common::createResumeUrl('/wx/subscriberTips',array('id'=>$question_getnew_id),1);
                            IWeiXin::getInstance($this->_public_id)->responseMessageCustom($wx_user_info['openid'],
                                I18n::getInstance()->getOther('notify_wx_getnew_send_allow_use_url', array('allow_use_url'=>$allow_use_url,'compilation_name'=>$compilation_name)));
                        }
                    }else{
                        IWeiXin::getInstance($this->_public_id)->responseMessageCustom($wx_user_info['openid'],
                            I18n::getInstance()->getOther('notify_wx_getnew_compilation_notice_to_img_user',
                                array('nickname'=>$user_info['nickname'],'num'=>(2-$link_num))));
                    }
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

    /**
     * 回复通过拉新图片扫描过来的用户
     * @param int $scene_id 场景ID
     * @param string $openid 用户openid
     * @param array $user_info 用户信息
     * @return bool
     */
    private function sendGetnewSubscribe($scene_id, $openid, $user_info=array()){
        if(empty($user_info)){
            $user_info = $this->updateUserInfo($openid);
        }
        if(empty($user_info)){
            return false;
        }

        //关注用户信息
        $subscribe_wx_user_id = $user_info['id'];
        if(empty($subscribe_wx_user_id)){
            return false;
        }
        //拉新用户信息
        $wx_user_id = $scene_id - 100000;
        if(!is_numeric($wx_user_id) || $wx_user_id<=0){
            return false;
        }
        if($wx_user_id == $subscribe_wx_user_id){
            return IWeiXin::getInstance($this->_public_id)->responseTextMessage(I18n::getInstance()->getOther('notify_wx_getnew_is_self'));
        }
        $wx_user_info = WxUserModel::model()->MGetInfoById($wx_user_id, 'id,openid,nickname');
        if(empty($wx_user_info['openid'])){
            return false;
        }

        //检查是否已经关联过
        WxActivityGetnewSubscribeModel::model()->setTableAndPublicId($this->_public_id);
        $check_is_link = WxActivityGetnewSubscribeModel::model()->checkIsLink($wx_user_id, $subscribe_wx_user_id);
        if(!empty($check_is_link)){
            return IWeiXin::getInstance($this->_public_id)->responseTextMessage(I18n::getInstance()->getOther('notify_wx_getnew_has_link'));
        }else{
            //获取拉新用户已关联的数量
            $link_num = WxActivityGetnewSubscribeModel::model()->getLinkNum($wx_user_id);
            if($link_num >= 3){
                $this->sendSub();
                IWeiXin::getInstance($this->_public_id)->responseMessageCustom($openid, 'DoAzAMq2aZ9LYG8KD45EbZK8VuDMT80i12YW6VOOihM', 'image');
                return true;
//                return IWeiXin::getInstance($this->_public_id)->responseTextMessage(I18n::getInstance()->getOther('notify_wx_getnew_link_num_is_max'));
            }else{
                //创建关联
                $link_save_res = WxActivityGetnewSubscribeModel::model()->saveLink($wx_user_id, $subscribe_wx_user_id);
                if(!empty($link_save_res)){
                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                        $user_info['openid'],
                        I18n::getInstance()->getOther('notify_wx_getnew_link_success',
                        array('nickname'=>$wx_user_info['nickname']))
                    );
                    IWeiXin::getInstance($this->_public_id)->responseMessageCustom(
                        $user_info['openid'],
                        'DoAzAMq2aZ9LYG8KD45EbZK8VuDMT80i12YW6VOOihM',
                        'image'
                    );

                    //满足条件,推送解锁码
                    if($link_num + 1 >= 3){
                        IWeiXin::getInstance($this->_public_id)->responseMessageCustom($wx_user_info['openid'],
                            I18n::getInstance()->getOther('notify_wx_getnew_link_notice_to_img_user_done',
                                array('nickname'=>$user_info['nickname'])));

                        $unlock_code = TemplateCodeShareModel::model()->createCode(array('valid_num' => 2,), 6);
                        if(!empty($unlock_code)){
                            IWeiXin::getInstance($this->_public_id)->responseMessageCustom($wx_user_info['openid'],
                                I18n::getInstance()->getOther('notify_wx_getnew_send_unlock_code', array('unlock_code'=>$unlock_code)));
                            WxActivityGetnewModel::model()->setTableAndPublicId($this->_public_id);
                            WxActivityGetnewModel::model()->MUpdate(array('unlock_code'=>$unlock_code), array('wx_user_id'=>$wx_user_id));
                        }
                    }else{
                        IWeiXin::getInstance($this->_public_id)->responseMessageCustom($wx_user_info['openid'],
                            I18n::getInstance()->getOther('notify_wx_getnew_link_notice_to_img_user',
                                array('nickname'=>$user_info['nickname'],'num'=>(2-$link_num))));
                    }
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

}
