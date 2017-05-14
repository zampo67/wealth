<?php
class LogScanTimeModel extends MBaseModel{
    protected $_table = '{{log_scan_time}}';
    protected $_public_id = '';


    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    //用户关注取消关注
    public function save($openid,$type='0'){
        //$info = UserModel::getInfoByOpenid($openid, 'id');
        WxUserModel::model()->setTableByPublicId($this->_public_id);
        $wx_user_info = WxUserModel::model()->getInfoByOpenid($openid,'id');
        $wx_user_id = !empty($wx_user_info['id']) ? $wx_user_info['id'] : 0;
        $data = array(
            'openid' => $openid,
            'type'  => $type,
            'wx_user_id' => $wx_user_id,
        );
        $res = $this->MSave($data, 1, 0);
        return $res;
    }

    //获取关注用户的数据,根据openid区分用户
    public function subLog($start,$end,$time_id,$type='1'){
        if($time_id==3){
            $sql = "SELECT distinct openid,FROM_UNIXTIME(ctime,'%Y-%m') AS ctime
                FROM ".$this->_table." WHERE ctime>=$start AND ctime<$end AND type='{$type}'";
        }else if($time_id==4){
            $sql = "SELECT distinct openid,FROM_UNIXTIME(ctime,'%k') AS ctime
                FROM ".$this->_table." WHERE ctime>=$start AND ctime<$end AND type='{$type}'";
        }else{
            $sql = "SELECT distinct openid,FROM_UNIXTIME(ctime,'%Y-%m-%d') AS ctime
                FROM ".$this->_table." WHERE ctime>=$start AND ctime<$end AND type='{$type}'";
        }

        return Db::getInstance()->executeS($sql);
    }

    public function setTableByPublicId($public_id){
        if(isset($public_id) && is_numeric($public_id)){
            $this->setTable(SqlTemplate::getTableName('log_scan_time', array('public_id'=>$public_id)));
            $this->createTable($public_id);
            $this->_public_id = $public_id;
        }
    }

    public function createTable($public_id){
        return $this->MExecute(SqlTemplate::getCreateTableSql('log_scan_time', array('public_id'=>$public_id)));
    }

    public function setPublicId($public_id){
        if(!empty($public_id) && is_numeric($public_id)){
            $this->_public_id = $public_id;
        }
    }
}