<?php
namespace adxApiV2;

error_reporting(1);
define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/hzengConfig.php';
require_once APP_PATH . '/library/base.php';
require_once APP_PATH . '/library/hzengClient/client/HzSdk.php';
require_once APP_PATH . '/library/hzengClient/client/request/AdvertiserQueryQualificationRequest.php';

use adxApiV2\config\hzengConfig as hzengConfig;
use \library\base as base;

class hzengAdvertiserStatus extends base{

    public $appName = 'hzengAdvertiserStatus';

    public function run(){
        $this->hzengConfig = new hzengConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = array();
        $arr_client_sync_old_list = array();
        $arr_db_info = $this->getWaitStatusAdvertiser('adxId = 25 and adxStatus=0');
        if ($arr_db_info) {

            $total = count($arr_db_info);
            $idAry = array();
            foreach ($arr_db_info as $k => $item_db) {
                $accountId = $item_db['beheAccountId'];
                $idAry[] = (int) $accountId;
                if (count($idAry) == 1 || ($total - 1) == $k) {
                    $client = new \AdxClient();
                    $client->dspId = $this->hzengConfig->hz_dsp_id;
                    $client->token = $this->hzengConfig->hz_token;
                    $req = new \AdvertiserQueryQualificationRequest();
                    $req->addEntity($idAry);
                    $arr_client_list = $client->execute($req);
                    if (is_array($arr_client_list) && $arr_client_list['status'] === 0) {
                        if (empty($arr_client_list["response"])) {
                            $update_data['adxStatus'] = 99;
                            $update_data['uploadStatus'] = 0;
                            $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 25");
                        } else {
                            foreach ($arr_client_list["response"] as $item_client) {
                                $state = $item_client["state"];
                                $advertiserId = $item_client["advertiserId"];
                                $reason = $item_client["refuseReason"];
                                $update_data = array();
                                $update_data['mtime'] = date("Y-m-d H:i:s");
                                if ($state == 0) {
                                    $update_data['adxStatus'] = 1;
                                    $this->saveAdvertiser("beheAccountId = {$advertiserId} and adxId = 25");
                                } else if ($state == 2) {
                                    $update_data['adxStatus'] = 2;
                                    $this->saveAdvertiser("beheAccountId = {$advertiserId} and adxId = 25");
                                } else if ($state == 3 || $state == 1) {
                                    $update_data['adxStatus'] = 0;
                                    $this->saveAdvertiser("beheAccountId = {$advertiserId} and adxId = 25");
                                }
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

$obj = new hzengAdvertiserStatus();
$obj->run();
?>

