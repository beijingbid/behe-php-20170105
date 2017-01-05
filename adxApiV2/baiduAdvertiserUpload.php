<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/baiduConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\baiduConfig as baiduConfig;
use \library\base as base;

class baiduAdvertiserUpload extends base{

    public $appName = 'baiduAdvertiserUpload';

    public function run(){
        $this->baiduConfig = new baiduConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = $this->getAdvertiser(11,'99');
        $date = date("Y-m-d H:i:s");
        if ($arr_client_list) {
            foreach ($arr_client_list as $k => $account_item) {
                $request = array();
                $accountId = $account_item['beheAccountId'];
                $request[0]["advertiserId"] = $accountId;
                $request[0]["advertiserName"] = $account_item["companyName"];
                $request[0]["advertiserLiteName"] = $account_item["companyName"];
                $request[0]["siteName"] = $account_item["companyName"];
                $pos = strpos($account_item["companyUrl"], 'http');
                if ($pos !== false) {
                    $request[0]["siteUrl"] = $account_item["companyUrl"];
                } else {
                    $request[0]["siteUrl"] = "http://" . $account_item["companyUrl"];
                }
                $request_ary = array();
                $request_ary["authHeader"]["dspId"] = $this->baiduConfig->baidu_dsp_id;
                $request_ary["authHeader"]["token"] = $this->baiduConfig->baidu_token;
                $request_ary["request"] = $request;
                $request_json = json_encode($request_ary);
                if($account_item['uploadStatus'] == 0){
                    $str_return = $this->postCurl($this->baiduConfig->baidu_advertiser_add, $request_json, "json");
                }else{
                    $str_return = $this->postCurl($this->baiduConfig->baidu_advertiser_update, $request_json, "json");
                }
                if (!empty($str_return) && $str_return['response'] == '200') {
                    $arr_return = $str_return['response'];
                    if (!empty($arr_return)) {
                        if ($arr_return['status'] == '2') {
                            $update_data = array();
                            $update_data['reason'] = $arr_return['errors'][0]['message'];
                            $update_data['adxStatus'] = 99;
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = $date;
                            $this->saveAdvertiser($accountId,11,$update_data);
                        } else {
                            $update_data = array();
                            $update_data['reason'] = "";
                            $update_data['adxStatus'] = 0;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = $date;
                            $this->saveAdvertiser($accountId,11,$update_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new baiduAdvertiserUpload();
$obj->run();
?>

