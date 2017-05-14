<?php
class WxSendQrcodeModel extends MBaseModel
{
    protected $_table = '{{wx_send_qrcode}}';
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

    public function rules()
    {
        return array(
            'source_type' => array(
                'label' => '回复类型',
                'in_array' => array('1', '2', '3'),
            ),
            'content' => array(
                'label' => '回复内容',
                'empty' => '回复内容不能为空',
            ),
        );
    }

    public function getInfoById($id){
        $data = self::model()->MFind($id);
        if(!empty($data['content']) && $data['source_type'] != 3){
            $source_data = WxSendSourceModel::model()->getInfoByMediaId($data['content'],$data['source_type']);
            switch($data['source_type']){
                case 1:
                    $data['soft_list'] = (!empty($source_data)) ? $source_data : array();
                    break;
                case 2:
                    $data['file_path'] = (!empty($source_data['file_path'])) ? $source_data['file_path'] : '';
                    break;
            }
        }

        return $data;
    }

    public function getSend($qrcode_id){
        if(empty($qrcode_id)){
            return array();
        }
        $options = array(
            'field' => 'source_type,content',
            'where' =>array(
                'public_id' => $this->_public_id,
                'qrcode_id' => $qrcode_id,
            ),
        );

        $data = self::model()->MFindAll($options);

        if(empty($data)){
            return array();
        }
        $return_data = array();
        foreach($data as $k=>$d){
            switch($d['source_type']){
                case 1:
                    $return_data[$k]['send_data'] = WxSendSoftModel::model()->getListByIds($d['content']);
                    break;
                case 2:
                case 3:
                    $return_data[$k]['send_data'] = $d['content'];
                    break;

            }
            $return_data[$k]['source_type'] = $d['source_type'];
        }

        return $return_data;
    }

    public function getList($start,$limit=10,$keyword=''){
        if($start>0){
            $start --;
        }else{
            $start = 0;
        }
        $start = $start*$limit;

        $where = "s.public_id=$this->_public_id";
        $where .= (!empty($keyword)) ? " AND s.title like '%{$keyword}%'" : '';

        $sql = "SELECT SQL_CALC_FOUND_ROWS s.id,s.qrcode_id,s.title,s.source_type,s.content,s.mtime,q.ticket
                FROM $this->_table AS s
                LEFT JOIN {{wx_qrcode}} q ON q.id=s.qrcode_id
                WHERE $where AND s.is_del='0' AND s.status='1'
                ORDER by s.mtime DESC
                LIMIT $start,$limit";

        return self::model()->MFindAllBySql($sql);
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