<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/snkConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\snkConfig as snkConfig;
use \library\base as base;

class snkMaterialUpload extends base{

    public $appName = 'snkMaterialUpload';

    public function run(){
        $this->snkConfig = new snkConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAr = $this->getAllowAdvertiser(29);
        $arr_db_info = $this->getBannerMaterial(29,'3,99','0,1');
        if ($arr_db_info) {
            foreach ($arr_db_info as $item_db) {
                $orderId = $item_db["orderId"];
                $orderIdAry[$orderId] = $orderId;
                $adAccountId = $allowAccountIdAry[$item_db["adAccountId"]];
                $adAccountIdAry[$adAccountId] = $adAccountId;
                $campaignId = $item_db["campaignId"];
                $campaignIdAry[$campaignId] = $campaignId;
                $uploadMaterialList[] = $item_db;
            }
        }

        if ($uploadMaterialList) {
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select beheAccountId ,adxStatus from adx_account where adxId = 29 and beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $accountList[$item['beheAccountId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCategyId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();
            $sql = "select xId,parent,sId as id from ig_industry_snk";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }
            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categoryList[$item['companySubCateId']]) {
                    $campaignList[$item['id']]['creativeTradeId'] = $categoryList[$item['companySubCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['creativeTradeId'] = 0;
                }
            }
            foreach ($uploadMaterialList as $item) {
                $materialId = $item['id'];
                $orderId=$item['orderId'];
                $creativeUrl = $this->config['file_path'] . $item['fileUrl'];
                $advertiserId = $item['beheAccountId'];
                $landingPage = $item['goUrl'];
                $targetUrl = $this->snkConfig->snk_ck . urlencode($item['goUrl']);
                $monitorUrls = $this->snkConfig->snk_vw;
                $width = $item['width'];
                $height = $item['height'];
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];

                if ($creativeTradeId == 0) {
                    continue;
                }
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->snkConfig->snk_file_ext_type[$file_ext_name];
                if (!$this->snkConfig->snk_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型";
                    $update_data['uploadStatus'] = 0;
                    $update_data['fileType'] = 1;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 29 and advertId = {$materialId}",$update_data);
                    continue;
                }
                if ($accountList[$advertiserId]['adxStatus'] != 1) {
                    $update_data = array();
                    $update_data['adxStatus'] = 99;
                    if ($accountList[$advertiserId]['adxStatus'] == 0) {
                        $update_data['reason'] = "广告主审核中";
                    } elseif ($accountList[$advertiserId]['adxStatus'] == 2) {
                        $update_data['reason'] = "广告主审核被拒";
                    }
                    $update_data['uploadStatus'] = 0;
                    $update_data['fileType'] = 1;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 29 and advertId = {$materialId}",$update_data);
                    continue;
                }
                $arr_material_info_ary = array();
                $arr_material_info_ary[0]["creativeId"] = $materialId;
                $arr_material_info_ary[0]["creativeUrl"] = $creativeUrl;
                $arr_material_info_ary[0]["advertiserId"] = $advertiserId;
                $arr_material_info_ary[0]["landingPage"] = $landingPage;
                $arr_material_info_ary[0]["targetUrl"] = $targetUrl;
                $arr_material_info_ary[0]["monitorUrls"][] = $monitorUrls;
                $arr_material_info_ary[0]["type"] = $type;
                $arr_material_info_ary[0]["width"] = $width;
                $arr_material_info_ary[0]["height"] = $height;
                $arr_material_info_ary[0]["creativeTradeId"] = $creativeTradeId;
                if (!empty($arr_material_info_ary)) {
                    $arr_send_json = array();
                    $arr_send_json["request"] = $arr_material_info_ary;
                    $arr_send_json["authHeader"]["dspId"] = $this->snkConfig->snk_dsp_id;
                    $arr_send_json["authHeader"]["token"] = $this->snkConfig->snk_token;
                    $str_json = json_encode($arr_send_json);
                    if($item['uploadStatus'] == 0):
                        $str_return = $this->postCurl($this->snkConfig->snk_material_add, $str_json, "json");
                    else:
                        $str_return = $this->postCurl($this->snkConfig->snk_material_update, $str_json, "json");
                    endif;
                    if (!empty($str_return)) {
                        $arr_return = $str_return['response'];
                        if (is_array($arr_return)) {
                            if ($arr_return['status'] === 0||$arr_return['errors'][0]['code']==2002) {
                                $update_data = array();
                                $update_data['adxStatus'] = 0;
                                $update_data['reason'] = "";
                                $update_data['uploadStatus'] = 1;
                                $update_data['fileType'] = 1;
                                $update_data['orderId'] = $orderId;
                                $update_data['mtime'] = date('Y-m-d H:i:s');
                                $this->saveMaterial("adxId = 29 and advertId = {$materialId}",$update_data);
                            }
                        }
                    }
                }
            }
        }

        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAry = $this->getAllowAdvertiser(29);
        $arr_db_info = $this->getVideoMaterial(29,'3,99','0,1');
        if ($arr_db_info) {
            foreach ($arr_db_info as $item_db) {
                $orderId = $item_db["orderId"];
                $orderIdAry[$orderId] = $orderId;
                $adAccountId = $allowAccountIdAry[$item_db["adAccountId"]];
                $adAccountIdAry[$adAccountId] = $adAccountId;
                $campaignId = $item_db["campaignId"];
                $campaignIdAry[$campaignId] = $campaignId;
                $uploadMaterialList[] = $item_db;
            }
        }

        if ($uploadMaterialList) {
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select beheAccountId ,adxStatus from adx_account where adxId=29 and beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $accountList[$item['beheAccountId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCategyId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();
            $sql = "select xId,parent,sId as id from ig_industry_snk";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }

            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categoryList[$item['companySubCateId']]) {
                    $campaignList[$item['id']]['creativeTradeId'] = $categoryList[$item['companySubCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['creativeTradeId'] = 0;
                }
            }

            foreach ($uploadMaterialList as $item) {
                $materialId = $item['id'];
                $orderId=$item['orderId'];
                $duration = $this->config['durationAry'][$item['duration']];
                $creativeUrl = $this->config['video_file_path'] . $item['fileUrl'];
                $advertiserId = $item['beheAccountId'];
                $landingPage = $item['goUrl'];
                $targetUrl = $this->snkConfig->snk_ck . urlencode($item['goUrl']);
                $monitorUrls = $this->snkConfig->snk_vw;
                $type = $this->snkConfig->snk_file_ext_type[$item['type']];
                $width = $item['width'];
                $height = $item['height'];
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                if ($creativeTradeId == 0) {
                    continue;
                }
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                if (!$this->snkConfig->snk_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型";
                    $update_data['fileType'] = 2;
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 29 and advertId = {$materialId}",$update_data);
                    continue;
                }

                if ($accountList[$advertiserId]['adxStatus'] != 1) {
                    $update_data = array();
                    $update_data['adxStatus'] = 99;
                    if ($accountList[$advertiserId]['adxStatus'] == 0) {
                        $update_data['reason'] = "广告主审核中";
                    } elseif ($accountList[$advertiserId]['adxStatus'] == 2) {
                        $update_data['reason'] = "广告主审核被拒";
                    }
                    $update_data['fileType'] = 2;
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 29 and advertId = {$materialId}",$update_data);
                    continue;
                }

                $arr_material_info_ary = array();
                $arr_material_info_ary[0]["creativeId"] = $materialId;
                $arr_material_info_ary[0]["creativeUrl"] = $creativeUrl;
                $arr_material_info_ary[0]["advertiserId"] = $advertiserId;
                $arr_material_info_ary[0]["landingPage"] = $landingPage;
                $arr_material_info_ary[0]["targetUrl"] = $targetUrl;
                $arr_material_info_ary[0]["monitorUrls"][] = $monitorUrls;
                $arr_material_info_ary[0]["type"] = $type;
                $arr_material_info_ary[0]["width"] = $width;
                $arr_material_info_ary[0]["height"] = $height;
                $arr_material_info_ary[0]["duration"] = $duration;
                $arr_material_info_ary[0]["creativeTradeId"] = $creativeTradeId;
                $arr_send_json = array();
                $arr_send_json["request"] = $arr_material_info_ary;
                $arr_send_json["authHeader"]["dspId"] = $this->snkConfig->snk_dsp_id;
                $arr_send_json["authHeader"]["token"] = $this->snkConfig->snk_token;
                $str_json = json_encode($arr_send_json);
                if($item['uploadStatus'] == 0):
                    $str_return = $this->postCurl($this->snkConfig->snk_material_add, $str_json, "json");
                else:
                    $str_return = $this->postCurl($this->snkConfig->snk_material_update, $str_json, "json");
                endif;
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return)) {
                        if ($arr_return['status'] === 0) {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['orderId'] = $orderId;
                            $update_data['reason'] = "";
                            $update_data['fileType'] = 2;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 29 and advertId = {$materialId}",$update_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new snkMaterialUpload();
$obj->run();
?>
    
