<?php
class WxSendSoftModel extends MBaseModel
{
    protected $_table = '{{wx_send_soft}}';
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

    public function rules(){
        return array(
            'title'  => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 60),
            ),
            'description'  => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 300),
            ),
            'url'  => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 300),
            ),
            'image_id'  => array(
                'empty' => array('type' => 'input'),
                'number' => array('msg'=>I18n::getInstance()->getErrorCommon('image_error')),
            ),
        );
    }

    public function adminEditSave($save_data){
        return $this->MSave($save_data);
    }

    public function adminSelectList($options,$limit=10){
        $current_page = (!empty($options['page']) && $options['page']>0) ? (int)$options['page'] : 1;
        $start = ($current_page - 1)*$limit;

        $where = array(
            "l.status='1'",
            "l.is_del='0'",
        );

        $where = implode(' AND ', $where);

        $sql = "SELECT l.id,l.title,l.description,l.image_id,l.mtime,CONCAT(i.save_url,i.save_name) AS image_url,url
                FROM $this->_table AS l
                LEFT JOIN {{image}} as i ON i.id=l.image_id
                WHERE {$where}
                LIMIT {$start},{$limit}";

        return $this->MFindAllBySql($sql);
    }

    public function getList($start,$limit=10,$keyword='',$is_all=0){
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

        $sql = "SELECT s.id,s.title,s.description,s.image_id,s.mtime,CONCAT(i.save_url,i.save_name) AS file_path,url
                FROM $this->_table AS s
                LEFT JOIN {{image}} as i ON i.id=s.image_id
                WHERE $where AND s.is_del='0' AND s.status='1'
                LIMIT $start,$limit";

        return $this->MFindAllBySql($sql);
    }

    public function getListByIds($ids){
        if(empty($ids)){
            return array();
        }

        $sql = "SELECT s.id,s.title,s.description,s.image_id,s.mtime,CONCAT(i.save_url,i.save_name) AS file_path,url
                FROM $this->_table AS s
                LEFT JOIN {{image}} as i ON i.id=s.image_id
                WHERE s.id IN ({$ids}) AND s.is_del='0' AND s.status='1'
                ORDER BY substring_index('{$ids}',s.id,1)";


        return $this->MFindAllBySql($sql);
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
            'field' => 'id,title,url,image_id,mtime',
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