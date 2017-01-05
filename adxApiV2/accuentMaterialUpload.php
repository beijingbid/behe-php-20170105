<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/valuemakerConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\valuemakerConfig as valuemakerConfig;
use \library\base as base;

class accuentMaterialUpload extends base{

    public $appName = 'accuentMaterialUpload';

    public function run(){
        $this->valuemakerConfig = new valuemakerConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getBannerMaterial(53,'3,99','0,1');
        if ($arr_db_info) {
            foreach ($arr_db_info as $item_db) {
                $orderId = $item_db["orderId"];
                $orderIdAry[$orderId] = $orderId;
                $adAccountId = $item_db["adAccountId"];
                $adAccountIdAry[$adAccountId] = $adAccountId;
                $campaignId = $item_db["campaignId"];
                $campaignIdAry[$campaignId] = $campaignId;
                $uploadMaterialList[] = $item_db;
            }
        }
        if ($uploadMaterialList) {
            $imgCode = file_get_contents(APP_PATH . "/tmp/vlk/img.html");
            $swfCode = file_get_contents(APP_PATH . "/tmp/vlk/swf.html");

            $sql = "select xId,parent,sId as id from ig_industry_vwa  where 1";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCategyId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();
            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categoryList[$item['companySubCateId']]) {
                    $campaignList[$item['id']]['creativeTradeId'] = $categoryList[$item['companySubCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['creativeTradeId'] = 0;
                }
            }
            foreach ($uploadMaterialList as $item) {
                $name = $item['name'];
                $materialId = $item['id'];
                $orderId = $item['orderId'];
                $adAccountId = $item['beheAccountId'];
                $creativeUrl = $this->config['file_path'] . $item['fileUrl'];
                $landingPage = $item['goUrl'];
                $targetUrl = $item['goUrl'];
                $monitorUrls = $item['turl'];
                $width = (int) $item['width'];
                $height = (int) $item['height'];
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->valuemakerConfig->valuemaker_file_type[$file_ext_name];
                if ($type == 2) {
                    $goUrl = '{!vam_click_url_esc}' . $item["goUrl"];
                } else {
                    $goUrl = '{!vam_click_url}' . $item["goUrl"];
                }
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                $arr_material_info_ary = array();
                if ($file_ext_name == 'swf') {
                    $code = $swfCode;
                } else {
                    $code = $imgCode;
                }
                $code = str_replace("%%TARGET_URL%%", $targetUrl, $code);
                $code = str_replace("%%VIEW_URL%%", "{!dsp_show_url}", $code);
                $code = str_replace("%%EXCHGID%%", 1, $code);
                $code = str_replace("%%ADID%%", $materialId, $code);
                $code = str_replace("%%CREATIVE_URL%%", $creativeUrl, $code);
                $code = str_replace("%%ADWIDTH%%", $width, $code);
                $code = str_replace("%%ADHEIGHT%%", $height, $code);
                $code = str_replace('{!ssp_click_url}', '{!vam_click_url}', $code);
                $arr_material_info_ary["id"] = $materialId;
                $arr_material_info_ary["html_snippet"] = $code;
                $arr_material_info_ary["adomain_list"] = array(getdomain($targetUrl));
                $arr_material_info_ary["format"] = $type;
                $arr_material_info_ary["width"] = $width;
                $arr_material_info_ary["height"] = $height;
                $arr_material_info_ary["category"] = $creativeTradeId;
                $arr_send_json = array();
                $arr_send_param = json_encode($arr_material_info_ary);
                if($item['uploadStatus'] == 0):
                    $str_return = $this->postCurl($this->valuemakerConfig->valuemaker_material_banner_add, $arr_send_param, "json", $this->valuemakerConfig->valuemaker_dsp_id . ":" . $this->valuemakerConfig->valuemaker_token, true, true);
                    $arr_return = $str_return['response'];
                    if ($str_return['httpCode'] == 200) {
                        $update_data = array();
                        $update_data['adxStatus'] = 0;
                        $update_data['reason'] = "";
                        $update_data['fileType'] = 1;
                        $update_data['orderId'] = $orderId;
                        $update_data['uploadStatus'] = 1;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    } else {
                        if ($arr_return['status'] == '1002') {

                            $update_data = array();
                            $update_data['fileType'] = 1;
                            $update_data['adxStatus'] = 99;
                            $update_data['reason'] = "";
                            $update_data['orderId'] = $orderId;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                        } else {
                            $update_data = array();
                            $update_data['fileType'] = 1;
                            $update_data['adxStatus'] = 2;
                            $update_data['orderId'] = $orderId;
                            $update_data['reason'] = $this->valuemakerConfig->valuemaker_error_config[$arr_return['status']] ? $this->valuemakerConfig->valuemaker_error_config[$arr_return['status']] : '';
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                        }
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    }
                else:
                    $str_return = $this->postCurl($this->valuemakerConfig->valuemaker_material_banner_update, $arr_send_param, "json", $this->valuemakerConfig->valuemaker_dsp_id . ":" . $this->valuemakerConfig->valuemaker_token, true, true);
                    $arr_return = $str_return['response'];
                    if ($str_return['httpCode'] == 200) {
                        $update_data = array();
                        $update_data['adxStatus'] = 0;
                        $update_data['reason'] = "";
                        $update_data['fileType'] = 1;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    } else {
                        $update_data = array();
                        $update_data['fileType'] = 1;
                        $update_data['adxStatus'] = 2;
                        $update_data['reason'] = $this->valuemakerConfig->valuemaker_error_config[$arr_return['status']] ? $this->valuemakerConfig->valuemaker_error_config[$arr_return['status']] : '';
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    }
                endif;
            }
        }

        $allowAccountIdAry = $this->getAllowAdvertiser(53);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getVideoMaterial(53,'3,99','0,1');
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
            $sql = '"select companyName,adxStatus,beheAccountId as exchangeStatus from adx_account where beheAccountId in ({$accountIdStr})";';
            $arr_db_info = $this->select('again_main',$sql);
            $companyArr = array();
            foreach ($arr_db_info as $k => $item) {
                $companyArr[$item['beheAccountId']] = $arr_db_info[$k];
            }
            $sql = "select xId,parent,sId as id from ig_industry_vwa  where 1";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCategyId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();

            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categoryList[$item['companySubCateId']]) {
                    $campaignList[$item['id']]['creativeTradeId'] = $categoryList[$item['companySubCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['creativeTradeId'] = 0;
                }
            }
            foreach ($uploadMaterialList as $item) {
                $name = $item['name'];
                $materialId = $item['id'];
                $orderId=$item['orderId'];
                $creativeUrl = $this->config['video_file_path'] . $item['fileUrl'];
                $landingPage = $item['goUrl'];
                $targetUrl = $item['goUrl'];
                $monitorUrls = $item['turl'];
                $width = $item['width'];
                $height = $item['height'];
                $duration = $item['duration'];
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->valuemakerConfig->valuemaker_file_type[$file_ext_name];
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                $adAccountId=$item['adAccountId'];
                $arr_material_info_ary = array();
                $arr_material_info_ary["id"] = $materialId;
                $arr_material_info_ary["duration"] = $this->config['durationAry'][$duration];
                $arr_material_info_ary["fileurl"] = $creativeUrl;
                $arr_material_info_ary["landingpage"] = '{!vam_click_url}{!dsp_click_url}' . $landingPage;
                $arr_material_info_ary["adomain_list"] = array(getdomain($targetUrl));
                $arr_material_info_ary["format"] = $type;
                $arr_material_info_ary["width"] = $width;
                $arr_material_info_ary["height"] = $height;
                $arr_material_info_ary["category"] = $creativeTradeId;
                $arr_material_info_ary["advertiser"] = $companyArr[$adAccountId]['companyName'];
                $arr_send_json = array();
                $arr_send_param = json_encode($arr_material_info_ary);
                if($item['uploadStatus'] == 0):
                    $str_return = $this->postCurl($this->valuemakerConfig->valuemaker_material_video_add, $arr_send_param, "json", $this->valuemakerConfig->valuemaker_dsp_id . ":" . $this->valuemakerConfig->valuemaker_token, true,true);
                    if ($str_return['httpCode'] == 200) {
                        $update_data = array();
                        $update_data['adxStatus'] = 0;
                        $update_data['reason'] = "";
                        $update_data['orderId'] =$orderId;
                        $update_data['fileType'] = 2;
                        $update_data['uploadStatus'] = 1;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    } else {
                        $update_data = array();
                        $update_data['fileType'] = 2;   
                        $update_data['adxStatus'] = 2;
                        $update_data['uploadStatus'] = 0;
                        $update_data['orderId'] =$orderId;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    }
                else:
                    $str_return = post2url($config->valuemaker_material_video_update, $arr_send_param, "json", $this->valuemakerConfig->valuemaker_dsp_id . ":" . $this->valuemakerConfig->valuemaker_token,true,true);
                    if ($str_return['httpCode'] == 200) {
                        $update_data = array();
                        $update_data['adxStatus'] = 0;
                        $update_data['reason'] = "";
                        $update_data['fileType'] = 2;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    } else {
                        $update_data = array();
                        $update_data['fileType'] = 2;
                        $update_data['adxStatus'] = 2;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 53 and advertId = {$materialId}",$update_data);
                    }
                endif;
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new accuentMaterialUpload();
$obj->run();
?>

