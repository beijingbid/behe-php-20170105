<?php

namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/youkuConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\youkuConfig as ykConfig;
use \library\base as base;

class ykMaterialStatus extends base {

    public $appName = "ykMaterialStatus";

    public function run() {
        $this->ykConfig = new ykConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $materialList = $this->getWaitStatusMaterial("adxId = 9 and uploadStatus=1 and adxStatus=0");
        if (!empty($materialList)) {
            foreach ($materialList as $key => $uploadInfo) {
                $arr_materialurl = array();
                $fileType = $uploadInfo['fileType'];
                if ($fileType == 2) {
                    $arr_materialurl[] = $uploadInfo['fileUrl'];
                } else {
                    if(strpos($uploadInfo["fileUrl"], $this->config['banner_cdn_domain'])===false){
                        $arr_materialurl[] = $this->config['banner_cdn_domain'] . $uploadInfo["fileUrl"];
                    }else{
                        $arr_materialurl[] =  $uploadInfo["fileUrl"];
                    }
                }
               // $this->log($arr_materialurl[0]);
                $request = array();
                $request['dspid'] = $this->ykConfig->dspId;
                $request['token'] = $this->ykConfig->token;
                $request['materialurl'] = $arr_materialurl;
                $this->log(json_encode($request));
                $response = $this->postCurl($this->ykConfig->materialStatusApi, json_encode($request), 'json');
                if ($response['response']['result'] === 0 && !empty($response['response']['message']['records'])) {
                    foreach ($response['response']['message']['records'] as $statusItem) {
                        if (isset($this->ykConfig->materialStatus[$statusItem['result']])) {
                            $update_data = array();
                            $update_data['reason'] = $statusItem['reason'];
                            $update_data['adxStatus'] = $this->ykConfig->materialStatus[$statusItem['result']];
                            $update_data['uploadStatus'] = 1;
                            if($fileType==1){
                                //$update_data['fileUrl']=str_replace($this->config['banner_cdn_domain'], '', $arr_materialurl[0]);
                            }
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("id={$uploadInfo['id']}", $update_data);
                            if(in_array($update_data['adxStatus'],array(1,2))){
                                $this->event_queue($uploadInfo['orderId'],'materialStatus');
                            }
                        }
                    }
                }elseif($response['response']['result'] === 0 &&$response['response']['total']==0){
                    $update_data = array();
                    $update_data['adxStatus'] = 99;
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

$obj = new ykMaterialStatus();
$obj->run();
?>

