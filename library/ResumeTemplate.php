<?php
class ResumeTemplate
{
    public  $default_headimgurl = '/static/images/common/web_headimg_default.jpg';
    protected $resume_id,$template_id;
    protected $i18n_id = 1;
    protected $resume_info = array();
    protected $resume_practice_type_id = 4;
    protected $resume_practice_list = array();
    protected $template_etc = array(
        'global_list_time' => array(                             // 设置全局列表时间展示
            array(
                'ids' => array(0),
                'format' => 'Y.m'
            ),
            array(
                'ids' => array(1,2,3,5,12),
                'format' => 'Y.m'
            )
        ),
        'work' => array(
            array(
                'ids' => array(0),
                'format' => 'list_work'
            ),
            array(
                'ids' => array(3),
                'format' => 'classify_work_and_project_time'
            ),
            array(
                'ids' => array(1,2,4),
                'format' => 'classify_work_and_project'
            ),
            array(
                'ids' => array(5,6,10,12,14,15,16),
                'format' => 'list_work_and_project'
            )
        ),
        'edu_experience' => array(                              // 设置在校经历格式
            array(
                'ids' => array(0),
                'format' => 'list_edu_experience'
            ),
            array(
                'ids' => array(1,12,14),
                'format' => 'classify_edu_experience'
            )
        ),                 
        'project' => array(                                     // 设置需要独立展示的项目经验
            array(
                'ids' => array(0),
                'format' => false
            ),
            array(
                'ids' => array(10,12,14,19),
                'format' => true
            )
        ),                       
        'head_img_url' => array(                                // 设置头像需要原图的简历模版id
            array(
                'ids' => array(0),
                'format' => false
            ),
            array(
                'ids' => array(8,9,10,14),
                'format' => true
            )
        ),                 
        'birth_time' => array(   
            array(
                'ids' => array(0),
                'format' => 'age'
            ),
            array(
                'ids' => array(2,3,4,7,8),
                'format' => 'Y.m.d'
            ),
            array(
                'ids' => array(15,19),
                'format' => 'Y-m-d'
            )
        ),
    );
    public function __construct($resume_id,$template_id='') {
        $this->resume_id = $resume_id;
        $this->template_id = $template_id;
    }

    protected function handleEtc($data){
        $res = array();
        foreach($data as $val){
            foreach($val['ids'] as $key){
                $res[$key] = $val['format'];
            }
        }
        return $res;
    }

    public function getEtc($module){
        $etc_data = $this->handleEtc($this->template_etc[$module]);
        return !empty($etc_data[$this->template_id]) ? $etc_data[$this->template_id] : $etc_data[0];
    }

    public function setGlobalEtc(){
        //设置全局列表时间          
        $this->global_list_time = $this->getEtc('global_list_time');
    }

    public function getTemplateData(){
        $resume_data = array();
        $this->setResumeInfo();

        if(!empty($this->resume_info)){
            // 简历语言检查
            $this->i18n_id = $this->resume_info['i18n_id'];
            VariablesModel::model()->setI18nById($this->i18n_id);
            //模版全局设置
            $this->setGlobalEtc();
            //简历基本信息
            $resume_data['base']['item'] = $this->getBaseItem();
            //简历求职意向
            $resume_data['target']['item'] = $this->getTargetItem();
            //简历教育背景列表
            $resume_data['edu']['list'] = $this->getEdu();
            $resume_data['edu']['num'] = count($resume_data['edu']['list']);
            //简历技能列表
            $resume_data['skill']['list'] = $this->getSkill();
            $resume_data['skill']['num'] = count($resume_data['skill']['list']);
            //简历爱好列表
            $resume_data['hobby']['list'] = $this->getHobby();
            $resume_data['hobby']['num'] = count($resume_data['hobby']['list']);
            //简历资格证书列表
            $resume_data['certificate']['list'] = $this->getCertificate();
            $resume_data['certificate']['num'] = count($resume_data['certificate']['list']);

            //简历在校经历列表
            $resume_data['edu_experience'] = $this->getEduExperience();

            //简历项目经历列表
            if($this->getEtc('project')){
                $resume_data['project'] = $this->getProject();
            }

            //简历工作列表
            $resume_data['work'] = $this->getWork();

            $resume_data['etc']['item'] = array(
                'i18n_id' => $this->i18n_id
            );
        }

        return $resume_data;
    }

    private function handleListEndTime($timestamp,$module=''){
        return ($timestamp==-1) ? I18n::getInstance()->getOther('template_'.$module.'end_time') : date($this->global_list_time,$timestamp);
    }

    private function handleListStartTime($timestamp){
        return date($this->global_list_time,$timestamp);
    }

    /**
     * 设置简历主体信息
     */
    private function setResumeInfo(){
        $this->resume_info = ResumeModel::model()->MFind(array(
            'field' => 'username,sex,birth_time,introduction,nationality,marital_status,political_status'
                .',weight,origin_prov_name,origin_city_name,location_prov_name,location_city_name'
                .',height,origin_prov_id,origin_city_id,location_prov_id,location_city_id'
                .',headimg_id,headimgurl,personal_links,mobile,email,wechat,qq,target_salary,i18n_id',
            'where' => array(
                'id' => $this->resume_id
            )
        ));
    }

    private function handleList($list,$format){
        $data = array(
            'list' => array(),
            'num' => 0
        );
        switch($format){
            case 'classify_edu_experience':                     // 对在校经历进行分类展示，并且得到社会实践的数据
                $edu_experience_type = VariablesModel::model()->getAttrById('eduExperienceType','name');
                $data = array(
                    5 => array(
                        'label' => $edu_experience_type[5],
                        'type_id' => 5,
                        'list' => array(),
                        'num' => 0
                    ),
                    6 => array(
                        'label' => $edu_experience_type[6],
                        'type_id' => 6,
                        'list' => array(),
                        'num' => 0
                    ),
                    1 => array(
                        'label' => $edu_experience_type[1],
                        'type_id' => 1,
                        'list' => array(),
                        'num' => 0
                    ),
                    7 => array(
                        'label' => $edu_experience_type[7],
                        'type_id' => 7,
                        'list' => array(),
                        'num' => 0
                    ),
                    2 => array(
                        'label' => $edu_experience_type[2],
                        'type_id' => 2,
                        'list' => array(),
                        'num' => 0
                    )
                );
                if(!empty($list)) {
                    foreach ($list as $l) {
                        if (!empty($data[$l['type_id']])) {
                            $others = !empty($l['others']) ? unserialize($l['others']) : array();
                            $data[$l['type_id']]['list'][] = array(
                                'title' => $l['title'],
                                'description' => $l['description'],
                                'position_name' => !empty($others['position']) ? $others['position'] : '',
                                'start_time' => $this->handleListStartTime($l['start_time']),
                                'end_time' => $this->handleListEndTime($l['end_time'])
                            );
                            $data[$l['type_id']]['num']++;
                        } else if ($l['type_id'] == $this->resume_practice_type_id) {
                            $l['position_name'] = '';
                            $l['logo_url'] = '';
                            $this->resume_practice_list[] = $l;
                        }
                    }
                }
                $data = array_values($data);
                break;
            case 'list_edu_experience':                         // 默认在校经历展示
                if(!empty($list)) {
                    $edu_experience_type = VariablesModel::model()->getAttrById('eduExperienceType', 'name');
                    foreach($list as $l){
                        if (!empty($edu_experience_type[$l['type_id']])) {
                            $others = !empty($l['others']) ? unserialize($l['others']) : array();
                            $data['list'][] = array(
                                'title' => $l['title'],
                                'description' => $l['description'],
                                'position_name' => !empty($others['position']) ? $others['position'] : '',
                                'start_time' => $this->handleListStartTime($l['start_time']),
                                'end_time' => $this->handleListEndTime($l['end_time']),
                                'label' => $edu_experience_type[$l['type_id']]
                            );
                            $data['num']++;
                        } else if ($l['type_id'] == $this->resume_practice_type_id) {
                            $l['position_name'] = '';
                            $l['logo_url'] = '';
                            $this->resume_practice_list[] = $l;
                        }
                    }
                }
                break;
            case 'list_work':
                $practice_list = $this->resume_practice_list;
                $list = array_merge($list,$practice_list);
                if(!empty($list)){
                    $list = Common::arraySort($list,'start_time','desc');
                    $work_type = VariablesModel::model()->getAttrById('workTypeResume', 'name');
                    foreach($list as &$l){
                        $l['logo_url'] = !empty($l['logo_url']) ? IMAGE_DOMAIN.$l['logo_url'] : '';
                        $l['start_time'] = $this->handleListStartTime($l['start_time']);
                        $l['end_time'] = $this->handleListEndTime($l['end_time']);
                        $l['label'] = $work_type[$l['type_id']];
                    }
                    unset($l);
                    $data['list'] = $list;
                    $data['num'] = count($list);
                }
                break;
            case 'list_work_and_project':
                $practice_list = $this->resume_practice_list;
                $project_list = $this->getProjectOriginList();
                $list = array_merge($list,$practice_list,$project_list);
                if(!empty($list)){
                    $list = Common::arraySort($list,'start_time','desc');
                    $work_type = VariablesModel::model()->getAttrById('workTypeResume', 'name');
                    $work_type[5] = I18n::getInstance()->getOther('template_work_project');
                    foreach($list as &$l){
                        $l['logo_url'] = !empty($l['logo_url']) ? IMAGE_DOMAIN.$l['logo_url'] : '';
                        $l['start_time'] = $this->handleListStartTime($l['start_time']);
                        $l['end_time'] = $this->handleListEndTime($l['end_time']);
                        $l['label'] = $work_type[$l['type_id']];
                    }
                    unset($l);
                }

                break;
            case 'classify_work_and_project':
                $work_type = VariablesModel::model()->getAttrById('workTypeResume', 'name');
                $practice_list = $this->resume_practice_list;
                $project_list = $this->getProjectOriginList();
                $list = array_merge($list,$practice_list,$project_list);
                $data = array(
                    1 => array(
                        'label' => $work_type[1],
                        'type_id' => 1,
                        'list' => array(),
                        'num' => 0
                    ),
                    3 => array(
                        'label' => $work_type[3],
                        'type_id' => 3,
                        'list' => array(),
                        'num' => 0
                    ),
                    2 => array(
                        'label' => $work_type[2],
                        'type_id' => 2,
                        'list' => array(),
                        'num' => 0
                    ),
                    4 => array(
                        'label' => $work_type[4],
                        'type_id' => 4,
                        'list' => array(),
                        'num' => 0
                    ),
                    5 => array(
                        'label' => I18n::getInstance()->getOther('template_work_project'),
                        'type_id' => 5,
                        'list' => array(),
                        'num' => 0
                    )
                );

                if(!empty($list)) {
                    foreach ($list as $l) {
                        if (!empty($data[$l['type_id']])) {
                            $data[$l['type_id']]['list'][] = array(
                                'logo_url' => !empty($l['logo_url']) ? IMAGE_DOMAIN.$l['logo_url'] : '',
                                'title' => $l['title'],
                                'description' => $l['description'],
                                'position_name' => !empty($l['position_name']) ? $l['position_name'] : '',
                                'start_time' => $this->handleListStartTime($l['start_time']),
                                'end_time' => $this->handleListEndTime($l['end_time'])
                            );
                            $data[$l['type_id']]['num']++;
                        }
                    }
                }

                $data = array_values($data);
                break;
            case 'classify_work_and_project_time':
                $practice_list = $this->resume_practice_list;
                $project_list = $this->getProjectOriginList();
                $list = array_merge($list,$practice_list,$project_list);
                if(!empty($list)) {
                    $list = Common::arraySort($list,'start_time','desc');
                    $work_type = VariablesModel::model()->getAttrById('workTypeResume', 'name');
                    $work_type[5] = I18n::getInstance()->getOther('template_work_project');
                    $month_format = I18n::getInstance()->getOther('template_month_format');

                    $res = array();
                    foreach ($list as $l) {
                        if(empty($res[date('Y',$l['start_time'])])){
                            $res[date('Y',$l['start_time'])]['label'] = date('Y',$l['start_time']);
                            $res[date('Y', $l['start_time'])]['list'] = array();
                            $res[date('Y', $l['start_time'])]['num'] = 0;
                        }
                        $res[date('Y', $l['start_time'])]['list'][] = array(
                            'title' => $l['title'],
                            'description' => $l['description'],
                            'position_name' => !empty($l['position_name']) ? $l['position_name'] : '',
                            'start_time' =>   I18n::getInstance()->getOther('template_classify_start_month',array('month'=>date($month_format,$l['start_time']))),
                            'end_time' =>   $this->handleListEndTimeByStartTime($l['start_time'],$l['end_time'],$month_format),
                            'logo_url' => !empty($l['logo_url']) ? IMAGE_DOMAIN.$l['logo_url'] : '',
                            'label' => $work_type[$l['type_id']]
                        );
                        $res[date('Y', $l['start_time'])]['num']++;
                    }
                    $data['list'] = array_values($res);
                    $data['num'] = count($res);
                }
                break;
            case 'classify_edu_experience_time':
                if(!empty($list)) {
                    $edu_experience_type = VariablesModel::model()->getAttrById('eduExperienceType', 'name');
                    $month_format = I18n::getInstance()->getOther('template_month_format');

                    $res = array();
                    foreach($list as $l){
                        if (!empty($edu_experience_type[$l['type_id']])) {
                            if(empty($res[date('Y',$l['start_time'])])){
                                $res[date('Y',$l['start_time'])]['label'] = date('Y',$l['start_time']);
                                $res[date('Y', $l['start_time'])]['list'] = array();
                                $res[date('Y', $l['start_time'])]['num'] = 0;
                            }
                            $others = !empty($l['others']) ? unserialize($l['others']) : array();
                            $res[date('Y', $l['start_time'])]['list'][] = array(
                                'title' => $l['title'],
                                'description' => $l['description'],
                                'position_name' => !empty($others['position']) ? $others['position'] : '',
                                'start_time' =>   I18n::getInstance()->getOther('template_classify_start_month',array('month'=>date($month_format,$l['start_time']))),
                                'end_time' =>   $this->handleListEndTimeByStartTime($l['start_time'],$l['end_time'],$month_format),
                                'label' => $edu_experience_type[$l['type_id']]
                            );
                            $res[date('Y', $l['start_time'])]['num']++;
                        } else if ($l['type_id'] == $this->resume_practice_type_id) {
                            $l['position_name'] = '';
                            $l['logo_url'] = '';
                            $this->resume_practice_list[] = $l;
                        }
                    }

                    $data['list'] = array_values($res);
                    $data['num'] = count($res);
                }
                break;
        }

        return $data;
    }

    private  function handleListEndTimeByStartTime($start_time,$end_time,$month_format){
        if($end_time == -1){
            return I18n::getInstance()->getOther('template_end_time');
        }

        if($end_time==$start_time){
            return '';
        }else if(date('Y',$start_time) == date('Y',$end_time)){
            return I18n::getInstance()->getOther('template_classify_end_month',array('month'=>date($month_format,$end_time)));
        }else{
            return I18n::getInstance()->getOther('template_classify_end_year_and_month',array('year'=>date('Y',$end_time),'month'=>date($month_format,$end_time)));
        }
    }

    private function getEduExperience(){
        $list = ResumeEduExperienceModel::model()->MFindAll(array(
            'field' => 'title,description,start_time,end_time,others,type_id',
            'where' => array(
                'resume_id' => $this->resume_id
            ),
            'order' => 'start_time DESC'
        ));
        
        return $this->handleList($list,$this->getEtc('edu_experience'));
    }

    private function getWork(){
        $list = ResumeWorkModel::model()->MFindAll(array(
            'field' => 'company_name AS title,company_logo_url AS logo_url,position_name,description,start_time,end_time,type_id',
            'where' => array(
                'resume_id' => $this->resume_id
            ),
            'order' => 'start_time DESC'
        ));
        return $this->handleList($list,$this->getEtc('work'));
    }

    private function getProjectOriginList(){
        return ResumeWorkProjectModel::model()->MFindAll(array(
            'field' => "title,'' AS position_name,'' AS logo_url,description,start_time,end_time,5 AS type_id",
            'where' => array(
                'resume_id' => $this->resume_id
            ),
            'order' => 'start_time DESC'
        ));
    }

    private function getProject(){
        $list = $this->getProjectOriginList();
        if(!empty($list)){
            foreach($list AS &$l){
                $l['start_time'] = $this->handleListStartTime($l['start_time']);
                $l['end_time'] = $this->handleListEndTime($l['end_time'],'certificate');
            }
            unset($l);
        }
        return array(
            'list' => $list,
            'num' => count($list)
        );
    }

    private function getCertificate(){
        $list = ResumeCertificateModel::model()->MFindAll(array(
            'field' => 'title,description,start_time,end_time',
            'where' => array(
                'resume_id' => $this->resume_id
            )
        ));

        if(!empty($list)){
            foreach($list AS &$l){
                $l['start_time'] = $this->handleListStartTime($l['start_time']);
                $l['end_time'] = $this->handleListEndTime($l['end_time'],'certificate_');
            }

            unset($l);
        }

        return $list;
    }

    /**
     * 获取简历爱好列表
     * @return mixed
     */
    private function getHobby(){
        return ResumeHobbyModel::model()->MFindAll(array(
            'field' => 'hobby_name',
            'where' => array(
                'resume_id' => $this->resume_id
            )
        ));
    }

    /**
     * 获取简历职业技能列表
     * @return mixed
     */
    private function getSkill(){
        $list = ResumeSkillModel::model()->MFindAll(array(
            'field' => 'skill_name,level',
            'where' => array(
                'resume_id' => $this->resume_id
            )
        ));

        if(!empty($list)){
            foreach($list AS &$l){
                $l['level_name'] = VariablesModel::model()->getAttrNameById('skillType',$l['level']);
            }
            unset($l);
        }

        return $list;
    }

    /**
     * 获取简历教育背景列表
     * @return mixed
     */
    private function getEdu(){
        $list = ResumeEduModel::model()->MFindAll(array(
            'field' => 'school_name_en AS school_name,degree_id,school_logo_url,college_name,major_name'
                .',double_college_name,double_major_name,course,gpa,start_time,end_time',
            'where' => array(
                'resume_id' => $this->resume_id
            )
        ));

        if(!empty($list)){
            foreach($list AS &$l){
                $l['school_logo_url'] = !empty($l['school_logo_url']) ? IMAGE_DOMAIN.$l['school_logo_url'] : '';
                $l['course'] = !empty($l['course']) ? unserialize($l['course']) : array();
                $l['degree_name'] = VariablesModel::model()->getAttrNameById('degree',$l['degree_id']);
                $l['start_time'] = date($this->global_list_time,$l['start_time']);
                $l['end_time'] = $this->handleListEndTime($l['end_time']);
                unset($l['degree_id']);
            }
            unset($l);
        }

        return $list;
    }

    /**
     * 获取求职意向信息
     * @return array
     */
    private function getTargetItem(){
        $target_data = array(
            'salary' => $this->getTargetSalary(),
            'position' => $this->getTargetPosition(),
            'location' => $this->getTargetLocation()
        );

        return $target_data;
    }

    /**
     * 获取期望工作地点
     * @return array
     */
    private function getTargetLocation(){
        $list = ResumeTargetLocationModel::model()->MFindAll(array(
            'field' => 'city_name',
            'where' => array(
                'resume_id' => $this->resume_id
            )
        ));
        $data = array(
            'list' => array(),
            'num' => 0
        );
        if(!empty($list)){
            $data['list'] = $list;
            $data['num'] = count($list);
        }

        return $data;
    }

    /**
     * 获取期望职位
     * @return array
     */
    private function getTargetPosition(){
        $list = ResumeTargetPositionModel::model()->MFindAll(array(
            'field' => 'position_name',
            'where' => array(
                'resume_id' => $this->resume_id
            )
        ));
        $data = array(
            'list' => array(),
            'num' => 0
        );
        if(!empty($list)){
            $data['list'] = $list;
            $data['num'] = count($list);
        }

        return $data;
    }

    /**
     * 获取期望薪资
     * @return array|string
     */
    private function getTargetSalary(){
        $money = $this->resume_info['target_salary'];
        if(empty($money)){
            return '';
        }

        if(is_numeric($money) && $money%1000==0){
            $min = $money/ 1000;
            if($min<=50){
                $max = $min + 1;
                return I18n::getInstance()->getOther('template_target_salary',array('min'=>$min,'max'=>$max));
            }else{
                return '';
            }
        }else {
            $salary_arr = explode('-', $money);

            if (count($salary_arr) == 2 && is_numeric($salary_arr[0]) && $salary_arr[0] % 1000 == 0 && is_numeric($salary_arr[1]) && $salary_arr[1] % 1000 == 0 && $salary_arr[0] < $salary_arr[1]) {
                $min = $salary_arr[0] / 1000;
                $max = $salary_arr[1] / 1000;
                return I18n::getInstance()->getOther('template_target_salary',array('min'=>$min,'max'=>$max));

            }else{
                return '';
            }
        }
    }

    /**
     * 获取简历模版的年龄信息
     * @return array
     */
    private function getBaseItem(){
        $data = array(
            'username' => $this->resume_info['username'],
            'head_img_url' => IMAGE_DOMAIN.$this->getHeadImgUrl(),
            'sex' => VariablesModel::model()->getAttrNameById('sex',$this->resume_info['sex']),
            'marital_status' => VariablesModel::model()->getAttrNameById('maritalStatus',$this->resume_info['marital_status']),
            'political_status' => VariablesModel::model()->getAttrNameById('politicalStatus',$this->resume_info['political_status']),
            'location_name' => $this->getLocationName(),
            'origin_name' => $this->getOriginName(),
            'nationality' => $this->resume_info['nationality'],
            'height' => $this->getHeight(),
            'weight' => $this->getWeight(),
            'mobile' => $this->resume_info['mobile'],
            'email' => $this->resume_info['email'],
            'wechat' => $this->resume_info['wechat'],
            'qq' => $this->resume_info['qq'],
            'introduction' => $this->resume_info['introduction']
        );
        $data['birth_time'] = $this->getBirthTime($this->getEtc('birth_time'));

        return $data;
    }

    /**
     * 获取简历所在地
     * @return mixed|string
     */
    private function getLocationName(){
        if($this->resume_info['location_prov_id']==990000){
            return $this->resume_info['location_city_name'];
        }else{
            $location_prov_name =  $this->i18n_id==1 ? str_replace('省','',$this->resume_info['location_prov_name']) : $this->resume_info['location_prov_name'];
            $location_city_name =  $this->i18n_id==1 ? str_replace('市','',$this->resume_info['location_city_name']) : $this->resume_info['location_city_name'];
            return ($location_prov_name!=$location_city_name) ? $location_prov_name.' '.$location_city_name : $location_city_name;
        }
    }

    /**
     * 获取简历籍贯
     * @return mixed|string
     */
    private function getOriginName(){
        if($this->resume_info['origin_prov_id'] == 0){
            return '';
        }
        if($this->resume_info['origin_prov_id']==990000){
            return $this->resume_info['origin_city_name'];
        }else{
            $origin_prov_name =  $this->i18n_id==1 ? str_replace('省','',$this->resume_info['origin_prov_name']) : $this->resume_info['origin_prov_name'];
            $origin_city_name =  $this->i18n_id==1 ? str_replace('市','',$this->resume_info['origin_city_name']) : $this->resume_info['origin_city_name'];
            return ($origin_prov_name!=$origin_city_name) ? $origin_prov_name.' '.$origin_city_name : $origin_city_name;
        }
    }

    /**
     * 获取简历中身高的展示
     * @return array|string
     */
    private function getWeight(){
        if(!empty($this->resume_info['weight'])){
            return I18n::getInstance()->getOther('template_weight',array('weight'=>$this->resume_info['weight']));
        }

        return '';
    }

    /**
     * 获取简历中身高的展示
     * @return array|string
     */
    private function getHeight(){
        if(!empty($this->resume_info['height'])){
            return I18n::getInstance()->getOther('template_height',array('height'=>$this->resume_info['height']));
        }

        return '';
    }

    /**
     * 获取简历中出生年月日的展示
     * @param $time_format
     * @return bool|string
     */
    private function getBirthTime($time_format){
        if($time_format=='age'){
            return $this->getAge();
        }
        $birth_time = $this->resume_info['birth_time'];
        if($birth_time>0){
            return date($time_format,$birth_time);
        }

        return '';
    }

    /**
     * 获取简历中年龄的展示
     * @return array|string
     */
    private function getAge(){
        $birth_time = $this->resume_info['birth_time'];
        if($birth_time>0){
            $age = date('Y', time()) - date('Y', $birth_time) - 1;
            if (date('m', time()) == date('m', $birth_time)){

                if (date('d', time()) > date('d', $birth_time)){
                    $age++;
                }
            }elseif (date('m', time()) > date('m', $birth_time)){
                $age++;
            }
            return I18n::getInstance()->getOther('template_age',array('age'=>$age));
        }else{
            return '';
        }
    }

    /**
     * 简历模版显示相关处理
     * @return string
     */
    private function getHeadImgUrl(){
        if(empty($this->resume_info['headimg_id'])){
            return $this->default_headimgurl;
        }
        // 要使用原图的模版
        if(!empty($this->getEtc('head_img_url'))){
            return ImageModel::model()->getOriginUrl($this->resume_info['headimg_id']);
        }

        return !empty($this->resume_info['headimgurl']) ? $this->resume_info['headimgurl'] : $this->default_headimgurl;
    }

}