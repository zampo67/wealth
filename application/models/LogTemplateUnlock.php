<?php
class LogTemplateUnlockModel extends MBaseModel{
    protected $_table = '{{log_template_unlock}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    public function MFind($options = array(), $status = 1){
        return parent::MFind($options, $status, 0);
    }

    public function MSave($data, $ctime = 1){
        return parent::MSave($data, $ctime, 0);
    }

    //用户解锁
    public function unlock($data=array()){
        if(empty($data['resume_id'])){
            return false;
        }
        $max_acivity_id = ResumeModel::model()->getTemplateMaxActivityId();

        $type = (empty($data['type'])) ? '0' : '1';
        if(!empty($data['acivity_id']) && $data['acivity_id']<=$max_acivity_id){
            $max_acivity_id = $data['acivity_id'];
        }

        $self_is_unlock = $this->MFind(array('field'=>'id','where'=>"activity_id={$max_acivity_id} AND resume_id={$data['resume_id']}"), 0);

        if($self_is_unlock){
            return true;
        }

        $self_unlock_data = array(
            'activity_id' => $max_acivity_id,
            'code_id' => 0,
            'resume_id' => $data['resume_id'],
            'type' => $type,
            'status' => ($data['status']==2) ? '2' : '1',
        );
        return $this->MSave($self_unlock_data);
    }
}