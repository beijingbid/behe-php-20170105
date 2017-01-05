<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/tencentConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tencentConfig as tencentConfig;
use \library\base as base;

class tencentAdvertiserStatus extends base{

    public $appName = 'tencentAdvertiserStatus';

    public function run(){
        $this->tencentConfig = new tencentConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = array();
        $arr_client_sync_old_list = array();
        $arr_db_info = $this->getWaitStatusAdvertiser("adxId = 5  and adxStatus = 0");
        if ($arr_db_info) {
            foreach ($arr_db_info as $k => $item_db) {
                $arr_send_ary = array();
                $arr_send_ary[0] = $item_db['companyName'];
                $str_json = json_encode($arr_send_ary);
                $str_post = "dsp_id=" . $this->tencentConfig->tencent_dsp_id . "&token=" . $this->tencentConfig->tencent_token . "&names=" . $str_json;
                $str_return = $this->postCurl($this->tencentConfig->tencent_advertiser_status, $str_post);
                if (!empty($str_return)) {
                    $arr_client_list = $str_return['response'];
                    if ($arr_client_list['ret_code'] == 0) {
                        if ($arr_client_list["ret_msg"]) {
                            foreach ($arr_client_list["ret_msg"] as $str_client_name => $item_client) {
                                $verify_status = $item_client["verify_status"];
                                $audit_info = $item_client["audit_info"] ? $item_client["audit_info"] : '';
                                $update_data = array();
                                if ($verify_status == "通过") {
                                    $update_data['adxStatus'] = 1;
                                } else if ($verify_status == "不通过") {
                                    $update_data['adxStatus'] = 2;
                                }else{
                                    continue;
                                }
                                $update_data['reason'] = $audit_info;
                                $update_data['mtime'] = date("Y-m-d H:i:s");
                                $this->saveAdvertiser("beheAccountId = {$item_db['beheAccountId']} and adxId=5",$update_data);
                            }
                        } else {
                            $update_data = array();
                            $update_data['uploadStatus'] = 0;
                            $update_data['reason'] = '';
                            $update_data['adxStatus'] = 99;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveAdvertiser("beheAccountId = {$item_db['beheAccountId']} and adxId=5",$update_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tencentAdvertiserStatus();
$obj->run();
?>
