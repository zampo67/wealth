<?php

class WxActivityGetnewModel extends MBaseModel{
    protected $_table = '{{wx_activity_getnew}}';
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
            $this->setTable('wx_activity_getnew_'.$public_id);
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
            'where' => array('wx_user_id' => $user_info['id']),
        ));
        if(empty($getnew_info)){
            $getnew_save_res = $this->MSave(array('wx_user_id' => $user_info['id']));
            if(empty($getnew_save_res)){
                Log::weixin('fail_save', 'wx_user_id:'.$user_info['id']);
                return false;
            }
            $getnew_info['id'] = $getnew_save_res;
        }

        $media_path = IMAGE_PATH.UPLOAD_PATH.'wxnotify/getnew/user/';
        $media_name = 'bg_user_'.$user_info['id'].'.jpg';

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
                    $ticket_res = IWeiXin::getInstance($this->_public_id)->qrcodeCreate($user_info['id']+100000, 1, 2592000);
                    if(empty($ticket_res->ticket)){
                        Log::weixin('fail_ticket', 'scene_id:'.($user_info['id']+100000));
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
                    'bg_name' => 'bg.gif',
                    'bg_first' => 0,
                    'source_list' => array(
                        array(
                            'path' => IMAGE_PATH.UPLOAD_PATH.'wxnotify/getnew/top.jpg',
                            'cut' => 0,
                            'unlink' => 0,
                            'width' => 750,
                            'height' => 868,
                            'x' => 0,
                            'y' => 0,
                        ),
                        array(
                            'url' => IWeiXin::getInstance()->showQrcode($getnew_info['ticket']),
                            'temp_path' => IMAGE_PATH.IMAGE_TEMP_PATH,
                            'temp_name' => 'qrcode_'.$user_info['id'],
                            'temp_type' => '.jpg',
                            'width' => 184,
                            'height' => 184,
                            'x' => 280,
                            'y' => 1088,
                        ),
                        array(
                            'url' => $user_info['headimgurl'],
                            'temp_path' => IMAGE_PATH.IMAGE_TEMP_PATH,
                            'temp_name' => 'headimgurl_'.$user_info['id'],
                            'temp_type' => '.jpg',
                            'width' => 100,
                            'height' => 100,
                            'x' => 126,
                            'y' => 900,
                        ),
                    ),
                    'text_list' => array(
                        array(
                            'ttf_path' => PUBLIC_PATH.'/static/fonts/msyh.ttf',
                            'fontsize' => 22,
                            'x' => 259,
                            'y' => 940,
                            'text' => $user_info['nickname'],
                            'color_red' => 102,
                            'color_green' => 102,
                            'color_blue' => 102,
                        ),
                        array(
                            'ttf_path' => PUBLIC_PATH.'/static/fonts/msyh.ttf',
                            'fontsize' => 22,
                            'x' => 259,
                            'y' => 986,
                            'text' => '邀请你使用知页简历模板',
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