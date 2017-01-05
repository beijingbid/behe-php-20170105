<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/tanxConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tanxConfig as tanxConfig;
use \library\base as base;

class tanxAdvertiserUpload extends base{

    public $appName = 'tanxAdvertiserUpload';

    public function run(){
        $this->tanxConfig = new tanxConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = $this->getAdvertiser(3,99,'0,1');
        $total = count($arr_client_list);
        $date = date("Y-m-d H:i:s");
        $advertiser_add_arr = array();
        $advertiser_add_name_arr = array();
        $i = 0;
        if ($arr_client_list) {
            foreach ($arr_client_list as $k => $item) {
                $businessLicenceName=$item['qualificationInfo']['businessLicenceName']?$item['qualificationInfo']['businessLicenceName']:$item['companyName'];
                $accountId = $item['beheAccountId'];
                $advertiser_add_arr[$i]['advertiser_name'] = $businessLicenceName;
                $advertiser_add_arr[$i]['user_type'] = 2;
                if (count($advertiser_add_arr) == 1 || $k == ($total - 1)) {
                    $arr_send_json = array();
                    $arr_send_json['advertisers'] = json_encode($advertiser_add_arr);
                    $arr_send_json['member_id'] = $this->tanxConfig->tanx_memberId;
                    $arr_send_json['method'] = $this->tanxConfig->tanx_api_advertiser_add_url;
                    $arr_send_json['format'] = 'json';
                    $arr_send_json["v"] = "2.0";
                    $arr_send_json['sign_method'] = 'md5';
                    $arr_send_json['app_key'] = $this->tanxConfig->tanx_appkey;
                    list($s1, $s2) = explode(' ', microtime());
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
                    if (is_array($return_arr) && $return_arr['tanx_qualification_advertiser_add_response']['is_success']) {
                        foreach ($return_arr['tanx_qualification_advertiser_add_response']['advertiser_list']['advertiser_dto'] as $adlist) {
                            $exAccountId = $adlist['advertiser_id'];
                            $db_data = array();
                            $db_data['exAccountId'] = $adlist['advertiser_id'];
                            $db_data['adxStatus'] = 0;
                            $db_data['uploadStatus'] = 1;
                            $db_data['uploadName'] = $businessLicenceName;
                            $db_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 3",$db_data);
                        }
                    }
                    $advertiser_add_arr = array();
                    $i = 0;
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tanxAdvertiserUpload();
$obj->run();
?>
