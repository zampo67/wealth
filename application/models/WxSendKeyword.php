<?php
class WxSendKeywordModel extends MBaseModel
{
    protected $_table = '{{wx_send_keyword}}';
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
            'keyword' => array(
                'label' => '关键词',
                'empty' => '请填写关键词',
            ),
            'keyword_type' => array(
                'label' => '关键词类型',
                'in_array' => array('1','2','3','4'),
            ),
            'source_type' => array(
                'label' => '回复类型',
                'in_array' => array('1','2','3'),
            ),
            'content' => array(
                'label' => '回复内容',
                'empty' => '回复内容不能为空',
            ),
        );
    }

    public function is_exist($keyword,$type){
        $options = array(
            'field' => 'id',
            'where' => array(
                'keyword' => $keyword,
                'keyword_type' => $type,
            ),
        );

        $res = self::model()->MFind($options);

        if($res){
            return $res;
        }

        return false;
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

    public function getSend($content){
        if(empty($content)){
            return array();
        }

        $data = $this->getDataByKeyword($content);

        if(empty($data)){
            return array();
        }

        $ids = implode(',',array_column($data,'id'));
        $this->MPlusField('count',array('id'=>$ids));

        $return_data = array();
        foreach($data as $k=>$d) {
            switch ($d['source_type']) {
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

    public function getDataByKeyword($content){
        //完全匹配
        $options = array(
            'field' => 'id,source_type,content',
            'where' =>array(
                'public_id' => $this->_public_id,
                'keyword' => $content,
                'keyword_type' => 1,
            ),
        );

        $data = self::model()->MFindAll($options);

        if($data){
            return $data;
        }

        $sql = "SELECT id,source_type,content,keyword,keyword_type FROM $this->_table WHERE public_id=$this->_public_id AND keyword_type!=1 AND is_del='0' AND status='1' ORDER BY keyword_type";

        $data = self::model()->MFindAllBySql($sql);

        $return_data = array();

        if(!empty($data)){
            foreach($data as $d){
                if (preg_match ( '('.$d['keyword'].')', $content )) {
                    if($d['keyword_type'] == 4){
                         $return_data[] = $d;
                    }else{
                        if ($d ['keyword'] != $content) {
                            $arr = explode ( $d ['keyword'], $content );
                                // 左边匹配
                            if ($d ['keyword_type'] == 2 &&  empty ( $arr [0] ))
                                $return_data[] = $d;

                                // 右边 匹配
                            if ($d ['keyword_type'] == 3 &&  empty ( $arr [1] ))
                                $return_data[] = $d;
                        }
                    }
                }
            }
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

        $where = "public_id=$this->_public_id";
        $where .= (!empty($keyword)) ? " AND keyword like '%{$keyword}%'" : '';

        $sql = "SELECT SQL_CALC_FOUND_ROWS id,keyword,keyword_type,source_type,content,`count`,mtime
                FROM $this->_table
                WHERE $where AND is_del='0' AND status='1'
                ORDER by mtime DESC
                LIMIT $start,$limit";

        return self::model()->MFindAllBySql($sql);
    }

    //关键词类型
    public function keywordTypes($key=''){
        $labels= array(1=>'完全匹配',2=>'左边匹配',3=>'右边匹配',4=>'模糊匹配');

        if(!empty($labels[$key])){
            return $labels[$key];
        }else{
            return $labels;
        }
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