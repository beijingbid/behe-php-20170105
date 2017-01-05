<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/gdtConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\gdtConfig as gdtConfig;
use \library\base as base;

class gdtAdvertiserStatus extends base{

    public $appName = 'gdtAdvertiserStatus';

    public function run(){
        $this->gdtConfig = new gdtConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $str_dsp_id = $this->gdtConfig->gdt_dsp_id; //58123 620222 token12345678901
        $str_auth = $this->gdtConfig->gdt_token; //haoye12345678901
        $api_url = $this->gdtConfig->gdt_advertiser_status;
        $arr_db_info = $this->getWaitStatusAdvertiser("adxId = '49' and uploadStatus = 1 and adxStatus = 0");
        $i = 1;
        $total = count($arr_db_info);
        $behe_status = array('PREPARING' => 0, 'PENDING' => 0, 'APPROVED' => 1, 'REJECTED' => 2);
        $request = array();
        foreach ($arr_db_info as $item_db) {

            $accountId = $item_db["beheAccountId"];
            $request[] = $accountId;
            if ($i % 20 == 0 || $i >= $total) {
                $request_arr = array();
                $request_arr['data'] = $request;
                $request_json = json_encode($request_arr);
                $str_return = $this->postCurl($api_url, $request_json, $str_auth);
                $arr_return = $str_return['response'];
                $request = array();
                if ($arr_return['data']) {
                    foreach ($arr_return['data'] as $return_item) {
                        if ($return_item['code'] === 0) {
                            $db_data = array();
                            $db_data['adxStatus'] = $behe_status[$return_item['data']['review_status']];
                            $db_data['reason'] = $return_item['data']['review_msg'];
                            $db_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveAdvertiser("adxId = '49' and beheAccountId = {$return_item['data']['advertiser_id']}",$db_data);
                        } elseif ($return_item['code'] === 23001) {
                            $db_data = array();
                            $db_data['adxStatus'] = 99;
                            $db_data['uploadStatus'] = 0;
                            $db_data['reason'] = '';
                            $db_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveAdvertiser("adxId = '49' and beheAccountId = {$return_item['data']['advertiser_id']}",$db_data);
                        }
                    }
                }
            }
            $i++;
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new gdtAdvertiserStatus();
$obj->run();
?>
