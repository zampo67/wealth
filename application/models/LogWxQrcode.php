<?php
class LogWxQrcodeModel extends MBaseModel{
    protected $_table = '{{log_wx_qrcode}}';
    protected $_public_id = '';
    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    //用户关注来源 1关注 2 关注用户扫描
    public function save($openid, $qrcode_id, $type_id=1){
        //$info = UserModel::model()->getInfoByOpenid($openid, 'id');
        WxUserModel::model()->setTableByPublicId($this->_public_id);
        $wx_user_info = WxUserModel::model()->getInfoByOpenid($openid,'id');
        $wx_user_id = !empty($wx_user_info['id']) ? $wx_user_info['id'] : 0;
        $data = array(
            'wx_user_id' => $wx_user_id,
            'openid' => $openid,
            'qrcode_id'  => $qrcode_id,
            'type_id'  => $type_id,
            'public_id' => $this->_public_id,
        );
        $res = self::model()->MSave($data, 1, 0);
        return $res;
    }

    //获取当天的扫描数据
    public function dayList($ids_str=''){
        if(empty($ids_str)) return false;

        $start = mktime(0,0,0);

        $sql = "SELECT qrcode_id,COUNT(*) AS num FROM ".$this->_table." where ctime >= $start AND qrcode_id IN ($ids_str) GROUP BY qrcode_id";

        return Db::getInstance()->executeS($sql);
    }

    //获取单个二维码的扫描量
    public function getList($qrcode_id,$start_time,$end_time,$type='%Y-%m-%d'){
        $sql = "SELECT FROM_UNIXTIME(ctime,'{$type}') AS tag,count(1) AS num
                FROM ".$this->_table."
                WHERE qrcode_id=$qrcode_id AND ctime>$start_time AND ctime<=$end_time
                GROUP BY tag";

        return Db::getInstance()->executeS($sql);
    }

    public function setTableByPublicId($public_id){
        if(isset($public_id) && is_numeric($public_id)){
            $this->setTable(SqlTemplate::getTableName('log_wx_qrcode', array('public_id'=>$public_id)));
            $this->createTable($public_id);
            $this->_public_id = $public_id;
        }
    }

    public function createTable($public_id){
        return $this->MExecute(SqlTemplate::getCreateTableSql('log_wx_qrcode', array('public_id'=>$public_id)));
    }
}