<?php

namespace WeiXin;

class Variable {
    const MSG_TYPE_TEXT = 'text';
    const MSG_TYPE_IMAGE = 'image';
    const MSG_TYPE_VOICE = 'voice';
    const MSG_TYPE_VIDEO = 'video';
    const MSG_TYPE_LOCATION = 'location';
    const MSG_TYPE_LINK = 'link';
    const MSG_TYPE_EVENT = 'event';

    const MSG_EVENT_SUBSCRIBE = 'subscribe';
    const MSG_EVENT_UNSUBSCRIBE = 'unsubscribe';
    const MSG_EVENT_SCAN = 'scan';
    const MSG_EVENT_LOCATION = 'LOCATION';
    const MSG_EVENT_CLICK = 'CLICK';

    const REPLY_TYPE_TEXT = 'text';
    const REPLY_TYPE_IMAGE = 'image';
    const REPLY_TYPE_VOICE = 'voice';
    const REPLY_TYPE_VIDEO = 'video';
    const REPLY_TYPE_MUSIC = 'music';
    const REPLY_TYPE_NEWS = 'news';
    const REPLY_TYPE_CUSTOMER_SERVICE = 'transfer_customer_service';

    const MEDIA_TYPE_IMAGE = "image";
    const MEDIA_TYPE_VOICE = 'voice';
    const MEDIA_TYPE_VIDEO = 'video';
    const MEDIA_TYPE_THUMB = 'thumb';

    const SCOPE_REDIRECT = "snsapi_base";
    const SCOPE_POP = "snsapi_userinfo";
    
    const ACCESS_TOKEN_KEY = 'access_token';
    const JSAPI_TICKET = 'jsapi_ticket';
//    const OAUTH_ACCESS_TOKEN_KEY = 'oauth_access_token';
//    const OAUTH_REFRESH_TOKEN_KEY = 'oauth_refresh_token';
    
    public static $errcode = array(
        '-1'    => '系统繁忙',
        '0'     => '请求成功',
        '40001' => '获取access_token时AppSecret错误，或者access_token无效',
        '40002' => '不合法的凭证类型',
        '40003' => '不合法的OpenID',
        '40004' => '不合法的媒体文件类型',
        '40005' => '不合法的文件类型',
        '40006' => '不合法的文件大小',
        '40007' => '不合法的媒体文件id',
        '40008' => '不合法的消息类型',
        '40009' => '不合法的图片文件大小',
        '40010' => '不合法的语音文件大小',
        '40011' => '不合法的视频文件大小',
        '40012' => '不合法的缩略图文件大小',
        '40013' => '不合法的APPID',
        '40014' => '不合法的access_token',
        '40015' => '不合法的菜单类型',
        '40016' => '不合法的按钮个数',
        '40017' => '不合法的按钮个数',
        '40018' => '不合法的按钮名字长度',
        '40019' => '不合法的按钮KEY长度',
        '40020' => '不合法的按钮URL长度',
        '40021' => '不合法的菜单版本号',
        '40022' => '不合法的子菜单级数',
        '40023' => '不合法的子菜单按钮个数',
        '40024' => '不合法的子菜单按钮类型',
        '40025' => '不合法的子菜单按钮名字长度',
        '40026' => '不合法的子菜单按钮KEY长度',
        '40027' => '不合法的子菜单按钮URL长度',
        '40028' => '不合法的自定义菜单使用用户',
        '40029' => '不合法的oauth_code',
        '40030' => '不合法的refresh_token',
        '40031' => '不合法的openid列表',
        '40032' => '不合法的openid列表长度',
        '40033' => '不合法的请求字符，不能包含\uxxxx格式的字符',
        '40035' => '不合法的参数',
        '40038' => '不合法的请求格式',
        '40039' => '不合法的URL长度',
        '40050' => '不合法的分组id',
        '40051' => '分组名字不合法',
        '41001' => '缺少access_token参数',
        '41002' => '缺少appid参数',
        '41003' => '缺少refresh_token参数',
        '41004' => '缺少secret参数',
        '41005' => '缺少多媒体文件数据',
        '41006' => '缺少media_id参数',
        '41007' => '缺少子菜单数据',
        '41008' => '缺少oauth code',
        '41009' => '缺少openid',
        '42001' => 'access_token超时',
        '42002' => 'refresh_token超时',
        '42003' => 'oauth_code超时',
        '43001' => '需要GET请求',
        '43002' => '需要POST请求',
        '43003' => '需要HTTPS请求',
        '43004' => '需要接收者关注',
        '43005' => '需要好友关系',
        '44001' => '多媒体文件为空',
        '44002' => 'POST的数据包为空',
        '44003' => '图文消息内容为空',
        '44004' => '文本消息内容为空',
        '45001' => '多媒体文件大小超过限制',
        '45002' => '消息内容超过限制',
        '45003' => '标题字段超过限制',
        '45004' => '描述字段超过限制',
        '45005' => '链接字段超过限制',
        '45006' => '图片链接字段超过限制',
        '45007' => '语音播放时间超过限制',
        '45008' => '图文消息超过限制',
        '45009' => '接口调用超过限制',
        '45010' => '创建菜单个数超过限制',
        '45015' => '回复时间超过限制',
        '45016' => '系统分组，不允许修改',
        '45017' => '分组名字过长',
        '45018' => '分组数量超过上限',
        '46001' => '不存在媒体数据',
        '46002' => '不存在的菜单版本',
        '46003' => '不存在的菜单数据',
        '46004' => '不存在的用户',
        '47001' => '解析JSON/XML内容错误',
        '48001' => 'api功能未授权',
        '50001' => '用户未授权该api',
    );
    
    public static $link = array(
        'ip_list' => 'https://api.weixin.qq.com/cgi-bin/getcallbackip',
        'access_token' => 'https://api.weixin.qq.com/cgi-bin/token',
        'message' => "https://api.weixin.qq.com/cgi-bin/message/custom/send",
        'message_send_group' => 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall',
        'message_send_openids' => 'https://api.weixin.qq.com/cgi-bin/message/mass/send',
        'message_send_template' => 'https://api.weixin.qq.com/cgi-bin/message/template/send',
        'group_create' => "https://api.weixin.qq.com/cgi-bin/groups/create",
        'group_get' => "https://api.weixin.qq.com/cgi-bin/groups/get",
        'group_getid' => "https://api.weixin.qq.com/cgi-bin/groups/getid",
        'group_rename' => "https://api.weixin.qq.com/cgi-bin/groups/update",
        'group_move' => "https://api.weixin.qq.com/cgi-bin/groups/members/update",
        'user_info' => "https://api.weixin.qq.com/cgi-bin/user/info",
        'user_get' => 'https://api.weixin.qq.com/cgi-bin/user/get',
        'user_batchget' => 'https://api.weixin.qq.com/cgi-bin/user/info/batchget',
        'menu_create' => 'https://api.weixin.qq.com/cgi-bin/menu/create',
        'menu_get' => 'https://api.weixin.qq.com/cgi-bin/menu/get',
        'menu_delete' => 'https://api.weixin.qq.com/cgi-bin/menu/delete',
        'qrcode' => 'https://api.weixin.qq.com/cgi-bin/qrcode/create',
        'showqrcode' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode',
        'media_download' => 'http://file.api.weixin.qq.com/cgi-bin/media/get',
        'media_upload' => 'https://api.weixin.qq.com/cgi-bin/media/upload',
        'add_material' => 'https://api.weixin.qq.com/cgi-bin/material/add_material',
        'del_material' => 'https://api.weixin.qq.com/cgi-bin/material/del_material',
        'news_upload' => 'https://api.weixin.qq.com/cgi-bin/media/uploadnews',
        'oauth_code' => 'https://open.weixin.qq.com/connect/oauth2/authorize',
        'oauth_access_token' => 'https://api.weixin.qq.com/sns/oauth2/access_token',
        'oauth_refresh' => 'https://api.weixin.qq.com/sns/oauth2/refresh_token',
        'oauth_userinfo' => 'https://api.weixin.qq.com/sns/userinfo',
        'jsapi_ticket' => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket',
    );
    
    public static function getErrMsg($code){
        if(isset(self::$errcode[$code])){
            return self::$errcode[$code];
        }else{
            return self::$errcode[-1];
        }
    }
    
    public static function getLink($name){
        if(isset(self::$link[$name])){
            return self::$link[$name];
        }else{
            return false;
        }
    }
}
