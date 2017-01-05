<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/iqyConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\iqyConfig as iqyConfig;
use \library\base as base;

class iqyAdvertiserStatus extends base{

    public $appName = 'iqyAdvertiserStatus';

    public function run(){
        $this->iqyConfig = new iqyConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $dsp_token = $this->iqyConfig->iqy_dsp_token;
        $statusResult = $this->getWaitStatusAdvertiser("adxId =55 and adxStatus=0 and uploadStatus=1");
        if (count($statusResult) > 0) {
            foreach ($statusResult as $key => $value) {
                $aid = $value['beheAccountId'];
                $queryUrl = "http://220.181.184.220/upload/api/advertiser?dsp_token=" . $dsp_token . "&ad_id=" . $aid;
                $return_str = $this->getCurl($queryUrl);
                $queryResult = json_decode($return_str);
                if (!empty($queryResult)) {
                    if ($queryResult->code == 0) {
                        if ($queryResult->status == "PASS") {
                            $update_data = array();
                            $update_data['adxStatus'] = 1;
                            $update_data['reason'] = "";
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser($value['beheAccountId'],55,$update_data);
                        } else if ($queryResult->status == "UNPASS") {
                            $update_data = array();
                            $update_data['adxStatus'] = 2;
                            $update_data['reason'] = "";
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser($value['beheAccountId'],55,$update_data);
                        }
                    } elseif ($queryResult->code == 4001) {
                        $update_data = array();
                        $update_data['uploadStatus'] = 0;
                        $update_data['adxStatus'] = 99;
                        $update_data['reason'] = "";
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveAdvertiser($value['beheAccountId'],55,$update_data);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new iqyAdvertiserStatus();
$obj->run();
?>
