<?php
namespace adxApiV2;

error_reporting(1);
define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/tanxConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tanxConfig as tanxConfig;
use \library\base as base;

class tanxMaterialStatus extends base{

    public $appName = 'tanxMaterialStatus';

    public function run(){
        $this->tanxConfig = new tanxConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_material_list = array();
        $arr_order_list = array();
        $date = date("Y-m-d H:i:s", strtotime('-7 day'));
        $arr_center_info = $this->getWaitStatusMaterial("adxId =3 and adxStatus in (0,99)  and ctime>='{$date}' order by id desc");
        $tanx_material_status_ary = array('PASS' => 1, 'REFUSE' => 2, 'WAITING' => 0);
        $tanx_level_ary = array(99 => 0, 1 => 1);
        $i = 1;
        if ($arr_center_info) {
            foreach ($arr_center_info as $k => $item) {
                $orderId = $item['orderId'];
                $arr_send_json = array();
                $arr_send_json['creative_id'] = $item['advertId'];
                $arr_send_json['member_id'] = $this->tanxConfig->tanx_memberId;
                $arr_send_json['method'] = $this->tanxConfig->tanx_material_status;
                $arr_send_json['format'] = 'json';
                $arr_send_json["v"] = "2.0";
                $arr_send_json['sign_method'] = 'md5';
                $arr_send_json['app_key'] = $this->tanxConfig->tanx_appkey;
                $time = time();
                $token = md5($this->tanxConfig->tanx_userKey . $time);
                $arr_send_json['timestamp'] = date('Y-m-d H:i:s');
                $arr_send_json['token'] = $token;
                $arr_send_json['sign_time'] = $time;
                ksort($arr_send_json);
                $sign = $this->getSign($arr_send_json);
                $arr_send_json['sign'] = $sign;
                $return_json = $this->postCurl($this->tanxConfig->tanx_api_url, $arr_send_json);
                $return_arr = $return_json['response'];
                if (is_array($return_arr)) {
                    if ($return_arr['tanx_creative_get_response']['is_ok']) {
                        $db_data = array();
                        $db_data['adxStatus'] = $tanx_material_status_ary[$return_arr['tanx_creative_get_response']['result']['status']];
                        $db_data['uploadStatus'] = 1;
                        $db_data['level'] = $tanx_level_ary[$return_arr['tanx_creative_get_response']['result']['level']] ? $tanx_level_ary[$return_arr['tanx_creative_get_response']['result']['level']] : 0;
                        $db_data['reason'] = $return_arr['tanx_creative_get_response']['result']['refuse_cause'];
                        $db_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("advertId = {$item['adId']} and adxId = 3",$db_data);
                    } elseif ($return_arr['error_response']['code'] == 15) {
                        $db_data = array();
                        $db_data['uploadStatus'] = 0;
                        $db_data['reason'] = $return_arr['error_response']['sub_msg'];
                        $db_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("advertId = {$item['adId']} and adxId = 3",$db_data);
                    } else {
                        if ($i == 1) {
                            $i++;
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tanxMaterialStatus();
$obj->run();
?>
