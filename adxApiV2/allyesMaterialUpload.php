<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/allyesConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\allyesConfig as allyesConfig;
use \library\base as base;

class allyesMaterialUpload extends base{

    public $appName = 'allyesMaterialUpload';

    public function run(){
        $this->allyesConfig = new allyesConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $imgCode = file_get_contents(APP_PATH . "/tmp/img.html");
        $swfCode = file_get_contents(APP_PATH . "/tmp/swf.html");
        $arr_db_info = $this->getBannerMaterial(15,'3,99','0,1');
        if ($arr_db_info) {
            foreach($arr_db_info as $item_db) {
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
            $sql = "select xId,parent,sId as id from ig_industry_allyes";
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
                $adAccountId = $item['adAccountId'];
                $creativeUrl = $this->config['file_path'] . $item['fileUrl'];
                $landingPage = $item['goUrl'];
                $targetUrl = $item['goUrl'];
                $monitorUrls = $item['turl'];
                $width = $item['width'];
                $height = $item['height'];
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->allyesConfig->allyes_file_type[$file_ext_name];
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                if (!$this->allyesConfig->allyes_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型{$file_ext_name}";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 15 and advertId = {$materialId}");
                    continue;
                }
                $arr_material_info_ary = array();
                if ($file_ext_name == 'swf') {
                    $code = $swfCode;
                } else {
                    $code = $imgCode;
                }
                $code = str_replace("%%VIEW_URL%%", "{!dsp_show_url}", $code);
                $code = str_replace("%%TARGET_URL%%", $landingPage, $code);
                $code = str_replace("%%EXCHGID%%", 1, $code);
                $code = str_replace("%%ADID%%", $materialId, $code);
                $code = str_replace("%%CREATIVE_URL%%", $creativeUrl, $code);
                $code = str_replace("%%ADWIDTH%%", $width, $code);
                $code = str_replace("%%ADHEIGHT%%", $height, $code);
                $arr_material_info_ary["bindId"] = $materialId;
                $arr_material_info_ary["code"] = $code;
                $arr_material_info_ary["name"] = $name;
                $arr_material_info_ary["type"] = $type;
                $arr_material_info_ary["width"] = $width;
                $arr_material_info_ary["height"] = $height;
                $arr_material_info_ary["creativeTagId"] = $creativeTradeId;
                $arr_send_json = array();
                $arr_send_param = json_encode($arr_material_info_ary);

                if($item['uploadStatus'] == 0):
                    $str_return = $this->postCurl($this->allyesConfig->allyes_material_banner_add, $arr_send_param, "json", $this->allyesConfig->allyes_dsp_id . ":" . $this->allyesConfig->allyes_token);
                else:
                    $str_return = $this->postCurl($this->allyesConfig->allyes_material_banner_update, $arr_send_param, "json", $this->allyesConfig->allyes_dsp_id . ":" . $this->allyesConfig->allyes_token);
                endif;
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return)) {
                        if ($arr_return['id'] != '') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['fileType'] = 1;
                            $update_data['orderId'] = $orderId;
                            $update_data['exAdId'] = $arr_return['id'];
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 15 and advertId = {$materialId}");
                        } elseif ($arr_return['errors'][0] == '202032') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['fileType'] = 1;
                            $update_data['orderId'] = $orderId;
                            $update_data['exAdId'] = $arr_return['id'];
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 15 and advertId = {$materialId}");
                        } else {
                            $update_data = array();
                            $update_data['fileType'] = 1;
                            $update_data['adxStatus'] = 2;
                            $update_data['reason'] = $this->allyesConfig->allyes_error_config[$arr_return['errors'][0]];
                            $update_data['uploadStatus'] = 0;
                            $update_data['orderId'] = $orderId;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 15 and advertId = {$materialId}");
                        }
                    }
                }
            }
        }


        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getVideoMaterial(15,'3,99','0,1');
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
            $sql = "select xId,parent,sId as id from ig_industry_allyes";
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
                $adAccountId=$item['adAccountId'];
                $orderId=$item['orderId'];
                $creativeUrl = $this->config['video_file_path'] . $item['fileUrl'];
                $landingPage = $item['goUrl'];
                $targetUrl = $item['goUrl'];
                $monitorUrls = $item['turl'];
                $width = $item['width'];
                $height = $item['height'];
                $duration=$this->config['durationAry'][$item['duration']];
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->allyesConfig->allyes_file_type[$file_ext_name];
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                if (!$this->allyesConfig->allyes_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型{$file_ext_name}";
                    $update_data['uploadStatus'] = 0;
                    $update_data['fileType'] = 2;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 15 and advertId = {$materialId}",$update_data);
                    continue;
                }
                $arr_material_info_ary = array();
            
                $arr_material_info_ary["bindId"] = $materialId;
                $arr_material_info_ary["duration"] = $duration;
                $arr_material_info_ary["fileurl"] = $creativeUrl;
                $arr_material_info_ary["xmltype"] = 1;
                $arr_material_info_ary["name"] = $name;
                $arr_material_info_ary["type"] = (int)$type;
                $arr_material_info_ary["width"] = (int)$width;
                $arr_material_info_ary["height"] =(int) $height;
                $arr_material_info_ary["creativeTagId"] = $creativeTradeId;
                $arr_material_info_ary["landingpage"] = "{!ssp_click_url}{!dsp_click_url}" . $landingPage;
                $arr_send_json = array();
                $arr_send_param = json_encode($arr_material_info_ary);
                if($item['uploadStatus'] == 0):
                    $str_return = $this->postCurl($this->allyesConfig->allyes_material_video_add, $arr_send_param, "json", $this->allyesConfig->allyes_dsp_id . ":" . $this->allyesConfig->allyes_token);
                else:
                    $str_return = $this->postCurl($this->allyesConfig->allyes_material_video_update, $arr_send_param, "json", $this->allyesConfig->allyes_dsp_id . ":" . $this->allyesConfig->allyes_token);
                endif;
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return)) {
                        if ($arr_return['id'] != '') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['orderId'] = $orderId;
                            $update_data['fileType'] = 2;
                            $update_data['exAdId'] = $arr_return['id'];
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 15 and advertId = {$materialId}",$update_data);
                        }else {
                            $update_data = array();
                            $update_data['fileType'] = 2;
                            $update_data['adxStatus'] = 2;
                            $update_data['orderId'] = $orderId;
                            $update_data['reason'] = $config->allyes_error_config[$arr_return['errors'][0]];
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 15 and advertId = {$materialId}",$update_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new allyesMaterialUpload();
$obj->run();
?>

