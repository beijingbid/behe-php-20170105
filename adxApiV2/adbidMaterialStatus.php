<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/adbidConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\adbidConfig as adbidConfig;
use \library\base as base;

class adbidMaterialStatus extends base{

    public $appName = 'adbidMaterialStatus';

    public function run(){
        $this->adbidConfig = new adbidConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $str_user_name = $this->adbidConfig->adbid_dsp_id;
        $str_token = $this->adbidConfig->adbid_token;
        $str_api_file_status = $this->adbidConfig->adbid_file_status;
        $str_api_material_status = $this->adbidConfig->adbid_creative_status;
        $str_auth = base64_encode($str_user_name . ":" . $str_token);
        $str_auth = $str_user_name . ":" . $str_token;
        $arr_material_old_list = array();
        $arr_db_center_material = $this->getWaitStatusMaterial("adxId = 43 and adxStatus = 0 and exAdId <> '' and uploadStatus = 1 order by id desc");
        $date = date("Y-m-d H:i:s");
        foreach ($arr_db_center_material as $item_db) {
            $materialId = $item_db["exAdId"];
            $fileId = $item_db["exFileId"];

            $orderId = $item_db["orderId"];
            $param = "/$fileId.json";
            $str_return_file = $this->getCurl($str_api_file_status . $param, "", $str_auth);
            $arr_return_file = json_decode($str_return_file, true);
            $db_data = array();
            if ($arr_return_file['status'] == 1) {
                $param = "/$materialId.json";
                $str_return_material = $this->getCurl($str_api_material_status . $param, "json", $str_auth);
                $arr_return_material = json_decode($str_return_material, true);
                if ($arr_return_material['result']['id']) {
                    $db_data['exFileStatus'] = $arr_return_file['status'];
                    $db_data['adxStatus'] = $arr_return_material['result']['auditStatus'];
                    $this->saveMaterial("adxId = 43 and id='{$item_db['id']}'",$db_data);
                    if ($orderId > 0 && $arr_return_material['result']['auditStatus'] == 1) {
                        order_queue($orderId);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new adbidMaterialStatus();
$obj->run();
?>

