<?php

/**
 * Created by PhpStorm.
 * User: James
 * Date: 15/11/2
 * Time: 下午11:44
 */
class SendCloud{
    CONST TEMPLATE_MAIL_LOGIN_VERIFI = 'zhiyeapp_login_verifi';
    CONST TEMPLATE_MAIL_FORGET_PWD = 'zhiyeapp_forget_pwd';
    CONST TEMPLATE_MAIL_ACTIVITY_1_REGISTER = 'zhiyeapp_activity_1_register';
    CONST TEMPLATE_MAIL_SEND_RESUME_TO_EMAIL = 'zhiyeapp_send_resume_to_email';

    CONST TEMPLATE_MAIL_MULTI_SEND_RESUME_TO_EMAIL = 'zhiyeapp_multi_send_resume_to_email';

    CONST TEMPLATE_MAIL_COMPANY_LOGIN_VERIFI = 'zalent_login_verifi';
    CONST TEMPLATE_MAIL_COMPANY_FORGET_PWD = 'zalent_forget_pwd';
    CONST TEMPLATE_MAIL_COMPANY_JOB_REJECTED = 'zalent_job_rejected';
    CONST TEMPLATE_MAIL_COMPANY_JOB_INTERVIEW = 'zalent_job_interview';
    CONST TEMPLATE_MAIL_COMPANY_JOB_OFFER = 'zalent_job_offer';

    CONST TEMPLATE_MAIL_MULTI_COMPANY_JOB_REJECTED = 'zalent_multi_job_rejected';
    CONST TEMPLATE_MAIL_MULTI_COMPANY_JOB_SCREENED = 'zalent_multi_job_screened';
    CONST TEMPLATE_MAIL_MULTI_COMPANY_JOB_INTERVIEW = 'zalent_multi_job_interview';
    CONST TEMPLATE_MAIL_MULTI_COMPANY_JOB_OFFER = 'zalent_multi_job_offer';

    CONST URL_MAIL_SEND = 'http://sendcloud.sohu.com/webapi/mail.send.json';
    CONST URL_MAIL_TEMPLATE_SEND = 'http://sendcloud.sohu.com/webapi/mail.send_template.json';

    CONST PARAM_FROMNAME = '知页';
    CONST PARAM_FROM = 'hello@zhiyeapp.com';
    CONST PARAM_COMPANY_FROMNAME = 'Zalent';
    CONST PARAM_COMPANY_FROM = 'hello@zalent.com';

    private static $api_key = SENDCLOUD_API_KEY;
    private static $api_user = SENDCLOUD_API_USER;
    private static $api_user_multi = SENDCLOUD_API_USER_MULTI;

    private static function getAttachmentsHttp($param, $files){
        $handle = fopen($files['path'],'rb');
        $content = fread($handle,filesize($files['path']));
        $eol = "\r\n";
        $data = '';
        $mime_boundary=md5(time());

        // 配置参数
        foreach ( $param as $key => $value ) {
            $data .= '--' . $mime_boundary . $eol;
            $data .= 'Content-Disposition: form-data; ';
            $data .= "name=" . $key . $eol . $eol;
            $data .= $value . $eol;
        }

        // 配置文件
        $data .= '--' . $mime_boundary . $eol;
        $data .= 'Content-Disposition: form-data; name="'.$files['name'].'"; filename="'.$files['name'].'"' . $eol;
        $data .= 'Content-Type: text/plain' . $eol;
        $data .= 'Content-Transfer-Encoding: binary' . $eol . $eol;
        $data .= $content . $eol;
        $data .= "--" . $mime_boundary . "--" . $eol . $eol;

        return array(
            'header' => 'Content-Type: multipart/form-data;boundary='.$mime_boundary . $eol,
            'content' => $data,
        );
    }

    /**
     * 发送普通邮件
     * @param string $to 发送到的地址
     * @param string $subject  标题
     * @param string $html  内容
     * @param string $fromname  发送者名称
     * @param string $from  发送者地址
     * @param array $files  附件文件信息
     * @return string  发送结果
     */
    public static function sendMail($to,$subject,$html,$fromname=self::PARAM_FROMNAME,$from=self::PARAM_FROM,$files=array()){
        //不同于登录SendCloud站点的帐号，您需要登录后台创建发信子帐号，使用子帐号和密码才可以进行邮件的发送。
        $param = array(
            'api_user' => self::$api_user,
            'api_key' => self::$api_key,
            'from' => $from,
            'fromname' => $fromname,
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'resp_email_id' => 'true'
        );
        if(!empty($files)){
            $http = self::getAttachmentsHttp($param, $files);
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => $http['header'],
                    'content' => $http['content'],
                ));
        }else{
            $data = http_build_query($param);
            $options = array(
                'http' => array(
                    'method'  => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $data
                ));
        }
        $context  = stream_context_create($options);
        $res = file_get_contents(self::URL_MAIL_SEND, FILE_TEXT, $context);
        Log::mail('sendMail', $res);
        return $res;
    }

    /**
     * 发送模板邮件
     * @param string $to 发送到的地址
     * @param string $subject  标题
     * @param string $template_invoke_name  模板名称
     * @param array  $options  模板配置项
     * @param string $fromname  发送者名称
     * @param string $from  发送者地址
     * @param array $files  附件文件信息
     * @return string  发送结果
     */
    public static function sendTemplateMail($to,$subject,$template_invoke_name,$options,$fromname=self::PARAM_FROMNAME,$from=self::PARAM_FROM,$files=array()) {
        if(is_array($to)){
            $var_to = $to;
            $api_user = self::$api_user_multi;
        }else{
            $var_to = array($to);
            $api_user = self::$api_user;
        }

        $vars = json_encode(
            array(
                "to" => $var_to,
                "sub" => self::templateSub($template_invoke_name,$options)
            )
        );
        $param = array(
            'api_user' => $api_user, # 使用api_user和api_key进行验证
            'api_key' => self::$api_key,
            'from' => $from, # 发信人，用正确邮件地址替代
            'fromname' => $fromname,
            'substitution_vars' => $vars,
            'template_invoke_name' => $template_invoke_name,
            'subject' => $subject,
            'resp_email_id' => 'true',
        );
        if(!empty($files)){
            $http = self::getAttachmentsHttp($param, $files);
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => $http['header'],
                    'content' => $http['content'],
                ));
        }else{
            $data = http_build_query($param);
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $data
                ));
        }
        $context  = stream_context_create($options);
        $res = file_get_contents(self::URL_MAIL_TEMPLATE_SEND, FILE_TEXT, $context);
        Log::mail('sendTemplateMail', $res);
        return $res;
    }

    /**
     * 获取模板变量
     * @param string $template_invoke_name 模板名称
     * @param array $options 模板配置
     * @return array 模板变量
     */
    private static function templateSub($template_invoke_name, $options=array()){
        $res = array();
        switch ($template_invoke_name){
            case self::TEMPLATE_MAIL_LOGIN_VERIFI:
                $res = array(
                    "%logoimg%" => array($options['logoimg']),
                    "%wxqrcodeimg%" => array($options['wxqrcodeimg']),
                    "%email%" => array($options['email']),
                    "%url%" => array($options['url']),
                    "%link%" => array($options['link']),
                    "%dsxQQ%" => array($options['dsxQQ']),
                    "%domain%" => array($options['domain']),
                );
                break;
            case self::TEMPLATE_MAIL_FORGET_PWD:
                $res = array(
                    "%logoimg%" => array($options['logoimg']),
                    "%wxqrcodeimg%" => array($options['wxqrcodeimg']),
                    "%url%" => array($options['url']),
                    "%limitTime%" => array($options['limitTime']),
                    "%dsxQQ%" => array($options['dsxQQ']),
                    "%domain%" => array($options['domain']),
                );
                break;
            case self::TEMPLATE_MAIL_ACTIVITY_1_REGISTER:
                $res = array(
                    "%logoimg%" => array($options['logoimg']),
                    "%wxqrcodeimg%" => array($options['wxqrcodeimg']),
                    "%email%" => array($options['email']),
                    "%link%" => array($options['link']),
                    "%dsxQQ%" => array($options['dsxQQ']),
                    "%domain%" => array($options['domain']),
                );
                break;
            case self::TEMPLATE_MAIL_SEND_RESUME_TO_EMAIL:
                $res = array(
                    "%logoimg%" => array($options['logoimg']),
                    "%wxqrcodeimg%" => array($options['wxqrcodeimg']),
                    "%dsxQQ%" => array($options['dsxQQ']),
                    "%domain%" => array($options['domain']),
                );
                break;
            case self::TEMPLATE_MAIL_MULTI_SEND_RESUME_TO_EMAIL:
                $res = array(
                    "%logoimg%" => $options['logoimg'],
                    "%wxqrcodeimg%" => $options['wxqrcodeimg'],
                    "%dsxQQ%" => $options['dsxQQ'],
                    "%domain%" => $options['domain'],
                );
                break;
            case self::TEMPLATE_MAIL_COMPANY_LOGIN_VERIFI:
                $res = array(
                    "%logoimg%" => array($options['logoimg']),
                    "%wxqrcodeimg%" => array($options['wxqrcodeimg']),
                    "%qq%" => array($options['qq']),
                    "%email%" => array($options['email']),
                    "%url%" => array($options['url']),
                    "%link%" => array($options['link']),
                    "%domain%" => array($options['domain']),
                );
                break;
            case self::TEMPLATE_MAIL_COMPANY_FORGET_PWD:
                $res = array(
                    "%logoimg%" => array($options['logoimg']),
                    "%wxqrcodeimg%" => array($options['wxqrcodeimg']),
                    "%url%" => array($options['url']),
                    "%limitTime%" => array($options['limitTime']),
                    "%domain%" => array($options['domain']),
                );
                break;
            case self::TEMPLATE_MAIL_COMPANY_JOB_REJECTED:
                $res = array(
                    "%head_img%" => array($options['head_img']),
                    "%company_name%" => array($options['company_name']),
                    "%user_name%" => array($options['user_name']),
                    "%content%" => array($options['content']),
                );
                break;
            case self::TEMPLATE_MAIL_MULTI_COMPANY_JOB_REJECTED:
                $res = array(
                    "%logo_img%" => $options['logo_img'],
                    "%domain%" => $options['domain'],
                    "%head_img%" => $options['head_img'],
                    "%company_name%" => $options['company_name'],
                    "%user_name%" => $options['user_name'],
                    "%content%" => $options['content'],
                    "%wxqrcode_img%" => $options['wxqrcode_img'],
                    "%qq_img%" => $options['qq_img'],
                );
                break;
            case self::TEMPLATE_MAIL_MULTI_COMPANY_JOB_SCREENED:
                $res = array(
                    "%logo_img%" => $options['logo_img'],
                    "%domain%" => $options['domain'],
                    "%head_img%" => $options['head_img'],
                    "%company_name%" => $options['company_name'],
                    "%user_name%" => $options['user_name'],
                    "%position_name%" => $options['position_name'],
                    "%wxqrcode_img%" => $options['wxqrcode_img'],
                    "%qq_img%" => $options['qq_img'],
                );
                break;
            case self::TEMPLATE_MAIL_COMPANY_JOB_INTERVIEW:
                $res = array(
                    "%head_img%" => array($options['head_img']),
                    "%user_name%" => array($options['user_name']),
                    "%company_name%" => array($options['company_name']),
                    "%content1%" => array($options['content1']),
                    "%content2%" => array($options['content2']),
                    "%button_href%" => array($options['button_href']),
                );
                break;
            case self::TEMPLATE_MAIL_MULTI_COMPANY_JOB_INTERVIEW:
                $res = array(
                    "%logo_img%" => $options['logo_img'],
                    "%domain%" => $options['domain'],
                    "%head_img%" => $options['head_img'],
                    "%user_name%" => $options['user_name'],
                    "%company_name%" => $options['company_name'],
                    "%content1%" => $options['content1'],
                    "%content2%" => $options['content2'],
                    "%wxqrcode_img%" => $options['wxqrcode_img'],
                    "%qq_img%" => $options['qq_img'],
                );
                break;
            case self::TEMPLATE_MAIL_COMPANY_JOB_OFFER:
                $res = array(
                    "%head_img%" => array($options['head_img']),
                    "%user_name%" => array($options['user_name']),
                    "%company_name%" => array($options['company_name']),
                    "%content1%" => array($options['content1']),
                    "%content2%" => array($options['content2']),
                    "%button_href%" => $options['button_href'],
                );
                break;
            case self::TEMPLATE_MAIL_MULTI_COMPANY_JOB_OFFER:
                $res = array(
                    "%logo_img%" => $options['logo_img'],
                    "%domain%" => $options['domain'],
                    "%head_img%" => $options['head_img'],
                    "%user_name%" => $options['user_name'],
                    "%company_name%" => $options['company_name'],
                    "%content1%" => $options['content1'],
                    "%content2%" => $options['content2'],
                    "%wxqrcode_img%" => $options['wxqrcode_img'],
                    "%qq_img%" => $options['qq_img'],
                );
                break;
            default :
                break;
        }
        return $res;
    }

}