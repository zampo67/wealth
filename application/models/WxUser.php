<?php

class WxUserModel extends MBaseModel{
    protected $_table = '{{wx_user}}';
    protected $_public_id = 1;

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
        if(isset($public_id) && is_numeric($public_id) && $public_id>1){
            $this->setTable(SqlTemplate::getTableName('wx_user', array('public_id'=>$public_id)));
            $this->createTable($public_id);
            $this->_public_id = $public_id;
        }
    }

    public function createTable($public_id){
        return $this->MExecute(SqlTemplate::getCreateTableSql('wx_user', array('public_id'=>$public_id)));
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

    /**
     * 根据openid获取用户微信信息
     * @param string $openid openid
     * @param string $field 字段
     * @return mixed
     */
    public function getInfoByOpenid($openid, $field=''){
        return $this->MFind(array(
            'field' => empty($field) ? '*' : $field,
            'where' => array('openid' => $openid)
        ));
    }

    /**
     * 根据unionid获取用户微信信息
     * @param string $unionid unionid
     * @param string $field 字段
     * @return mixed
     */
    public function getInfoByUnionid($unionid, $field=''){
        return $this->MFind(array(
            'field' => empty($field) ? '*' : $field,
            'where' => array('unionid' => $unionid)
        ));
    }

    /**
     * 处理微信用户信息接口返回的数据
     * @param array $data 用户信息
     * @return array
     */
    public function handleWeixinUserInfo($data){
        return ($data['subscribe']=='1') ? array(
            'openid' => $data['openid'],
            'sex' => $data['sex'],
            'nickname' => $data['nickname'],
            'province' => $data['province'],
            'city' => $data['city'],
            'country' => $data['country'],
            'language' => $data['language'],
            'headimgurl' => $data['headimgurl'],
            'is_subscribe' => $data['subscribe'],
            'subscribe_time' => $data['subscribe_time'],
            'unionid' => $data['unionid'],
        ) : array(
            'openid' => $data['openid'],
            'unionid' => $data['unionid'],
            'is_subscribe' => $data['subscribe'],
        );
    }

    /**
     * 根据openid保存用户数据
     * @param string $openid openid
     * @return bool|int
     */
    public function saveByOpenid($openid){
        if(empty($openid)){
            return false;
        }

        // 从微信接口获取用户信息
        $user_wx = IWeiXin::getInstance($this->_public_id)->getUserInfo($openid, true);
        if(empty($user_wx['openid']) || empty($user_wx['unionid'])){
            return false;
        }

        // 处理微信返回的用户信息
        $save_data = $this->handleWeixinUserInfo($user_wx);

        // 检查用户是否已经存在,不存在则新增,存在则更新
        $user_exist = $this->getInfoByUnionid($user_wx['unionid'], 'id');
        if(!empty($user_exist)){
            $save_data['id'] = $user_exist['id'];
            $this->MSave($save_data);
        }else{
            $save_data['id'] = $this->MSave($save_data);
        }
        return $save_data;
    }

}