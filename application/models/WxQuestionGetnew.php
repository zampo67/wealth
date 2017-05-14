<?php

class WxQuestionGetnewModel extends MBaseModel{
    protected $_table = '{{wx_question_getnew}}';
    protected $_public_id = '';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function rules(){
        return array(

        );
    }

    public function setTableByPublicId($public_id){
        if(isset($public_id) && is_numeric($public_id)){
            $this->setTable('wx_question_getnew_'.$public_id);
        }
    }

    public function setPublicId($public_id){
        if(!empty($public_id) && is_numeric($public_id)){
            $this->_public_id = $public_id;
        }
    }

    public function setTableAndPublicId($public_id){
        $this->setPublicId($public_id);
        $this->setTableByPublicId($public_id);
    }

    public function getMediaIdByWxUserInfo($user_info, $is_reset=0){
        //获取拉新信息
        $getnew_info = $this->MFind(array(
            'field' => 'id,ticket,media_id',
            'where' => array('wx_user_id' => $user_info['id'],'question_compilation_id'=>$user_info['question_compilation_id']),
        ));
        if(empty($getnew_info)){
            $getnew_save_res = $this->MSave(array('wx_user_id' => $user_info['id'],'question_compilation_id'=>$user_info['question_compilation_id']));
            if(empty($getnew_save_res)){
                Log::weixin('fail_save', 'wx_user_id:'.$user_info['id'].' and question_compilation_id'.$user_info['question_compilation_id']);
                return false;
            }
            $getnew_info['id'] = $getnew_save_res;
        }

        $media_path = IMAGE_PATH.UPLOAD_PATH.'wxnotify/getnew/question/';
        $media_name = 'bg_user_'.$getnew_info['id'].'.jpg';

        if($is_reset == 1){
            $getnew_info = array(
                'id' => $getnew_info['id'],
                'ticket' => '',
                'media_id' => '',
            );
            $this->MSave($getnew_info);
            unlink($media_path.$media_name);
        }

        //检测media_id
        if(empty($getnew_info['media_id'])){
            if(!file_exists($media_path.$media_name)){
                //获取临时二维码
                if(empty($getnew_info['ticket'])){
                    $ticket_res = IWeiXin::getInstance($this->_public_id)->qrcodeCreate($getnew_info['id']+100000000, 1, 2592000);
                    if(empty($ticket_res->ticket)){
                        Log::weixin('fail_ticket', 'scene_id:'.($user_info['id']+100000000));
                        return false;
                    }

                    $getnew_info['ticket'] = $ticket_res->ticket;
                    $this->MSave(array(
                        'id' => $getnew_info['id'],
                        'ticket' => $getnew_info['ticket'],
                    ));
                }

                //合并图片
                $merge_data = array(
                    'des_path' => $media_path,
                    'des_name' => $media_name,
                    'bg_path' => IMAGE_PATH.UPLOAD_PATH.'wxnotify/getnew/',
                    'bg_name' => 'bg_question.gif',
                    'bg_first' => 0,
                    'source_list' => array(
                        array(
                            'url' => IWeiXin::getInstance()->showQrcode($getnew_info['ticket']),
                            'temp_path' => IMAGE_PATH.IMAGE_TEMP_PATH,
                            'temp_name' => 'qrcode_'.$getnew_info['id'],
                            'temp_type' => '.jpg',
                            'width' => 165,
                            'height' => 165,
                            'x' => 87,
                            'y' => 1092,
                        ),
                        array(
                            'url' => $user_info['headimgurl'],
                            'temp_path' => IMAGE_PATH.IMAGE_TEMP_PATH,
                            'temp_name' => 'headimgurl_'.$user_info['id'],
                            'temp_type' => '.jpg',
                            'width' => 82,
                            'height' => 82,
                            'x' => 360,
                            'y' => 1093,
                        ),
                    ),
                    'text_list' => array(
                        array(
                            'ttf_path' => PUBLIC_PATH.'/static/fonts/msyh.ttf',
                            'fontsize' => 22,
                            'x' => 472,
                            'y' => 1120,
                            'text' => $user_info['nickname'],
                            'color_red' => 102,
                            'color_green' => 102,
                            'color_blue' => 102,
                        ),
                        array(
                            'ttf_path' => PUBLIC_PATH.'/static/fonts/msyh.ttf',
                            'fontsize' => 22,
                            'x' => 472,
                            'y' => 1160,
                            'text' => '邀请你一起参加',
                            'color_red' => 102,
                            'color_green' => 102,
                            'color_blue' => 102,
                        ),
                    ),
                );
                $merge_res = Common::mergeImagesToBgImage($merge_data);
                if(empty($merge_res)){
                    Log::weixin('fail_merge', json_encode($merge_data));
                    return false;
                }
            }

            //上传图片到微信
            $media_res = IWeiXin::getInstance($this->_public_id)->mediaUpload('image', $media_path.$media_name);
            if(empty($media_res->media_id)){
                Log::weixin('fail_media', 'media_path:'.$media_path.$media_name);
                return false;
            }
            $getnew_info['media_id'] = $media_res->media_id;
            $this->MSave(array(
                'id' => $getnew_info['id'],
                'media_id' => $getnew_info['media_id'],
            ));
        }
        return $getnew_info['media_id'];
    }

}