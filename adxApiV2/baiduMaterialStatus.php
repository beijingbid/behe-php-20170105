<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/baiduConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\baiduConfig as baiduConfig;
use \library\base as base;

class baiduMaterialStatus extends base{

    public $appName = 'baiduMaterialStatus';

    public function run(){
        $this->baiduConfig = new baiduConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_return = array();
        $arr_client_sync_old_list = array();
        $arr_db_info = $this->getWaitStatusMaterial("adxId = 11  and adxStatus=0");
        if ($arr_db_info) {
            $idAry = array();
            $orderIdAry=array();
            $total = count($arr_db_info);
            foreach ($arr_db_info as $k => $item_db) {
                $adId = $item_db['advertId'];
                $idAry[] = $adId;
                $orderIdAry[$adId]=$item_db['orderId'];
                if (count($idAry) == 100 || ($total - 1) == $k) {
                    $arr_send_json = array();
                    $arr_send_json["creativeIds"] = $idAry;
                    $arr_send_json["authHeader"]["dspId"] = $this->baiduConfig->baidu_dsp_id;
                    $arr_send_json["authHeader"]["token"] = $this->baiduConfig->baidu_token;
                    $str_json = json_encode($arr_send_json);
                    $str_return = $this->postCurl($this->baiduConfig->baidu_material_status, $str_json, "json");
                    if (!empty($str_return)) {
                        $arr_return = $str_return['response'];
                        foreach ($arr_return["response"] as $item_client) {
                            $state = $item_client["state"];
                            $creativeId = $item_client["creativeId"];
                            $reason = implode(',', $item_client["refuseReason"]);
                            $level = $item_client['is_vulgar'];
                            $update_data = array();
                            $update_data['reason'] = $reason;
                            $update_data['level'] = $level;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            if ($state == 0) {
                                $update_data['adxStatus'] = 1;
                                $this->saveMaterial("advertId = {$creativeId} and adxId = 11",$update_data);
                                if($orderIdAry[$creativeId]>0){
                                   $this->event_queue($orderIdAry[$creativeId],'materialStatus');
                                }
                            } else if ($state == 2) {
                                $update_data['adxStatus'] = 2;
                                $this->saveMaterial("advertId = {$creativeId} and adxId = 11",$update_data);
                            } else if ($state == 3 || $state == 1) {
                                $update_data['adxStatus'] = 0;
                                $this->saveMaterial("advertId = {$creativeId} and adxId = 11",$update_data);
                            }
                        }
                    }
                    $idAry = array();
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new baiduMaterialStatus();
$obj->run();
?>
