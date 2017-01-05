<?php

namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/youkuConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\youkuConfig as ykConfig;
use \library\base as base;

class ykMaterialUpload extends base {

    public $appName = "ykMaterialUpload";

    public function run() {
        $this->ykConfig = new ykConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $update_arr = array();
        $update_arr['exchangeStatus'] = 99;
        $this->save('again_main', 'advert_adx_status', "(reason = '物料时长不符合广告位要求' or reason='物料所属的广告主为空') and adxId=9", $update_arr);
        $allowAccountIdAry = $this->getAllowAdvertiser(9);
        $uploadVideoMaterialList = $this->getVideoMaterial(9, 99, "0,1");
        if (!empty($uploadVideoMaterialList)) {

            foreach ($uploadVideoMaterialList as $k => $item) {

                $adAccountId = $item['adAccountId'];
                $beheAccountId = $allowAccountIdAry[$item['adAccountId']];

                $sql = "select companyName as uploadName,adxStatus as exchangeStatus from adx_account where beheAccountId = {$beheAccountId}";
                $accountInfo = $this->select("again_main", $sql);

                if (!$accountInfo) {
                    $this->log("{$adAccountId}广告主不存在", $this->appName);
                    continue;
                }
                $companyName = $accountInfo[0]['uploadName'];
                if ($this->config['file_type'][$item['type']] == 'flv' || $this->config['file_type'][$item['type']] == 'mp4') {
                    if ($item['youkuFileUrl'] != '') {
                        $fileUrl = $item['youkuFileUrl'];
                        $cdnUrl = $fileUrl;
                    } else {
                        $this->log("等待文件上传到优酷CDN", $this->appName);
                        continue;
                    }
                } else {

                    $fileUrl = $this->config['video_cdn_domain'] . $item["fileUrl"];
                    $cdnUrl = $fileUrl;
                }
                $orderId = $item['orderId'];
                $goUrl = $item["goUrl"];
                $monitorUrl = $item["turl"];
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime("+365 day"));

                $request = array();
                $request['dspid'] = $this->ykConfig->dspId;
                $request['token'] = $this->ykConfig->token;
                $request['material'][0]["url"] = $fileUrl;
                $request['material'][0]["landingpage"] = $goUrl;
                $request['material'][0]["monitor"][] = $monitorUrl;
                $request['material'][0]["advertiser"] = $companyName;
                $request['material'][0]["startdate"] = $startDate;
                $request['material'][0]["enddate"] = $endDate;

                $response = $this->postCurl($this->ykConfig->materialUploadApi, json_encode($request), 'json');
                $this->log(json_encode($response), $this->appName);
                $this->log(json_encode($request), $this->appName);
                if ($response['response']['result'] === 0) {
                    $update_data = array();
                    $update_data['orderId'] = $orderId;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $update_data['reason'] = "";
                    $update_data['fileType'] = 2;
                    $update_data['fileUrl'] = $cdnUrl;
                    if (empty($response['response']['message'])) {
                        $update_data['exchangeStatus'] = 0;
                        $update_data['uploadStatus'] = 1;
                    } else {
                        $errorCode = 0;
                        foreach ($response['response']['message'] as $code => $item_message) {
                            $errorCode = $code;
                        }
                        if (isset($this->ykConfig->errorConfig[$errorCode])) {
                            $update_data['reason'] = $this->ykConfig->errorConfig[$errorCode];
                            $this->log($item['id'] . $update_data['reason'], $this->appName);
                        }
                        $update_data['exchangeStatus'] = 2;
                        $update_data['uploadStatus'] = 0;
                    }
                    $this->saveMaterial(" adId = {$item['id']} and adxId = 9 ", $update_data);
                }
            }
        }


        $uploadBannerMaterialList = $this->getBannerMaterial(9, 99, "0,1");
        if (!empty($uploadBannerMaterialList)) {
            foreach ($uploadBannerMaterialList as $k => $item) {
                $adId = $item["id"];
                $adAccountId = $item['adAccountId'];
                $beheAccountId = $allowAccountIdAry[$item['adAccountId']];
                $sql = "select companyName as uploadName,adxStatus as exchangeStatus from adx_account where beheAccountId = {$beheAccountId}";
                $accountInfo = $this->select("again_main", $sql);
                if (!$accountInfo) {
                    $this->log("{$adId}|||{$adAccountId}广告主不存在", $this->appName);
                    continue;
                }
                $companyName=$accountInfo[0]['uploadName'];
                $fileUrl = $this->config['banner_cdn_domain'] . $item["fileUrl"];
                $orderId = $item['orderId'];
                $goUrl = $item["goUrl"];
                $monitorUrl = $item["turl"];
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime("+365 day"));
                $request = array();
                $request['dspid'] = $this->ykConfig->dspId;
                $request['token'] = $this->ykConfig->token;
                $request['material'][0]["url"] = $fileUrl;
                $request['material'][0]["landingpage"] = $goUrl;
                $request['material'][0]["monitor"][] = $monitorUrl;
                $request['material'][0]["advertiser"] = $companyName;
                $request['material'][0]["startdate"] = $startDate;
                $request['material'][0]["enddate"] = $endDate;
                $response = $this->postCurl($this->ykConfig->materialUploadApi, json_encode($request), 'json');
                $this->log(json_encode($response), $this->appName);
                if ($response['response']['result'] === 0) {
                    $update_data = array();
                    $update_data['orderId'] = $orderId;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $update_data['reason'] = "";
                    $update_data['fileType'] = 1;
                    $update_data['fileUrl'] = $item["fileUrl"];
                    if (empty($response['response']['message'])) {
                        $update_data['adxStatus'] = 0;
                        $update_data['uploadStatus'] = 1;
                    } else {
                        $errorCode = 0;
                        foreach ($response['response']['message'] as $code => $item_message) {
                            $errorCode = $code;
                        }
                        if (isset($this->ykConfig->errorConfig[$errorCode])) {
                            $update_data['reason'] = $this->ykConfig->errorConfig[$errorCode];
                            $this->log($item['id'] . $update_data['reason'], $this->appName);
                        }
                        $update_data['adxStatus'] = 2;
                        $update_data['uploadStatus'] = 0;
                    }
                    $this->saveMaterial(" adId = {$item['id']} and adxId = 9 ", $update_data);
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new ykMaterialUpload();
$obj->run();
?>

