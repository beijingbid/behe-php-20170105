<?php

namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/youkuConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\youkuConfig as ykConfig;
use \library\base as base;

class ykAdvertiserStatus extends base {

    public $appName = "ykAdvertiserStatus";

    public function run() {
        $this->ykConfig = new ykConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $advertiserList = $this->getWaitStatusAdvertiser("adxId=9 and uploadStatus=1 and adxStatus=0");
        if (!empty($advertiserList)) {
            /*
             * {
              "dspid":"11268",
              "token":"92205dff8f9d48e1b7a26b0b88af7dc1",
              "advertiser":"合一网络"
              }
             */
            foreach ($advertiserList as $key => $uploadInfo) {
                $companyName = $uploadInfo['companyName'];
                $request = array();
                $request['dspid'] = $this->ykConfig->dspId;
                $request['token'] = $this->ykConfig->token;
                $request['advertiser'] = $companyName;
                $response = $this->postCurl($this->ykConfig->advertiserStatusApi, json_encode($request), 'json');
                if ($response['response']['result'] === 0 && !empty($response['response']['message'])) {
                    if (isset($this->ykConfig->status[$response['response']['message']['state']])) {
                        $update_data = array();
                        if (isset($this->ykConfig->status[$response['response']['message']['refusereason']])) {
                            $update_data['reason'] = $this->ykConfig->status[$response['response']['message']['refusereason']];
                        } else {
                            $update_data['reason'] = "";
                        }
                        $update_data['adxStatus'] = $this->ykConfig->status[$response['response']['message']['state']];
                        $update_data['uploadStatus'] = 1;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveAdvertiser(" beheAccountId = '{$uploadInfo['beheAccountId']}'",9, $update_data);
                    }
                }

                $this->log(json_encode($response), $this->appName);
            }
        }
        $this->destoryPid($this->appName);
    }

}

$obj = new ykAdvertiserStatus();
$obj->run();
?>

