<?php

class ImageModel extends MBaseModel {
    protected $_table = '{{image}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    /**
     * Model规则
     * @return array
     */
    public function rules(){
        return array(

        );
    }

    /**
     * 根据图片ID获取图片信息
     * @param int $id 图片ID
     * @return array
     */
    public function getInfoByPk($id){
        return $this->MFind(array(
            'field' => "id,md5,save_url,save_name,thumb_name,square_cut_name",
            'where' => array('id' => $id),
        ));
    }

    public function getOriginUrl($id){
        $image_info = $this->getInfoByPk($id);
        $origin_url = '';
        if(!empty($image_info)){
            $origin_url = $image_info['save_url'].$image_info['save_name'];
        }

        return $origin_url;
    }

    public function getSquareUrl($id){
        $image_info = $this->getInfoByPk($id);
        $square_url = '';
        if(!empty($image_info)){
            $square_url = $image_info['save_url'].$image_info['square_cut_name'];
        }

        return $square_url;
    }

    /**
     * 根据图片MD5值获取图片信息
     * @param string $md5 图片MD5值
     * @return array
     */
    public function getInfoByMd5($md5){
        return $this->MFind(array(
            'field' => "id,md5,save_url,save_name,thumb_name,square_cut_name",
            'where' => array('md5' => $md5),
        ));
    }

    public function setImageData($info, $file, $user_id=0,$handle_type='base'){
        $md5 = md5($info);
        $exist = $this->getInfoByMd5($md5);
        if($exist){
            return $exist;
        }else{
            //预存储
            $save_data = array(
                'user_id' => $user_id,
                'save_url' => '',
                'save_name' => '',
                'thumb_name' => '',
                'square_cut_name' => '',
                'md5' => $md5,
                'status' => '0',
            );
            switch($handle_type){
                case 'system_school_image':
                    unset($save_data['user_id']);
                    $save_data['admin_id'] = $user_id;
                    break;
            }
            $image_id = $this->MSave($save_data);
            if(empty($image_id)){
                return false;
            }
            //存储图片
            $up = new FileUpload();
            //设置属性(上传的位置， 大小， 类型， 名是是否要随机生成)
            list($path,$upload_url) = $this->getUserUpload($handle_type);
            $up -> set("image_id", $image_id);
            $up -> set("path", $path);
            $up -> set("maxsize", 1024*1024*10);
            $up -> set("allowtype", array("gif", "png", "jpg","jpeg"));

            $tmp_path = PUBLIC_PATH.IMAGE_TEMP_PATH.'images/';
            $tmp_name = time().$image_id;
            $type = Common::getImageType($file['type']);
            if(!is_dir($tmp_path)){
                mkdir($tmp_path,0777,true);
            }
            file_put_contents($tmp_path.$tmp_name.$type, $info);
            $exif_type = Common::getImageTypeByPath($tmp_path.$tmp_name.$type);
            if($exif_type != $type){
                rename($tmp_path.$tmp_name.$type, $tmp_path.$tmp_name.$exif_type);
                $type = $exif_type;
            }

            $options = array(
                'name' => $file['name'],
                'tmp_name' => $tmp_path.$tmp_name.$type,
                'size' => $file['size'],
                'error' => 0,
            );

            //使用对象中的upload方法， 就可以上传文件， 方法需要传一个上传表单的名子 pic, 如果成功返回true, 失败返回false
            if($up->upload($options)){
                $image_name = $up->getFileName();
                list($image_name_show,$image_name_type) = explode('.',$image_name);
                $image_path = $path.$image_name;
                if(file_exists($image_path) && $up->getFileType()=='jpg'){
                    Common::adjustPicOrientation($image_path);
                }

                //压缩图片
                $thumb_name = $image_name_show.'_thumb.'.$image_name_type;
                new ImageProc($path.$image_name, $path.$thumb_name);
                //截图
                $square_cut_name = $image_name_show.'_square_cut.'.$image_name_type;
                new ImageProc($path.$image_name, $path.$square_cut_name, IMAGE_SQUARE_SIZE, IMAGE_SQUARE_SIZE, 1, 1);

                $res = $this->MSave(array(
                    'id' => $image_id,
                    'save_url' => $upload_url,
                    'save_name' => $image_name,
                    'thumb_name' => $thumb_name,
                    'square_cut_name' => $square_cut_name,
                    'status' => '1',
                ));
                if($res){
                    $data = array(
                        'id'=>$image_id,
                        'md5'=>$md5,
                        'save_url'=>$upload_url,
                        'save_name'=>$image_name,
                        'thumb_name'=>$thumb_name,
                        'square_cut_name'=>$square_cut_name,
                        'image_url'=>IMAGE_DOMAIN.$upload_url.$image_name,
                        'image_thumb_url'=>IMAGE_DOMAIN.$upload_url.$thumb_name,
                        'image_square_cut_url'=>IMAGE_DOMAIN.$upload_url.$square_cut_name
                    );
                    return $data;
                }else{
                    return false;
                }
            }else{
                unlink($tmp_path.$tmp_name.$type);
                $this->MExecute("DELETE FROM ".$this->_table." WHERE id={$image_id}");
                return false;
            }
        }
    }

    public function getUserUpload($handle_type){
        switch($handle_type){
            case 'system_school_image':
                $path = IMAGE_PATH.UPLOAD_PATH.'school/log/';
                $upload_url = UPLOAD_PATH.'school/log/';
                break;
            default:
                $tag = date('Ym');
                $path = IMAGE_PATH.IMAGE_UPLOAD_PATH.$tag.'/';
                $upload_url = IMAGE_UPLOAD_PATH.$tag.'/';
                break;
        }

        if (!is_dir($path)){
            mkdir ($path, 0777 ,true);
        }
        return array($path, $upload_url);
    }

    public function setCacheData($data){
        if(empty($data)){
            return false;
        }
        $key = uniqid().rand(100,999);
        IRedis::getInstance()->setEx('images_upload_'.$key, $data, 600);
        return $key;
    }

    public function getCacheData($key){
        if(empty($key)){
            return false;
        }
        return IRedis::getInstance()->get('images_upload_'.$key);
    }

    public function delCacheData($key){
        if(empty($key)){
            return false;
        }
        return IRedis::getInstance()->delete('images_upload_'.$key);
    }

}
