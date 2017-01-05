<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/tencentConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tencentConfig as tencentConfig;
use \library\base as base;

class tencentMaterialStatus extends base{

    public $appName = 'tencentMaterialStatus';

    public function run(){
        $this->tencentConfig = new tencentConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = array();
        $arr_client_sync_old_list = array();
        $arr_db_info = $this->getWaitStatusMaterial('adxId = 5  and adxStatus=0 and orderId > 0 group by orderId');
        if ($arr_db_info) {
            foreach ($arr_db_info as $k => $item_db) {
                $arr_send_ary = array();
                $arr_send_ary[0]=$item_db['advertId'];
                $str_json = json_encode($arr_send_ary);
                $str_post = "dsp_id=" . $this->tencentConfig->tencent_dsp_id . "&token=" . $this->tencentConfig->tencent_token . "&dsp_order_id_info=" . $str_json;
                $str_return = $this->postCurl($this->tencentConfig->tencent_material_status, $str_post);
                if (!empty($str_return)) {
                    $arr_client_list = $str_return['response'];
                    if ($arr_client_list['ret_code'] == 0) {
                        if ($arr_client_list["ret_msg"]['records']) {
                            foreach ($arr_client_list["ret_msg"]['records'] as $file_key => $targetUrlItem) {
                                foreach ($targetUrlItem as $item) {
                                    $update_data = array();
                                    $status = $item['status'];
                                    if ($status == "审核通过") {
                                        $update_data['adxStatus'] = 1;
                                    } else if ($status == "审核不通过") {
                                        $update_data['adxStatus'] = 2;
                                    } else {
                                        continue;
                                    }
                                    $update_data['reason'] = $targetUrlItem['reason'] ? $targetUrlItem['reason'] : '';
                                    $update_data['mtime'] = date("Y-m-d H:i:s");
                                    $this->saveMaterial("advertId = {$item_db['advertId']} and adxId = 5 ",$update_data);
                                    if($update_data['adxStatus'] == 1){
                                        order_queue($item_db['orderId']);
                                    }
                                }
                            }
                        } else {
                            $update_data = array();
                            $update_data['uploadStatus'] = 0;
                            $update_data['reason'] = '';
                            $update_data['adxStatus'] = 99;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId = {$item_db['advertId']} and adxId = 5 ",$update_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tencentMaterialStatus();
$obj->run();
?>