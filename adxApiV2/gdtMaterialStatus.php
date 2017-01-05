<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/gdtConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\gdtConfig as gdtConfig;
use \library\base as base;

class gdtMaterialStatus extends base{

    public $appName = 'gdtMaterialStatus';

    public function run(){
        $this->gdtConfig = new gdtConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $str_dsp_id = $this->gdtConfig->gdt_dsp_id; //58123 620222 token12345678901
        $str_auth = $this->gdtConfig->gdt_token; //haoye12345678901
        $api_url = $this->gdtConfig->gdt_material_status;
        $allowAccountIdAry = $this->getAllowAdvertiser(49);
        $arr_db_info = $this->getWaitStatusMaterial("adxId = '49' and uploadStatus=1 and adxStatus=0");
        $i = 1;
        $total = count($arr_db_info);
        $behe_status = array('PREPARING' => 0, 'PENDING' => 0, 'APPROVED' => 1, 'REJECTED' => 2);
        $request = array();
        $offset = 0;
        foreach ($arr_db_info as $item_db) {
            $accountId = $allowAccountIdAry[$item_db["adAccountId"]];
            $creative_id = $item_db["advertId"];
            $orderId=$item_db['orderId'];
            $request[$offset]['advertiser_id'] = $accountId;
            $request[$offset]['creative_id'] = $creative_id;
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
                        $this->saveMaterial("adxId = '49' and advertId = {$return_item['data']['creative_id']}",$db_data);
                        if($behe_status[$return_item['data']['review_status']]==1&&$orderId>0){
                            order_queue($orderId);
                        }
                    } elseif ($return_item['code'] === 23001) {
                        $db_data = array();
                        $db_data['adxStatus'] = 99;
                        $db_data['uploadStatus'] = 0;
                        $db_data['reason'] = '';
                        $db_data['mtime'] = date("Y-m-d H:i:s");
                        $this->saveMaterial("adxId = '49' and advertId = {$return_item['data']['creative_id']}",$db_data);
                    }
                }
            }
            $offset = 0;
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new gdtMaterialStatus();
$obj->run();
?>

