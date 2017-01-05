<?php

namespace config;

error_reporting(E_ERROR);
date_default_timezone_set('Asia/Shanghai');

class common {

    public $config = array(
        'zipPath'=>"/home/system/opt/again_tools/exchangeApi/rar/",
        'edpDataRedis'=>array('host'=>'192.168.1.16','port'=>6370),
        'edpDJRedis'=>array('host'=>'192.168.1.185','port'=>6372),
        'php' => '/home/system/php/bin/php ',
        'banner_cdn_domain' => "http://v.behe.com",
        'video_cdn_domain' => "http://s.behe.com",
        '91banner_cdn_domain' => "https://v.behe.com",
        '91video_cdn_domain' => "https://s.behe.com",
        'file_type' => array(1 => 'png', 2 => 'gif', 3 => 'jpg', 4 => 'flv', 5 => 'swf', 8 => 'mp4'),
        'file_system_path' => "/home/system/apache/htdocs/again/public/upload/material",
        'file_logo_system_path' => "/home/system/apache/htdocs/again/public/upload/logo",
        'duration_type_ary' => array(0 => 15, 1 => 15, 2 => 30, 3 => 60, 4 => 5),
        'account_file_domain' => "http://again.behe.com/upload/logo",
        'allow_db' => array(
            'production' => array(
                'report' => array('dbname' => 'again_dsp_report_time', 'offset' => 2),
                'again_dsp_report_time' => array('dbname' => 'again_v1_dsp_report_time', 'offset' => 1),
                'again_dsp_report_pay' => array('dbname' => 'again_v1_dsp_report_pay', 'offset' => 1),
                'again_ip_database_v1' => array('dbname' => 'again_ip_database_v1', 'offset' => 0),
                'again_v1_main' => array('dbname' => 'again_v1_main', 'offset' => 0),
                'again_center' => array('dbname' => 'again_center', 'offset' => 1),
                'again_dsp_report_follow' => array('dbname' => 'again_v1_dsp_report_follow', 'offset' => 1),
                'again_dsp_report_channel' => array('dbname' => 'again_v1_dsp_report_channel', 'offset' => 1),
                "yooshu_udsp_basic" => array('dbname' => 'yooshu_udsp_basic', 'offset' => 3)
            ),
            'development' => array(
                'again_v1_main' => array('dbname' => 'again_v1_main', 'offset' => 5),
                'again_v1_info' => array('dbname' => 'again_v1_info', 'offset' => 5)
            ),
        ),
        'db' => array(
            array(
                'ip' => "192.168.1.168",
                'username' => "real_budget",
                'password' => "UeEyuS5czxB9he6W",
                'port' => 3306,
                'options' => array(
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            ),
            array(
                'ip' => "192.168.1.160",
                'username' => "real_budget",
                'password' => "UeEyuS5czxB9he6W",
                'port' => 3306,
                'options' => array(
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            ),
            array(
                'ip' => "192.168.1.212",
                'username' => "real_budget",
                'password' => "UeEyuS5czxB9he6W",
                'port' => 3306,
                'options' => array(
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            ),
            array(
                'ip' => "10.1.1.105",
                'username' => "dsp-test",
                'password' => "yooshudsp123!@#",
                'port' => 7066,
                'options' => array(
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            ),
            array(
                'ip' => "192.168.1.166",
                'username' => "real_budget",
                'password' => "UeEyuS5czxB9he6W",
                'port' => 3306,
                'options' => array(
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            ),
            array(
                'ip' => "192.168.1.19",
                'username' => "real_test",
                'password' => "eZcR7dxteVDayXA",
                'port' => 3306,
                'options' => array(
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            )
        )
    );

}

?>