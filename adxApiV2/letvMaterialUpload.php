<?php

namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/letvConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\letvConfig as letvConfig;
use \library\base as base;

class letvMaterialUpload extends base {

    public $appName = "letvMaterialUpload";

    public function run() {
        $this->letvConfig = new letvConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadVideoMaterialList = $this->getVideoMaterial(12, 99, "0,1");
        $allowAccountIdAry = $this->getAllowAdvertiser(12);
        if (!empty($uploadVideoMaterialList)) {

            foreach ($uploadVideoMaterialList as $k => $item) {

                $adAccountId = $item['adAccountId'];
                $behe_type = $this->config['file_type'];
                $fileType = 2;
                $durationType = $item['duration'];
                if (!isset($this->config['duration_type_ary'][$durationType])) {
                    continue;
                }
                $beheAccountId = $allowAccountIdAry[$item['adAccountId']];
                $sql = "select companyName,companyCateId,companySubCateId from behe_account where beheAccountId={$beheAccountId}";
                $accountInfo = $this->select("again_main", $sql, true);
                if (!$accountInfo) {
                    $this->log("{$adAccountId}广告主不存在",$this->appName);
                    continue;
                }

                $companyName = $accountInfo[0]['companyName'];
                $sql = "select * from ig_industry_letv where xId='{$accountInfo[0]['companyCateId']}'";
                $industryInfo = $this->select("again_info", $sql, true);
                if (!$industryInfo) {
                    $this->log("{$adAccountId}{$accountInfo[0]['companyCateId']}行业不存在",$this->appName);

                    continue;
                }

                $duration = $this->config['duration_type_ary'][$durationType];
                $fileUrl=$this->config['video_cdn_domain'] . $item["fileUrl"];
                $orderId = $item['orderId'];
                $goUrl = $item["goUrl"];
                $monitorUrl = $item["turl"];
                if($monitorUrl=='http://'){
                    $monitorUrl='';
                }

                $industry = $industryInfo[0]['sId'];
                $display = array(0, 1, 2, 3, 4, 5, 6, 7);
                $request = array();
                //$request['ad'][0]["name"] = $item['name'];
                $request['ad'][0]["url"] = $fileUrl;
                $request['ad'][0]["landingpage"] = array($goUrl);
                $request['ad'][0]["monitor"][] =$monitorUrl;
                $request['ad'][0]["advertiser"] = $companyName;
                $request['ad'][0]["startdate"] = date('Y-m-d');
                $request['ad'][0]["enddate"] = date("Y-m-d", strtotime("+1 years"));
                $request['ad'][0]["type"] = $behe_type[$item['type']];
                $request['ad'][0]["media"] = array(1, 2, 3);
                $request['ad'][0]["duration"] = $duration;
                $request['ad'][0]["industry"] = $industry;
                $request['ad'][0]["display"] = $display;
                $request['token'] = $this->letvConfig->token;
                $request['dspid'] = $this->letvConfig->dspId;
               
                $request_json=json_encode($request);
                $response = $this->postCurl($this->letvConfig->materialUploadApi, $request_json, 'json');
                $this->log(json_encode($response), $this->appName);
                $this->log($request_json, $this->appName);
                if (isset($response['response']['result'])) {
                    $update_data = array();
                    $update_data['orderId'] = $orderId;
                    $update_data['accountId'] = $adAccountId;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $update_data['reason'] = "";
                    $update_data['fileType'] = 2;
                    $update_data['fileUrl'] = $item['fileUrl'];
                    if (empty($response['response']['message'])) {
                        $update_data['adxStatus'] = 0;
                        $update_data['uploadStatus'] = 1;
                    } else {
                        $errorCode = 0;
                        foreach ($response['response']['message'] as $code => $item_message) {
                            $errorCode = $code;
                        }
                        if (isset($this->letvConfig->errorConfig[$errorCode])) {
                            $update_data['reason'] = $this->letvConfig->errorConfig[$errorCode];
                            $this->log($item['id'] . $update_data['reason'], $this->appName);
                        }
                        $update_data['adxStatus'] = 2;
                        $update_data['uploadStatus'] = 0;
                    }
                    $this->saveMaterial("adId={$item['id']} and adxId=12", $update_data,12);
               }
            }
        }


        $uploadBannerMaterialList = $this->getBannerMaterial(12, 99, "0,1");
        if (!empty($uploadBannerMaterialList)) {

            foreach ($uploadBannerMaterialList as $k => $item) {

                $adAccountId = $item['adAccountId'];
                $behe_type = $this->config['file_type'];
                $fileType = 1;
                $duration = 0;
                $beheAccountId = $allowAccountIdAry[$item['adAccountId']];
                $sql = "select companyName,companyCateId,companySubCateId from behe_account where beheAccountId={$beheAccountId}";
                $accountInfo = $this->select("again_main", $sql, true);
                if (!$accountInfo) {
                    $this->log("{$adAccountId}广告主不存在",$this->appName);
                    continue;
                }
                $companyName = $accountInfo[0]['companyName'];
                $sql = "select * from ig_industry_letv where xId='{$accountInfo[0]['companyCateId']}'";
                $industryInfo = $this->select("again_info", $sql, true);
                if (!$industryInfo) {
                    $this->log("{$adAccountId}{$accountInfo[0]['companyCateId']}行业不存在",$this->appName);

                    continue;
                }

                $fileUrl = $this->config['banner_cdn_domain'] . $item["fileUrl"];
                $orderId = $item['orderId'];
                $goUrl = $item["goUrl"];
                $monitorUrl = $item["turl"];
                if($monitorUrl=='http://'){
                    $monitorUrl='';
                }
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime("+365 day"));

                $industry = $industryInfo[0]['sId'];
                $display = array(0, 1, 2, 3, 4, 5, 6, 7);
                $request = array();
                //$request['ad'][0]["name"] = $item['name'];
                $request['ad'][0]["url"] = $fileUrl;
                $request['ad'][0]["landingpage"] = array($goUrl);
                $request['ad'][0]["monitor"][] =$monitorUrl;
                $request['ad'][0]["advertiser"] = $companyName;
                $request['ad'][0]["startdate"] = date('Y-m-d');
                $request['ad'][0]["enddate"] = date("Y-m-d", strtotime("+1 years"));
                $request['ad'][0]["type"] = $behe_type[$item['type']];
                $request['ad'][0]["media"] = array(1, 2, 3);
                $request['ad'][0]["duration"] = $duration;
                $request['ad'][0]["industry"] = $industry;
                $request['ad'][0]["display"] = $display;
                $request['token'] = $this->letvConfig->token;
                $request['dspid'] = $this->letvConfig->dspId;
                $request_json=json_encode($request);

                $response = $this->postCurl($this->letvConfig->materialUploadApi, $request_json, 'json');
                $this->log(json_encode($response), $this->appName);
                $this->log($request_json, $this->appName);
                
                if (isset($response['response']['result'])) {
                    $update_data = array();
                    $update_data['orderId'] = $orderId;
                    $update_data['accountId'] = $adAccountId;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $update_data['reason'] = "";
                    $update_data['fileType'] = 1;
                    $update_data['fileUrl'] = $item['fileUrl'];
                    
                    if (empty($response['response']['message'])) {
                        $update_data['adxStatus'] = 0;
                        $update_data['uploadStatus'] = 1;
                    } else {
                        $errorCode = 0;
                        foreach ($response['response']['message'] as $code => $item_message) {
                            $errorCode = $code;
                        }
                        if (isset($this->letvConfig->errorConfig[$errorCode])) {
                            $update_data['reason'] = $this->letvConfig->errorConfig[$errorCode];
                            $this->log($item['id'] . $update_data['reason'], $this->appName);
                        }
                        $update_data['adxStatus'] = 2;
                        $update_data['uploadStatus'] = 0;
                    }
                    $this->saveMaterial("adId={$item['id']} and adxId=12", $update_data,12);
                }
            }
        }


        $this->destoryPid($this->appName);
    }

}

$obj = new letvMaterialUpload();
$obj->run();
?>
