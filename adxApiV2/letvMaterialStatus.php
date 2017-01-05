<?php

namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/letvConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\letvConfig as letvConfig;
use \library\base as base;

class letvMaterialStatus extends base {
    public $appName = "letvMaterialStatus";
    public function run() {
        $this->letvConfig = new letvConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $materialList = $this->getWaitStatusMaterial("adxId=12 and uploadStatus=1 and adxStatus=0");
        if (!empty($materialList)) {

            foreach ($materialList as $key => $uploadInfo) {
                $arr_materialurl = array();
                $fileType = $uploadInfo['fileType'];
                if ($fileType == 2) {
                    $arr_materialurl[] = $this->config['video_cdn_domain'] . $uploadInfo['fileUrl'];
                } else {
                    $arr_materialurl[] = $this->config['banner_cdn_domain'] . $uploadInfo["fileUrl"];
                }
                
                $request = array();
                $request['dspid'] = $this->letvConfig->dspId;
                $request['token'] = $this->letvConfig->token;
                $request['adurl'] = $arr_materialurl;

                $response = $this->postCurl($this->letvConfig->materialStatusApi, json_encode($request), 'json');
                if ($response['response']['result'] === 0 &&!empty($response['response']['message']['records'])) {
                    foreach ($response['response']['message']['records'] as $statusItem) {
                        if (isset($this->letvConfig->materialStatus[$statusItem['result']])) {
                            $update_data = array();
                            $update_data['reason'] = $statusItem['reason'];
                            $update_data['adxStatus'] = $this->letvConfig->materialStatus[$statusItem['result']];
                            $update_data['uploadStatus'] = 1;
                            $update_data['exAdId'] = $statusItem['adid'];
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("id={$uploadInfo['id']}", $update_data);
                            if(in_array($update_data['adxStatus'],array(1,2))){
                                $this->event_queue($uploadInfo['orderId'],'materialStatus');
                            }
                        }
                    }

                }else{
                    $update_data = array();
                    $update_data['reason'] ='';
                    $update_data['adxStatus'] =99;
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("id={$uploadInfo['id']}", $update_data);
                }

                $this->log(json_encode($response), $this->appName);
            }
        }
        $this->destoryPid($this->appName);
    }

}

$obj = new letvMaterialStatus();
$obj->run();
?>
