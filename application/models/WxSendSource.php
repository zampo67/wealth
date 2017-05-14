<?php
class WxSendSourceModel extends MBaseModel
{
    protected $_table = '{{wx_send_source}}';
    protected $_table_image = '{{image}}';
    protected $_public_id = '';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function adminSelectList($options,$limit=10){
        $current_page = (!empty($options['page']) && $options['page']>0) ? (int)$options['page'] : 1;
        $start = ($current_page - 1)*$limit;

        $where = array(
            "l.status='1'",
            "l.is_del='0'",
        );

        $where = implode(' AND ', $where);

        $sql = "SELECT l.image_id,CONCAT(i.save_url,i.save_name) AS image_url
                FROM $this->_table AS l
                LEFT JOIN {$this->_table_image} as i ON i.id=l.image_id
                WHERE $where AND l.is_del='0' AND l.status='1'
                GROUP BY l.image_id
                LIMIT $start,$limit";

        return $this->MFindAllBySql($sql);
    }

    public function getImageList($start,$limit=10,$keyword='',$is_all=0){

        if($start>0){
            $start --;
        }else{
            $start = 0;
        }
        $start = $start*$limit;

        $where = '1=1';
        if($is_all==0){
            $where = "s.public_id=$this->_public_id";
        }
        $where .= (!empty($keyword)) ? " AND s.title like '%{$keyword}%'" : '';

        $sql = "SELECT s.id,s.title,s.image_id,s.media_id,s.mtime,CONCAT('".IMAGE_DOMAIN."',i.save_url,i.save_name) AS file_path
                FROM $this->_table AS s
                LEFT JOIN {{image}} as i ON i.id=s.image_id
                WHERE $where AND s.is_del='0' AND s.status='1'
                LIMIT $start,$limit";


        return self::model()->MFindAllBySql($sql);
    }

    public function getInfoByMediaId($media_id,$type=2){
        if(empty($media_id) || empty($type)){
            return array();
        }
        switch($type){
            case 1:
                return WxSendSoftModel::model()->getListByIds($media_id);
                break;
            case 2:
                $sql = "SELECT CONCAT('".IMAGE_DOMAIN."',i.save_url,i.save_name) AS file_path
                                FROM $this->_table AS s
                                LEFT JOIN {{image}} AS i ON i.id=s.image_id
                                WHERE media_id='{$media_id}'";
                break;
        }


        return self::model()->MFindBySql($sql);

    }

    public function getEditList($options){
        $start = !empty($options['start']) ? $options['start'] : 0;
        $limit = !empty($options['limit']) ? $options['limit'] : 10;
        $all_num = !empty($options['all_num']) ? 1 : 0;
        if($start>0){
            $start--;
        }else{
            $start = 0;
        }
        $start = $start*$limit;

        $option = array(
            'field' => 'id,title,image_id,mtime',
            'order' => 'id DESC',
            'limit' => "{$start},{$limit}",
            'where' =>array(
                'public_id' => $this->_public_id
            ),
        );

        if(!empty($options['where'])){
            foreach($options['where'] as $key=>$val){
                switch($key){
                    case 'title':
                        $option['where']['title'] = "%{$val}%";
                        break;
                }
            }
        }

        return $this->MFindAll($option,$all_num);
    }

    public function editSave($save_data){
        $save_data['public_id'] = $this->_public_id;
        return $this->MSave($save_data);
    }

    public function setPublicId($public_id){
        if(!empty($public_id) && is_numeric($public_id)){
            $this->_public_id = $public_id;
        }
    }
}