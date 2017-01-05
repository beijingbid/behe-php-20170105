<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/iqyConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\iqyConfig as iqyConfig;
use \library\base as base;

class iqyMaterialStatus extends base{

    public $appName = 'iqyMaterialStatus';

    public function run(){
        $this->iqyConfig = new iqyConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadUrl = $config->iqy_material_status;
        $dsp_token = $config->iqy_dsp_token;
        $statusResult = $this->getWaitStatusMaterial("adxId = 55 and exFileId > 0 and adxStatus = 0");
        if (count($statusResult) > 0) {
            foreach ($statusResult as $key => $value) {
                $m_id = $value['exFileId'];
                $orderId=$value['orderId'];
                $queryUrl = $uploadUrl . "?dsp_token=" . $dsp_token . "&m_id=" . $m_id;
                $return_str = file_get_contents($queryUrl);
                $queryResult = json_decode($return_str);
                if ($queryResult->code == 0) {
                    if ($queryResult->status == "COMPLETE") {
                        $update_data = array();
                        $update_data['adxStatus'] = 1;
                        $update_data['reason'] = "";
                        $update_data['exAdId'] = $queryResult->tv_id;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId=55 and id = " . $value['id'],$update_data);
                        if($orderId>0){
                            order_queue($orderId);
                        }
                    } else if ($queryResult->status == "AUDIT_UNPASS") {
                        $update_data = array();
                        $update_data['adxStatus'] = 2;
                        $update_data['reason'] = "";
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId=55 and id = " . $value['id'],$update_data);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new iqyMaterialStatus();
$obj->run();
?>
