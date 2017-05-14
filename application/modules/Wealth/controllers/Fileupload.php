<?php

class FileuploadController extends BaseresumeController {
    
    public function init(){
        parent::init();
        $this->checkLogin();
    }

    private function setImageData(){
//        $files = $this->_files();
//        $files = $files['file'];
//        $info = file_get_contents($files['tmp_name']);
        $info = $this->_requestBody();
        if(empty($info)){
            return false;
        }
        $file = array(
            'type' => $this->_get('type', 'image/jpeg'),
            'name' => $this->_get('name'),
            'size' => $this->_get('size'),
        );
        return ImageModel::model()->setImageData($info, $file, $this->_user['id']);
    }

    // 接口-上传图片
    public function imageAction(){
        $data = $this->setImageData();
        if($data){
            $this->send(array(
                'image_id' => $data['id'],
                'image_domain' => IMAGE_DOMAIN,
                'image_url_origin' => $data['save_url'].$data['save_name'],
            ));
        }else{
            $this->sendFail('image', I18n::getInstance()->getErrorController('upload_image_wrong'));
        }
    }

    public function imageCutAction(){
        $data = $this->_post('data');
        $image_id = $this->_post('image_id');
        $image_url = $this->_post('image_url');

        if(!empty($data)){
            $str = base64_decode(substr(strstr($data,','),1));

            if(!empty($image_id) && is_numeric($image_id)){
                $image_info = ImageModel::model()->getInfoByPk($image_id);
                if(empty($image_info)){
                    $this->sendFail('image_id', I18n::getInstance()->getErrorController('upload_image_wrong'));
                }
            }else{
                if(empty($image_url)){
                    $this->sendFail('image_url', I18n::getInstance()->getErrorController('upload_image_wrong'));
                }

                $content = file_get_contents(IMAGE_PATH.$image_url);
                $md5 = md5($content);
                $image_info = ImageModel::model()->getInfoByMd5($md5);
                if(empty($image_info)){
                    $image_id = ImageModel::model()->MSave(array(
                        'user_id' => $this->_user['id'],
                        'save_url' => '',
                        'save_name' => '',
                        'thumb_name' => '',
                        'square_cut_name' => '',
                        'md5' => $md5,
                        'status' => '0',
                    ));
                    if(empty($image_id)){
                        return false;
                    }

                    $save_path = ImageModel::model()->getUserUpload();
                    $save_url = '/upload/images/'.date('Ym').'/';
                    list($usec, $sec) = explode(" ", microtime());
                    $fileName = floor($sec + $usec * 1000000);
                    $fileName .= rand(1000,9999);
                    $fileName .= $image_id;
                    $type = '.jpg';

                    copy(IMAGE_PATH.$image_url, $save_path.'/'.$fileName.$type);
                    $image_name = $thumb_name = $square_cut_name = $fileName.$type;
                    $res = ImageModel::model()->MSave(array(
                        'id' => $image_id,
                        'save_url' => $save_url,
                        'save_name' => $image_name,
                        'thumb_name' => $thumb_name,
                        'square_cut_name' => $square_cut_name,
                        'status' => '1',
                    ));

                    if(empty($res)){
                        $this->sendFail('image_url', I18n::getInstance()->getErrorController('upload_image_wrong'));
                    }
                    $image_info = array(
                        'save_url' => $save_url,
                        'save_name' => $image_name,
                        'square_cut_name' => $square_cut_name,
                    );
                }else{
                    $image_id = $image_info['id'];
                }
            }

            $exp = explode('.', $image_info['square_cut_name']);
            $name = empty($exp[0]) ? Common::getMicrotime() : $exp[0];
            $type = empty($exp[1]) ? '' : '.'.$exp[1];

            $name_user = $name.'_'.$this->_user['id'].rand(10,99);
            $square_cut_name_user = $name_user.$type;
            $file_user = IMAGE_PATH.$image_info['save_url'].$square_cut_name_user;

            //存储用户切图
            file_put_contents($file_user, $str);
            $exif_type = Common::getImageTypeByPath($file_user);

            if($exif_type != $type){
                rename($file_user, IMAGE_PATH.$image_info['save_url'].$name_user.$exif_type);
                $square_cut_name_user = $name_user.$exif_type;
                $file_user = IMAGE_PATH.$image_info['save_url'].$square_cut_name_user;
            }

            new ImageProc($file_user, $file_user, IMAGE_SQUARE_SIZE, IMAGE_SQUARE_SIZE);

            $this->send(array(
                'image_id' => $image_id,
                'image_domain' => IMAGE_DOMAIN,
                'image_url' => $image_info['save_url'].$square_cut_name_user,
                'image_url_origin' => $image_info['save_url'].$image_info['save_name'],
            ));
            exit;
        }else{
            $this->sendFail('data', I18n::getInstance()->getErrorController('upload_image_cut_wrong'));
        }
    }

}
