<?php

/**
 * Created by PhpStorm.
 * User: James
 * Date: 16/5/9
 * Time: 上午11:02
 */
class SqlTemplate{

    public static function getCreateTableSql($table, $options=array()){
        $table_name = self::getTableName($table, $options);
        $charset = DB_CHARSET;
        switch ($table){
            case 'log_rs_record_view':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
                  `request_uri` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '访问的URI',
                  `day` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '访问日期',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '访问时间',
                  PRIMARY KEY (`id`),
                  KEY `Iuser_id` (`user_id`),
                  KEY `Iday` (`day`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='日志-简历-访问记录';";
                break;
            case 'log_record_link_redirect':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `link_redirect_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '跳转链接ID',
                  `ip` varchar(30) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='简历端-记录-链接访问记录';";
                break;
            case 'log_rs_record_app_download':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `ip` varchar(30) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
                  `version` varchar(30) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '版本',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载时间',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='简历端-记录-APP下载记录';";
                break;
            case 'log_rs_record_browser':
            case 'log_rwx_record_browser':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `name_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览器ID',
                  `version_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本ID',
                  `ip` varchar(30) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                  PRIMARY KEY (`id`),
                  KEY `Ibrowser_id` (`name_id`),
                  KEY `Iversion_id` (`version_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='简历端-记录-浏览器类型版本统计';";
                break;
            case 'log_scan_time':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
                  `wx_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信用户id',
                  `openid` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '微信id',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '触发时间',
                  `type` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0为未关注，1为关注',
                  PRIMARY KEY (`id`),
                  KEY `Iuser_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='微信关注取消关注记录';";
                break;
            case 'log_wx_qrcode':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `public_id` tinyint(3) unsigned NOT NULL DEFAULT '1',
                  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
                  `wx_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '微信用户id',
                  `openid` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '',
                  `qrcode_id` int(10) unsigned NOT NULL DEFAULT '0',
                  `type_id` tinyint(3) unsigned NOT NULL DEFAULT '1',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `Iuser_id` (`user_id`),
                  KEY `Iqrcode_id` (`qrcode_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='二维码扫描记录';";
                break;
            case 'wx_user':
                return "CREATE TABLE IF NOT EXISTS `{{{$table_name}}}` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `public_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '微信id',
                  `sex` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '性别',
                  `openid` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '微信返回的OPENID',
                  `nickname` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '微信昵称',
                  `province` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '省',
                  `city` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '市',
                  `country` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '国家',
                  `language` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '语言',
                  `headimgurl` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '' COMMENT '头像路径',
                  `unionid` varchar(255) COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '',
                  `is_subscribe` enum('0','1') COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '0' COMMENT '是否关注',
                  `subscribe_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户关注时间',
                  `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                  `mtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
                  `dtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
                  `status` enum('0','1') COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '1' COMMENT '状态',
                  `is_del` enum('0','1') COLLATE {$charset}_unicode_ci NOT NULL DEFAULT '0' COMMENT '是否删除',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci COMMENT='用户微信信息表';";
                break;
            default:
                return false;
                break;
        }
    }

    public static function getTableName($table, $options=array()){
        switch ($table){
            case 'log_rs_record_view':
                return $table.'_'.( ( !empty($options['year_month']) && is_numeric($options['year_month']) ) ? $options['year_month'] : date('Ym') );
                break;
            case 'log_record_link_redirect':
            case 'log_rs_record_app_download':
            case 'log_rs_record_browser':
            case 'log_rwx_record_browser':
                return $table.'_'.(!empty($options['year']) ? $options['year'] : date('Y'));
                break;
            case 'log_scan_time':
            case 'log_wx_qrcode':
            case 'wx_user':
                return $table.'_'.$options['public_id'];
                break;
            default:
                return false;
                break;
        }
    }

}