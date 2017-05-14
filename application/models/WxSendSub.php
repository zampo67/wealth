<?php
class WxSendSubModel extends MBaseModel
{
    protected $_table = '{{wx_send_sub}}';
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
            'title' => array(
                'label' => '描述词',
                'empty' => '请填写描述词',
            ),
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

    public function getSub(){
        $options = array(
            'field' => 'id,title,source_type,content,mtime',
            'where' => array(
                'public_id' => $this->_public_id,
            ),
        );

        return self::model()->MFind($options);
    }

    public function getSend(){
        $options = array(
            'field' => 'source_type,content',
            'where' => array(
                'public_id' => $this->_public_id,
            ),
        );

        $data = self::model()->MFind($options);

        if(empty($data)){
            return array();
        }
        $return_data = array();
        switch($data['source_type']){
            case 1:
                $return_data['send_data'] = WxSendSoftModel::model()->getListByIds($data['content']);
                break;
            case 2:
            case 3:
                $return_data['send_data'] = $data['content'];
                break;

        }
        $return_data['source_type'] = $data['source_type'];
        return $return_data;
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