<?php

class SiteController extends BaseresumeController {

    public function init(){
        parent::init();
    }

    private function checkTestLimit($username, $error_code=''){
        if($this->checkTest() && empty(ZUserTestModel::model()->checkLimit($username))){
            $this->sendFail($error_code, I18n::getInstance()->getErrorController('account_not_allow'));
        }
    }

    // 接口-公共-检测是否登录
    public function checkLoginAction(){
        if($this->checkLogin(0)){
            $this->send(array(
                'user_info' => $this->getSendUserInfo(),
                'from_check_login' => 1,
            ));
        }else{
            $this->sendFail(CODE_USER_NOT_LOGIN, '', array('from_check_login' => 1));
        }
    }

    // 接口-极验验证启动
    public function geeStartAction(){
        Yaf\Loader::import('GeeTeam/lib/class.geetestlib.php');
        $GtSdk = new GeetestLib();
        $return = $GtSdk->register();
        $this->setTokenCacheData('gt_version', 2);
        if ($return) {
            $this->setTokenCacheData('gtserver', 1);
            $this->send(array(
                'success' => 1,
                'gt' => CAPTCHA_ID,
                'challenge' => $GtSdk->challenge
            ));
        }else{
            $this->setTokenCacheData('gtserver', 0);
            $rnd1 = md5(rand(0,100));
            $rnd2 = md5(rand(0,100));
            $challenge = $rnd1 . substr($rnd2,0,2);
            $result = array(
                'success' => 0,
                'gt' => CAPTCHA_ID,
                'challenge' => $challenge
            );
            $this->setTokenCacheData('challenge', $result['challenge']);
            $this->send($result);
        }
    }

    public function geeStart3Action(){
        Yaf\Loader::import('GeeTeam3/lib/class.geetestlib.php');
        $GtSdk = new GeetestLib(GEETEST3_CAPTCHA_ID, GEETEST3_PRIVATE_KEY);
        $status = $GtSdk->pre_process(array(
            "client_type" => ($this->_is_web == 1) ? 'web' : 'h5', #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => Tools::getRemoteAddr() # 请在此处传输用户请求验证时所携带的IP
        ), 1);
        $response = $GtSdk->get_response();
        $this->setTokenCacheData('gtserver', $status);
        $this->setTokenCacheData('gt_version', 3);
        $this->send($response);
    }

    // 接口-发送注册验证码
    public function sendMobileRegMsgAction(){
        $data = $this->_request();
        $this->verificationModelRules('', $data, array(
            '_module' => 'resume_mobile_register',
            'enable' => array('mobile'),
        ));

        $this->checkTestLimit($data['mobile'], 'mobile');
        $this->checkGeeCaptcha();

        //检测手机是否有记录
        if(!empty(UserModel::model()->getBindByMobile($data['mobile']))) {
            $this->sendFail('mobile', I18n::getInstance()->getErrorController('mobile_is_register'));
        }else{
            if($this->sendMessage($data['mobile'])){
                $this->send(array(), '', 0);
            }else{
                $this->sendFail('mobile', I18n::getInstance()->getErrorController('fail_send_mobile_code'));
            }
        }
    }

    // 接口-手机注册
    public function mobileRegisterAction(){
        $data = $this->_request();
        $this->verificationModelRules('', $data, 'resume_mobile_register');

        $this->checkTestLimit($data['mobile'], 'mobile');
        //校验验证码
        $code_data = IRedis::getInstance()->get('mobile_'.$data['mobile']);
        if(empty($code_data) || !Common::compareMobile($code_data, $data['mobile'], $data['mobile_code'])){
            $this->sendFail('mobile_code', I18n::getInstance()->getErrorController('mobile_code_wrong'));
        }
        //确认手机号码没有注册
        if(!empty(UserModel::model()->getBindByMobile($data['mobile']))){
            $this->sendFail('mobile', I18n::getInstance()->getErrorController('mobile_is_register'));
        }

        //用户数据登录数据录入
        $user_id = UserModel::model()->MSave(array(
            'is_verifi' => 1,
            'mobile' => $data['mobile'],
            'password' => UserModel::model()->getPassword($data['password']),
            'register_type_id' => VariablesModel::model()->getAttrs('registerType','mobile','id'),
            'register_plat_type_id' => ($this->_is_web == 1) ? VariablesModel::model()->getAttrs('registerPlatType','web_pc','id') : VariablesModel::model()->getAttrs('registerPlatType','web_mobile','id'),
        ));

        if(!empty($user_id)){
            //用户登录操作
            $sess_id = UserModel::model()->login(array(
                'user' => array(
                    'id' => $user_id,
                    'mobile' => $data['mobile'],
                    'mobile_security' => $data['mobile'],
                    'email' => '',
                    'email_security' => '',
                ),
            ), $user_id, $this->_sess_prefix);

            if(!empty($sess_id)){
                $this->_user['id'] = $user_id;
                $this->_user['i18n_id'] = 1;

                $this->send(array(
                    'ls_sess_id' => $sess_id,
                    'ls_sess_expire' => 86400*30,
                    'next' => $this->checkGuide(0) ? 'resume' : 'guide',
                ), '', 0);
                IRedis::getInstance()->delete('mobile_'.$data['mobile']);
            }else{
                $this->sendFail('mobile', I18n::getInstance()->getErrorController('fail_register'));
            }
        }else{
            $this->sendFail('mobile', I18n::getInstance()->getErrorController('fail_register'));
        }
    }

    // 接口-邮箱注册
    public function emailRegisterAction(){
        $data = $this->_request();
        $this->verificationModelRules('', $data, 'resume_email_register');

        $this->checkTestLimit($data['email'], 'email');
//        $this->checkGeeCaptcha();
        //确认手机号码没有注册
        if(!empty(UserModel::model()->getBindByEmail($data['email']))){
            $this->sendFail('email', I18n::getInstance()->getErrorController('email_is_register'));
        }

        //用户数据登录数据录入
        $user_id = UserModel::model()->MSave(array(
            'email' => $data['email'],
            'password' => UserModel::model()->getPassword($data['password']),
            'register_type_id' => VariablesModel::model()->getAttrs('registerType','email','id'),
            'register_plat_type_id' => ($this->_is_web == 1) ? VariablesModel::model()->getAttrs('registerPlatType','web_pc','id') : VariablesModel::model()->getAttrs('registerPlatType','web_mobile','id'),
        ));

        if(!empty($user_id)){
            //用户登录操作
            $sess_id = UserModel::model()->login(array(
                'user' => array(
                    'id' => $user_id,
                    'mobile' => '',
                    'mobile_security' => '',
                    'email' => $data['email'],
                    'email_security' => $data['email'],
                ),
            ), $user_id, $this->_sess_prefix);

            if(!empty($sess_id)){
                $this->_user['id'] = $user_id;
                $this->_user['i18n_id'] = 1;

                $this->send(array(
                    'ls_sess_id' => $sess_id,
                    'ls_sess_expire' => 86400*30,
                    'next' => $this->checkGuide(0) ? 'resume' : 'guide',
                ), '', 0);
            }else{
                $this->sendFail('email', I18n::getInstance()->getErrorController('fail_register'));
            }
        }else{
            $this->sendFail('email', I18n::getInstance()->getErrorController('fail_register'));
        }
    }

    // 接口-登录
    public function loginAction(){
        $data = $this->_request();
        $this->verificationModelRules('', $data, 'resume_login');

        $this->checkTestLimit($data['username'], 'username');
        if(Common::isMobile($data['username'])){
            $user_info = UserModel::model()->getBindByMobile($data['username'], 'id,mobile,email,password,i18n_id');
            $username_msg_key = 'mobile_is_not_register';
        }elseif(Common::isEmail($data['username'])){
            $user_info = UserModel::model()->getBindByEmail($data['username'], 'id,mobile,email,password,i18n_id');
            $username_msg_key = 'email_is_not_register';
        }else{
            $this->sendFail('username', I18n::getInstance()->getErrorController('format_wrong_login_username'));
        }

        if(empty($user_info)){
            $this->sendFail('username', I18n::getInstance()->getErrorController($username_msg_key));
        }

        if(!UserModel::model()->verifyPassword($data['password'], $user_info['password'])){
            $this->sendFail('password', I18n::getInstance()->getErrorController('username_or_password_wrong'));
        }

        //用户登录操作
        $sess_id = UserModel::model()->login(array(
            'user' => array(
                'id' => $user_info['id'],
                'mobile' => $user_info['mobile'],
                'mobile_security' => $user_info['mobile'],
                'email' => $user_info['email'],
                'email_security' => $user_info['email'],
            ),
        ), $user_info['id'], $this->_sess_prefix);
        if(!empty($sess_id)){
            $this->_user['id'] = $user_info['id'];
            $this->_user['i18n_id'] = $user_info['i18n_id'];

            $this->send(array(
                'ls_sess_id' => $sess_id,
                'ls_sess_expire' => ($this->_request('is_auto_login') == 1) ? 30 : 0,
                'next' => $this->checkGuide(0) ? 'resume' : 'guide',
            ), '', 0);
        }else{
            $this->sendFail('username', I18n::getInstance()->getErrorController('fail_login'));
        }
    }

    // 接口-退出登录
    public function logoutAction(){
        $sess_id = $this->_request('ls_sess_id');
        if(!empty($sess_id)){
            UserModel::model()->logout($sess_id, $this->_sess_prefix);
        }
        $this->send(array(), '', 0);
    }

    // 接口-获取引导页信息
    public function resumeGuideViewAction(){
        $this->checkLogin();

        $res = array(
            'id' => 0,
            'i18n_id' => $this->_user['i18n_id'],
            'username' => '',
            'sex' => '1',
            'email' => '',
            'mobile' => '',
            'location_prov_id' => 0,
            'location_city_id' => 0,
            'school_id' => 0,
            'school_name' => '',
            'major_name' => '',
            'degree_id' => 3,
            'start_time' => (date('Y') - 4).'-09',
            'end_time' => date('Y').'-06',
        );

        $resume_data = ResumeModel::model()->MFind(array(
            'field' => 'username,sex,email,mobile,location_prov_id,location_city_id',
            'where' => array('user_id'=>$this->_user['id'], 'i18n_id'=>$this->_user['i18n_id']),
        ));
        if(!empty($resume_data)){
            $res = array_merge($res, $resume_data);
        }elseif($this->_user['i18n_id'] != 1){
            $resume_cn_data = ResumeModel::model()->MFind(array(
                'field' => 'email,mobile,location_prov_id,location_city_id',
                'where' => array('user_id'=>$this->_user['id'], 'i18n_id'=>1),
            ));
            if(!empty($resume_cn_data)){
                $res = array_merge($res, $resume_cn_data);
            }
        }

        if($this->_user['i18n_id'] != 1){
            VariablesModel::model()->setI18nById($this->_user['i18n_id']);
        }

        if(empty($res['email'])){
            $res['email'] = $this->_user['email'];
        }
        if(empty($res['mobile'])){
            $res['mobile'] = $this->_user['mobile'];
        }
        $this->send(array(
            'user_info' => $this->getSendUserInfo(),
            'resume_info' => $res,
            'var_data' => array(
                'sex' => array('list' => VariablesModel::model()->getList('sex')),
                'degree' => array('list' => VariablesModel::model()->getList('degree')),
            ),
        ));
    }

    // 接口-获取引导页信息
    public function resumeGuideSaveAction(){
        $this->checkLogin();
        $data = $this->_request();
        switch ($data['i18n_id']){
            case 1:
                $rules_disable = 'school_name';
                $name_field = 'name';
                break;
            case 11:
                $rules_disable = 'school_id';
                $name_field = 'name_en';
                break;
            default:
                $this->sendError();exit;
                break;
        }
        $this->verificationModelRules('', $data, array(
            '_module' => 'resume_guide',
            'disable' => $rules_disable,
        ));

        //是否已经生成简历
        $resume_check = ResumeModel::model()->MFind(array(
            'field' => 'id',
            'where' => array('user_id'=>$this->_user['id'], 'i18n_id'=>$data['i18n_id']),
        ));
        if(!empty($resume_check)){
            $this->sendFail(CODE_GUIDE_RESUME_HAS_BEEN_SUBMIT, $resume_check['id']);
        }

        if($data['i18n_id'] == 1){
            //检测学校是否存在
            $school_check = SchoolSearchModel::model()->MGetInfoById($data['school_id'], 'id');
            if(empty($school_check)){
                $this->sendFail('school_id', I18n::getInstance()->getErrorController('school_not_exist'));
            }
        }

        //检查省份和城市是否存在,是否对应
        $prov_check = AreaModel::model()->MFind(array(
            'field' => "id,{$name_field} AS name",
            'where' => array('id' => $data['location_prov_id'], 'parent_id' => 0),
        ));
        if(empty($prov_check)){
            $this->sendFail('location_prov_id', I18n::getInstance()->getErrorController('prov_not_exist'));
        }
        $city_check = AreaModel::model()->MFind(array(
            'field' => "id,{$name_field} AS name",
            'where' => array('id' => $data['location_city_id'], 'parent_id' => $data['location_prov_id']),
        ));
        if(empty($city_check)){
            $this->sendFail('location_city_id', I18n::getInstance()->getErrorController('city_not_exist'));
        }

        $now_time = time();
        $resume_save_data = array(
            'user_id' => $this->_user['id'],
            'username' => $data['username'],
            'sex' => $data['sex'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'location_prov_id' => $data['location_prov_id'],
            'location_prov_name' => $prov_check['name'],
            'location_city_id' => $data['location_city_id'],
            'location_city_name' => $city_check['name'],
            'i18n_id' => $data['i18n_id'],
            'finish_time' => $now_time,
            'finish_plat_type_id' => ($this->_is_web == 1) ? VariablesModel::model()->getAttrId('resumeFinishPlatType','web_pc') : VariablesModel::model()->getAttrId('resumeFinishPlatType','web_mobile'),
        );
        //生成英文简历时,抓取中文简历的部分基本信息
        if($data['i18n_id'] == 11){
            $resume_info = ResumeModel::model()->MFind(array(
                'field' => 'id,qq,wechat,headimgurl,headimg_id,birth_time,personal_links,target_type
                            ,target_salary,marital_status,political_status,height,weight
                            ,origin_prov_id,origin_city_id',
                'where' => array('user_id'=>$this->_user['id'], 'i18n_id'=>1)
            ));
            if(!empty($resume_info)){
                $base_resume_id = $resume_info['id'];
                unset($resume_info['id']);
                $resume_save_data = array_merge($resume_save_data, $resume_info);

                $origin_area_ids = array();
                if(!empty($resume_save_data['origin_prov_id'])){
                    $origin_area_ids[] = $resume_save_data['origin_prov_id'];
                }
                if(!empty($resume_save_data['origin_city_id'])){
                    $origin_area_ids[] = $resume_save_data['origin_city_id'];
                }
                if(!empty($origin_area_ids)){
                    $origin_area_list = AreaModel::model()->MFindAll(array(
                        'field' =>  "id,{$name_field} AS name",
                        'where' => array('id' => $origin_area_ids),
                    ));
                    if(!empty($origin_area_list)){
                        $origin_area_list = Common::arrayColumnToKey($origin_area_list, 'id');
                        $resume_save_data['origin_prov_name'] = !empty($origin_area_list[$resume_save_data['origin_prov_id']]['name']) ? $origin_area_list[$resume_save_data['origin_prov_id']]['name'] : '';
                        $resume_save_data['origin_city_name'] = !empty($origin_area_list[$resume_save_data['origin_city_id']]['name']) ? $origin_area_list[$resume_save_data['origin_city_id']]['name'] : '';
                    }
                }
            }
        }

        //保存简历基本信息
        $resume_id = ResumeModel::model()->MSave($resume_save_data);
        if(!empty($resume_id)){
            //保存教育经历
            ResumeEduModel::model()->MSave(array(
                'user_id' => $this->_user['id'],
                'resume_id' => $resume_id,
                'school_id' => isset($data['school_id']) ? $data['school_id'] : 0,
                'school_name_en' => isset($data['school_name']) ? $data['school_name'] : '',
                'degree_id' => $data['degree_id'],
                'major_id' => MajorModel::model()->getIdByName($data['major_name'], $this->_user['id']),
                'major_name' => $data['major_name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'resume_finish_time' => $now_time,
            ));

            //生成英文简历时,并且有基础简历
            if($data['i18n_id'] == 11 && !empty($base_resume_id)){
                //抓取中文简历的期望行业
                $resume_industry_list = ResumeIndustryModel::model()->MFindAll(array(
                    'field' => 'industry_pid,industry_id,sort',
                    'where' => array('resume_id' => $base_resume_id),
                    'order' => 'sort ASC',
                ));
                if(!empty($resume_industry_list)){
                    $industry_ids = array_merge(array_column($resume_industry_list, 'industry_pid'), array_column($resume_industry_list, 'industry_id'));
                    $industry_list = IndustryModel::model()->MFindAll(array(
                        'field' =>  "id,{$name_field} AS name",
                        'where' => array('id' => $industry_ids),
                    ));
                    if(!empty($industry_list)){
                        $industry_list = Common::arrayColumnToKey($industry_list, 'id');
                    }

                    $resume_industry_save_data = array();
                    foreach ($resume_industry_list as $item){
                        $resume_industry_save_data[] = array(
                            'resume_id' => $resume_id,
                            'user_id' => $this->_user['id'],
                            'industry_pid' => $item['industry_pid'],
                            'industry_pname' => !empty($industry_list[$item['industry_pid']]['name']) ? $industry_list[$item['industry_pid']]['name'] : '',
                            'industry_id' => $item['industry_id'],
                            'industry_name' => !empty($industry_list[$item['industry_id']]['name']) ? $industry_list[$item['industry_id']]['name'] : '',
                            'sort' => $item['sort'],
                            'ctime' => $now_time,
                            'mtime' => $now_time,
                        );
                    }
                    ResumeIndustryModel::model()->MInsertMulti($resume_industry_save_data);
                }

                //抓取中文简历的期望地点
                $resume_location_list = ResumeTargetLocationModel::model()->MFindAll(array(
                    'field' => 'location_prov_id,location_city_id,sort',
                    'where' => array('resume_id' => $base_resume_id),
                    'order' => 'sort ASC',
                ));
                if(!empty($resume_location_list)){
                    $area_ids = array_merge(array_column($resume_location_list, 'location_prov_id'), array_column($resume_location_list, 'location_city_id'));
                    $area_list = AreaModel::model()->MFindAll(array(
                        'field' =>  "id,{$name_field} AS name",
                        'where' => array('id' => $area_ids),
                    ));
                    if(!empty($area_list)){
                        $area_list = Common::arrayColumnToKey($area_list, 'id');
                    }
                    
                    $resume_location_save_data = array();
                    foreach($resume_location_list as $item){
                        $resume_location_save_data[] = array(
                            'resume_id' => $resume_id,
                            'user_id' => $this->_user['id'],
                            'location_city_id' => $item['location_city_id'],
                            'city_name' => !empty($area_list[$item['location_city_id']]['name']) ? $area_list[$item['location_city_id']]['name'] : '',
                            'location_prov_id' => $item['location_prov_id'],
                            'prov_name' => !empty($area_list[$item['location_prov_id']]['name']) ? $area_list[$item['location_prov_id']]['name'] : '',
                            'sort' => $item['sort'],
                            'ctime' => $now_time,
                            'mtime' => $now_time,
                        );
                    }
                    ResumeTargetLocationModel::model()->MInsertMulti($resume_location_save_data);
                }
            }
            $this->send();

            //插入完成度资料
            ResumeCountModel::model()->getResumeCompetitiveness($resume_id);
        }else{
            $this->sendError();
        }
    }

    public function getSchoolAction(){
        $keywords = trim($this->_get('keywords'));
        $list = $bak_list = array();
        $num = $bak_num = 0;
        if(!empty($keywords)){
            $school_list = IRedis::getInstance()->get('school_list');
            if(empty($school_list)){
                $options = array(
                    'field' => 'id,name,logo_square_url AS logo_url',
                    'order' => 'ctime ASC,id ASC',
                );
                $school_list = SchoolSearchModel::model()->MFindAll($options);
                IRedis::getInstance()->set('school_list', $school_list);
            }

            $max_num = 10;
            foreach ($school_list as $k=>$l){
                $pos_index = stripos($l['name'], $keywords);
                if($pos_index !== false){
//                    $l['logo_url'] = !empty($l['logo_url']) ? IMAGE_DOMAIN.$l['logo_url'] : '';

                    if($pos_index == 0){
                        array_push($list, $l);
                        $num++;
                    }elseif($bak_num < $max_num){
                        array_push($bak_list, $l);
                        $bak_num++;
                    }
                }
                if($num == $max_num){
                    break;
                }
            }
            if($num < $max_num && $bak_num != 0){
                $list = array_merge($list, array_slice($bak_list, 0, $max_num - $num));
                $num = count($list);
            }
        }
        $data = array(
            'list' => $list,
            'num' => $num,
            'image_domain' => IMAGE_DOMAIN,
            // 'default_logo_url' => SchoolSearchModel::$defalut_logo_url,
        );
        
        $this->send($data);
    }

    public function checkSchoolExistAction(){
        $name = trim($this->_get('name'));
        if(!empty($name)){
            $row = SchoolSearchModel::model()->MFind(array(
                'field' => 'id',
                'where' => array(
                    'name' => $name
                ) 
            ));
            if(!empty($row)){
                $this->send($row);
            }else{
                $this->sendFail(CODE_FORMAT_WRONG_FIELD, I18n::getInstance()->getErrorController('is_empty_school_name'));
            }
        }else{
            $this->sendFail(CODE_IS_EMPTY_FIELD, I18n::getInstance()->getErrorController('is_empty_school_name'));
        }
    }
}
