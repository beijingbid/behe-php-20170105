<?php
namespace adxApiV2;

error_reporting(1);
define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/hzengConfig.php';
require_once APP_PATH . '/library/base.php';
require_once APP_PATH . '/library/hzengClient/client/HzSdk.php';
require_once APP_PATH . '/library/hzengClient/client/request/CreativeQueryAuditStateRequest.php';

use adxApiV2\config\hzengConfig as hzengConfig;
use \library\base as base;

class hzengMaterialStatus extends base{

    public $appName = 'hzengMaterialStatus';

    public function run(){
        $this->hzengConfig = new hzengConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = array();
        $arr_db_info = $this->getWaitStatusMaterial("adxId = 25 and adxStatus in (0)");
        if ($arr_db_info) {

            $total = count($arr_db_info);
            $idAry = array();
            foreach ($arr_db_info as $k => $item_db) {
                $adId = $item_db['advertId'];
                $orderId = $item_db['orderId'];
                $idAry[] = (int) $adId;
                if (count($idAry) == 1 || ($total - 1) == $k) {
                    $client = new \AdxClient();
                    $client->dspId = $config->hz_dsp_id;
                    $client->token = $config->hz_token;
                    $req = new \CreativeQueryAuditStateRequest();
                    $req->addEntity($idAry);
                    $arr_client_list = $client->execute($req);
                    if (is_array($arr_client_list) && $arr_client_list['status'] == 0) {
                        if (empty($arr_client_list["response"])) {
                            $update_data = array();
                            $update_data['adxStatus'] = 99;
                            $update_data['uploadStatus'] = 0;
                            $this->saveMaterial("advertId in (" . implode(',', $idAry) . ") and adxId=25",$update_data);
                        } else {
                            foreach ($arr_client_list["response"] as $item_client) {
                                $state = $item_client["state"];
                                $creativeId = $item_client['creativeId'];
                                $update_data = array();
                                $update_data['mtime'] = date("Y-m-d H:i:s");
                                if ($state == 0) {
                                    $update_data['adxStatus'] = 1;
                                    $this->saveMaterial("advertId = {$creativeId} and adxId=25", $update_data);
                                    if ($orderId > 0) {
                                        order_queue($orderId);
                                    }
                                } else if ($state == 2) {
                                    if (is_array($item_client["refuseReason"])) {
                                        $reason = implode('|', $item_client["refuseReason"]);
                                    } else {
                                        $reason = $item_client["refuseReason"];
                                    }

                                    $update_data['adxStatus'] = 2;
                                    $update_data['reason'] = $reason;
                                    $this->saveMaterial("advertId = {$creativeId} and adxId=25", $update_data);
                                    if ($orderId > 0) {
                                        order_queue($orderId);
                                    }
                                } else if ($state == 3 || $state == 1) {
                                    $update_data['adxStatus'] = 0;
                                    $this->saveMaterial("advertId = {$creativeId} and adxId=25", $update_data);
                                }
                                
                            }
                        }
                    }
                    usleep(1000);
                    $idAry = array();
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new hzengMaterialStatus();
$obj->run();
?>

