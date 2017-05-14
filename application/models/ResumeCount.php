<?php
class ResumeCountModel extends MBaseModel {
    protected $_table = '{{resume_count}}';

    /**
     * 实例化Model
     * @param string $className
     * @return mixed
     */
    public static function model($className=__CLASS__){
        return parent::model($className);
    }

    public function MSave($data, $mtime = 1){
        return parent::MSave($data, 0, $mtime);
    }

    /**
     * 获取用户简历完成度信息
     * @param int $resume_id 简历ID
     * @return bool|array
     */
    public function getResumeCompetitiveness($resume_id){
//        基本信息	 5
//        教育背景	 15
//        头像	     5
//        求职意向	 10
//        工作经历	 20
//        职业技能	 10
//        在校经历	 10
//        个人介绍	 10
//        项目	     5
//        资格证书	 5
//        兴趣爱好	 5
        $data = IRedis::getInstance()->hGet('resume_competitiveness', 'id_'.$resume_id);
        if(empty($data)){
            //检查简历是否存在
            $resume_info = ResumeModel::model()->MFind(array(
                'field' => 'id,user_id,headimg_id,introduction,finish_time',
                'where' => array('id' => $resume_id)
            ));
            if(empty($resume_info['finish_time'])){
                return false;
            }

            //检查完成度记录是否存在,不存在则创建,存在则更新
            $check = $this->MFind(array(
                'field' => 'id',
                'where' => array('resume_id' => $resume_id),
            ));
            if(!empty($check)){
                $save_data = array(
                    'id' => $check['id'],
                );
            }else{
                $save_data = array(
                    'user_id' => $resume_info['user_id'],
                    'resume_id' => $resume_id,
                );
            }

            //教育经历和工作经历总和
            $save_data['edu_experience'] = 0;
            $save_data['work'] = 0;

            //教育经历分类
            $edu_experience_count = ResumeEduExperienceModel::model()->getGroupCountForTypeIdByResumeId($resume_id);
            $edu_experience_count = Common::arrayColumnToKey($edu_experience_count, 'type_id');
            $edu_experience_type_ids = array(1,2,4,5,6,7);
            foreach ($edu_experience_type_ids as $edu_experience_type_id){
                $edu_experience_num = isset($edu_experience_count[$edu_experience_type_id]['num']) ? $edu_experience_count[$edu_experience_type_id]['num'] : 0;
                $save_data['edu_experience_'.$edu_experience_type_id] = $edu_experience_num;
                if($edu_experience_type_id != 4){
                    $save_data['edu_experience'] += $edu_experience_num;
                }else{
                    $save_data['work'] += $edu_experience_num;
                }
            }

            //工作经历分类
            $work_count = ResumeWorkModel::model()->getGroupCountForTypeIdByResumeId($resume_id);
            $work_count = Common::arrayColumnToKey($work_count, 'type_id');
            $work_type_ids = array(1,2,3);
            foreach ($work_type_ids as $work_type_id){
                $work_num = isset($work_count[$work_type_id]['num']) ? $work_count[$work_type_id]['num'] : 0;
                $save_data['work_'.$work_type_id] = $work_num;
                $save_data['work'] += $work_num;
            }

            //头像
            $save_data['headimg'] = !empty($resume_info['headimg_id']) ? '1' : '0';
            //求职意向
            $target_position_count = ResumeTargetPositionModel::model()->getCountByResumeId($resume_id);
            $save_data['target'] = !empty($target_position_count) ? '1' : '0';
            //个人介绍
            $save_data['introduction'] = !empty($resume_info['introduction']) ? '1' : '0';
            //项目
            $save_data['project'] = ResumeWorkProjectModel::model()->getCountByResumeId($resume_id);
            //证书
            $save_data['certificate'] = ResumeCertificateModel::model()->getCountByResumeId($resume_id);
            //技能
            $save_data['skill'] = ResumeSkillModel::model()->getCountByResumeId($resume_id);
            //爱好
            $save_data['hobby'] = ResumeHobbyModel::model()->getCountByResumeId($resume_id);

            //最高学历教育
            $top_degree_edu_info = ResumeEduModel::model()->getTopDegreeEduInfoByResumeId($resume_id);
            if(!empty($top_degree_edu_info)){
                $save_data['degree_id'] = $top_degree_edu_info['degree_id'];
                $save_data['start_time'] = $top_degree_edu_info['start_time'];
                $save_data['end_time'] = $top_degree_edu_info['end_time'];
            }

            //简历完成度
            $percent = 20;
            if(!empty($save_data['headimg'])){
                $percent += 5;
            }
            if(!empty($save_data['target'])){
                $percent += 10;
            }
            if(!empty($save_data['work'])){
                $percent += 20;
            }
            if(!empty($save_data['skill'])){
                $percent += 10;
            }
            if(!empty($save_data['edu_experience'])){
                $percent += 10;
            }
            if(!empty($save_data['introduction'])){
                $percent += 10;
            }
            if(!empty($save_data['project'])){
                $percent += 5;
            }
            if(!empty($save_data['certificate'])){
                $percent += 5;
            }
            if(!empty($save_data['hobby'])){
                $percent += 5;
            }
            $data = array('percent' => $percent);
            IRedis::getInstance()->hSet('resume_competitiveness', 'id_'.$resume_id, $data);

            //保存简历完成度
            $save_data['percent'] = $percent;
            $this->MSave($save_data);
        }
        return $data;
    }
    
}