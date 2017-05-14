<?php

class WxMenuModel extends MBaseModel {
    protected $_table = '{{wx_menu}}';
    protected $_public_id = '';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function getSend($key){
        if(empty($key)){
            return array();
        }
        $sql = "SELECT source_type,content FROM {$this->_table}
                WHERE `key`='{$key}' AND public_id={$this->_public_id} AND is_del='0' AND status='1'";
        $data = self::model()->MFindBySql($sql);

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

    public function rules(){
        return array(
            'name'  => array(
                'filter_func' => array('trim'),
                'empty' => array('type' => 'input'),
                'max_length' => array('length' => 12),
            ),
            'type' => array(
                'empty' => array('type' => 'select'),
                'in_array' => array('array'=>Common::arrayColumnToKey(VariablesModel::model()->getList('menuType'),'','id'))
            )
        );
    }

    public function adminEditDelete($id){
        return $this->MDel($id);
    }

    public function adminGetCount($pid){
        $options = array(
            'field' => 'COUNT(1) AS num',
            'where' => array(
                'pid' => $pid
            ),
        );

        $data = self::model()->MFind($options);

        return $data['num'];
    }

    public function adminEditSave($save_data){
        return $this->MSave($save_data);
    }

    public function getList(){
        $sql = "SELECT t1.type,t1.name,t1.key,t1.content AS url"
                . ",(SELECT GROUP_CONCAT("
                . "CONCAT(t2.type,'".DB_CONCAT_SEP."',t2.name,'".DB_CONCAT_SEP."',t2.key,'".DB_CONCAT_SEP."',t2.content)"
                . " ORDER BY t2.sort ASC SEPARATOR '".DB_GROUP_CONCAT_SEP."')"
                . " FROM ".$this->_table." AS t2"
                . " WHERE t2.public_id={$this->_public_id} AND t2.status='1' AND t2.is_del='0' AND t2.pid=t1.id"
                . ") AS sub_info"
                . " FROM ".$this->_table." AS t1"
                . " WHERE t1.public_id={$this->_public_id} AND t1.status='1' AND t1.is_del='0' AND t1.pid=0"
                . " ORDER BY t1.sort ASC ";
        $list = $this->MFindAllBySql($sql);

        foreach ($list as &$r){
            if(!preg_match('#http://#', $r['url']) && !preg_match('#https://#', $r['url'])){
                $r['url'] = DOMAIN.$r['url'];
            }
            if(!empty($r['sub_info'])){
                $r['sub_button'] = array();
                $group = explode(DB_GROUP_CONCAT_SEP, $r['sub_info']);
                foreach ($group as $g){
                    $sub = explode(DB_CONCAT_SEP, $g);
                    $temp = array();
                    $temp['type'] = isset($sub[0]) ? $sub[0] : '';
                    $temp['name'] = isset($sub[1]) ? $sub[1] : '';
                    switch($temp['type']){
                        case 'view':
                            $temp['url'] = isset($sub[3]) ? $sub[3] : '';
                            if(!preg_match('#http://#', $temp['url']) && !preg_match('#https://#', $temp['url'])){
                                $temp['url'] = DOMAIN.$temp['url'];
                            }
                            break;
                        case 'click':
                            $temp['key'] = isset($sub[2]) ? $sub[2] : '';
                            break;
                    }
                    $r['sub_button'][] = $temp;
                }
            }
            unset($r['sub_info']);
        }
        return $list;
    }

    public function adminEditItem($id,$public_id){
        $info =  $this->MFind(array(
            'field' => 'id,pid,name,type,content,source_type',
            'where' => array(
                'id' => $id,
                'public_id' => $public_id
            )
        ));

        if(!empty($info) && $info['source_type']>0){
            switch($info['source_type']){
                case 1:
                case 2:
                    $info['source_data'] = WxSendSourceModel::model()->getInfoByMediaId($info['content'],$info['source_type']);
                    break;
            }
        }

        return $info;
    }

    public function adminGetParents($public_id){
        return $this->MFindAll(array(
            'field' => 'id,name',
            'where' => array(
                'pid' => 0,
                'public_id' => $public_id
            )
        ));
    }

    public function adminEditList(){
        $sql = "SELECT t1.id,t1.type,t1.name,t1.key,t1.content AS url"
            . ",(SELECT GROUP_CONCAT("
            . "CONCAT(t2.id,'".DB_CONCAT_SEP."',t2.type,'".DB_CONCAT_SEP."',t2.name,'".DB_CONCAT_SEP."',t2.key,'".DB_CONCAT_SEP."',t2.content)"
            . " ORDER BY t2.sort ASC SEPARATOR '".DB_GROUP_CONCAT_SEP."')"
            . " FROM ".$this->_table." AS t2"
            . " WHERE t2.public_id={$this->_public_id} AND t2.status='1' AND t2.is_del='0' AND t2.pid=t1.id"
            . ") AS sub_info"
            . " FROM ".$this->_table." AS t1"
            . " WHERE t1.public_id={$this->_public_id} AND t1.status='1' AND t1.is_del='0' AND t1.pid=0"
            . " ORDER BY t1.sort ASC ";
        $list = $this->MFindAllBySql($sql);

        foreach ($list as &$r){
            if(!preg_match('#http://#', $r['url']) && !preg_match('#https://#', $r['url'])){
                $r['url'] = DOMAIN.$r['url'];
            }
            $r['sub_button'] = array(
                'list' => array(),
                'num' => 0
            );
            if(!empty($r['sub_info'])){
                $group = explode(DB_GROUP_CONCAT_SEP, $r['sub_info']);
                foreach ($group as $g){
                    $sub = explode(DB_CONCAT_SEP, $g);
                    $temp = array();
                    $temp['id'] = isset($sub[0]) ? $sub[0] : '';
                    $temp['type'] = isset($sub[1]) ? $sub[1] : '';
                    $temp['name'] = isset($sub[2]) ? $sub[2] : '';
                    switch($temp['type']){
                        case 'view':
                            $temp['url'] = isset($sub[4]) ? $sub[4] : '';
                            if(!preg_match('#http://#', $temp['url']) && !preg_match('#https://#', $temp['url'])){
                                $temp['url'] = DOMAIN.$temp['url'];
                            }
                            break;
                        case 'click':
                            $temp['key'] = isset($sub[3]) ? $sub[3] : '';
                            break;
                    }
                    $r['sub_button']['list'][] = $temp;
                }
                $r['sub_button']['num'] = count($r['sub_button']['list']);
            }
            unset($r['sub_info']);
        }
        return $list;
    }

    public function setPublicId($public_id){
        $this->_public_id = $public_id;
    }
}
