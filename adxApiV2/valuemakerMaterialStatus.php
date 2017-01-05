<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/valuemakerConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\valuemakerConfig as valuemakerConfig;
use \library\base as base;

class valuemakerMaterialStatus extends base{

    public $appName = 'valuemakerMaterialStatus';

    public function run(){
        $this->valuemakerConfig = new valuemakerConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $ay_str_user_name = $this->valuemakerConfig->valuemaker_dsp_id;
        $ay_str_token = $tihs->valuemakerConfig->valuemaker_token;
        $str_auth = $ay_str_user_name . ":" . $ay_str_token;
        $arr_material_list = $this->getWaitStatusMaterial(27);
        $date = date('Y-m-d H:i:s');
        if ($arr_material_list) {
            foreach ($arr_material_list as $k => $item) {
                $materialId = $item['adId'];
                $orderId=$item['orderId'];
                if ($item['fileType'] == 1) {
                    $str_api_url = $this->valuemakerConfig->valuemaker_material_status . "?id={$item['adId']}";
                } else {
                    $str_api_url = $this->valuemakerConfig->valuemaker_material_video_status . "?id={$item['adId']}";
                }
                $str_return = $this->getCurl($str_api_url, "", $str_auth);
                if (!empty($str_return)) {
                    $arr_material_status = json_decode($str_return, true);
                    $state = $arr_material_status["status"];
                    $str_sql = "";
                    if ($state == "2") {
                        $updateData['reason'] = '';
                        $updateData['mtime'] = $date;
                        $updateData['adxStatus'] = 1;
                    } elseif ($state == "3") {
                        $updateData['reason'] = '';
                        $updateData['mtime'] = $date;
                        $updateData['adxStatus'] = 2;
                    } else if ($state == "1") {
                        $updateData['reason'] = '';
                        $updateData['mtime'] = $date;
                        $updateData['adxStatus'] = 0;
                    }
                    if (!empty($updateData)) {
                        $this->saveMaterial("adxId = 27 and advertId = " . $materialId,$updateData);
                        if($state == 2 && $orderId > 0){
                            order_queue($orderId);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new valuemakerMaterialStatus();
$obj->run();
?>

