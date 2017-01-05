<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/allyesConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\allyesConfig as allyesConfig;
use \library\base as base;

class allyesMaterialStatus extends base{

    public $appName = 'allyesMaterialStatus';

    public function run(){
        $this->allyesConfig = new allyesConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $ay_str_user_name = $this->allyesConfig->allyes_dsp_id;
        $ay_str_token = $this->allyesConfig->allyes_token;
        $ay_str_api_status = $this->allyesConfig->allyes_material_status;
        $ay_str_auth = $ay_str_user_name . ":" . $ay_str_token;
        $arr_material_list = $this->getWaitStatusMaterial("adxId = 15 and adxStatus = 0");
        $date = date('Y-m-d H:i:s');
        if ($arr_material_list) {
            foreach ($arr_material_list as $k => $item) {
                $materialId = $item['adId'];
                $orderId = $item['orderId'];
                if ($item['fileType'] == 1) {
                    $str_api_url = $ay_str_api_status . "?id={$item['exAdId']}";
                    $str_return = $this->getCurl($str_api_url, "", $ay_str_auth);
                    if (!empty($str_return)) {
                        $arr_material_status = json_decode($str_return, true);
                        $state = $arr_material_status["status"];
                        $str_sql = "";
                        if ($state == "9") {
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 1;
                        } elseif ($state == "-2") {
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 2;
                        } else if ($state == "-1") {
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 0;
                        } elseif ($arr_material_status["errors"][0] == '202171') {
                            $str_sql = "DELETE FROM `again_center`.`center_material` WHERE `center_material`.`id` = " . $item['id'];
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 1;
                        }
                        if(!empty($updateData)):
                            $this->saveMaterial("adxId = 15 and advertId = " . $materialId,$updateData);
                            if ($orderId > 0 && $state == 9) {
                                order_queue($orderId);
                            }
                        endif;
                    }
                } else {
                    $str_api_url = $this->allyesConfig->allyes_material_video_status . "?id={$item['exAdId']}";
                    $str_return = $this->getCurl($str_api_url, "", $ay_str_auth);
                    if (!empty($str_return)) {
                        $arr_material_status = json_decode($str_return, true);
                        $state = $arr_material_status["status"];
                        $str_sql = "";
                        if ($state == "12") {
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 1;
                        } elseif ($state == "-2") {
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 2;
                        } else if ($state == "-1") {
                            $updateData['reason'] = '';
                            $updateData['mtime'] = $date;
                            $updateData['adxStatus'] = 0;
                        }
                        if(!empty($updateData)):
                            $this->saveMaterial("adxId = 15 and advertId = " . $materialId,$updateData);
                            if ($orderId > 0 && $state == 12) {
                                order_queue($orderId);
                            }
                        endif;
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new allyesMaterialStatus();
$obj->run();
?>

