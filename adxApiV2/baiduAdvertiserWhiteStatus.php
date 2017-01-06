<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/baiduConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\baiduConfig as baiduConfig;
use \library\base as base;

class baiduWhiteAdvertiserStatus extends base{

    public $appName = 'baiduWhiteAdvertiserStatus';

    public function run(){
        $this->baiduConfig = new baiduConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = array();
        $arr_db_info = $this->getWaitStatusAdvertiser("adxId = 11 and isWhiteUser = 0");
        if ($arr_db_info) {
            $total=count($arr_db_info);
            $idAry = array();
            foreach ($arr_db_info as $k => $item_db) {
                $accountId = $item_db['beheAccountId'];
                $idAry[] = $accountId;
                if (count($idAry) == 100 || ($total - 1) == $k) {
                    $arr_send_json = array();
                    $arr_send_json["advertiserIds"] = $idAry;
                    $arr_send_json["authHeader"]["dspId"] = $this->baiduConfig->baidu_dsp_id;
                    $arr_send_json["authHeader"]["token"] = $this->baiduConfig->baidu_token;
                    $str_json = json_encode($arr_send_json);
                    $str_return = $this->postCurl($this->baiduConfig->baidu_advertiser_white_status, $str_json, "json");
					
					// debug start
					$arr_return = array();
					$arr_return['response'] = array();
					$arr_return['response'][] = array('advertiserId'=>1,'state'=>0,'refuseReason'=>'','sellerAuditInfo'=>'','isWhiteUser'=>1);
					$arr_return['status'] = 0;
					$str_return = array('httpCode' => 200, 'response' => $arr_return);						
					$this->log("response:".json_encode($str_return));
					// debug end
					
                    if (!empty($str_return)) {
                        $arr_client_list = $str_return['response'];
                        foreach ($arr_client_list["response"] as $item_client) {
                            $isWhiteUser = $item_client["isWhiteUser"];
                            $update_data = array();
                            $advertiserId=$item_client["advertiserId"];
                            $update_data['isWhiteUser'] = $isWhiteUser;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveAdvertiser($advertiserId,11,$update_data);
                        }
                    }
                    $idAry=array();
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new baiduWhiteAdvertiserStatus();
$obj->run();
?>

