<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__file__).'/../'));

require_once APP_PATH . '/config/gdtConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\gdtConfig as gdtConfig;
use \library\base as base;

class gdtMaterialUpload extends base{

    public $appName = 'gdtMaterialUpload';

    public function run(){
        $this->gdtConfig = new gdtConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAry = $this->getAllowAdvertiser(49);
        $arr_db_info = $this->getBannerMaterial(49,'3,99','0,1');
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
            $sql = "select beheAccountId,adxStatus from adx_account where adxId=49 and beheAccountId in ($accountIdStr)";
            $accountDb = $this->select("again_main",$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $accountList[$item['beheAccountId']] = $item;
            }



            foreach ($uploadMaterialList as $item) {
                $materialId = $item['id'];
                $creativeUrl = $this->config['file_path'] . $item['fileUrl'];
                $advertiserId = $item['adAccountId'];
                $adAccountId=$advertiserId;
                $orderId=$item['orderId'];
                $landingPage = $item['goUrl'];
                $targetUrl = $this->gdtConfig->gdt_ck . urlencode($item['goUrl']);
                $monitor_url1 = $this->gdtConfig->gdt_vw;
                $monitor_url2 = $item['monitorUrl'] ? $material_item['monitorUrl'] : '';
                $target_url = $this->gdtConfig->gdt_ck . urlencode($landing_page);
                $width = $item['width'];
                $height = $item['height'];
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->gdtConfig->gdt_file_ext_type[$file_ext_name];


                if (!$config->gdt_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型{$file_ext_name}";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 49 and advertId = {$materialId}",$update_data);
                    continue;
                }
                if (!empty($accountList[$advertiserId]['adxStatus'])) {
                    if ($accountList[$advertiserId]['adxStatus'] != 1) {
                        $update_data = array();
                        $update_data['adxStatus'] = 99;
                        if ($accountList[$advertiserId]['adxStatus'] == 0) {
                            $update_data['reason'] = "广告主审核中";
                        } elseif ($accountList[$advertiserId]['adxStatus'] == 2) {
                            $update_data['reason'] = "广告主审核被拒";
                        }
                        $update_data['uploadStatus'] = 0;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 49 and advertId = {$materialId}",$update_data);
                        continue;
                    }
                } else {
                    continue;
                }
                if (!$this->gdtConfig->gdt_size[$str_size_temp]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "{$str_size_temp}尺寸不符合要求";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 49 and advertId = {$materialId}",$update_data);
                    continue;
                }
                $arr_material_info_ary = array();
                $arr_material_info_ary[0]["product_type"] = 1;
                $arr_material_info_ary[0]["creative_id"] = $materialId;
                $arr_material_info_ary[0]["multimedia1_file_url"] = $creativeUrl;
                $arr_material_info_ary[0]["advertiser_id"] = $advertiserId;
                $arr_material_info_ary[0]["landing_page"] = $landingPage;
                $arr_material_info_ary[0]["target_url"] = $targetUrl;
                $arr_material_info_ary[0]["monitor_url1"] = $monitor_url1;
                $arr_material_info_ary[0]["monitor_url2"] = $monitor_url2;
                //$arr_material_info_ary[0]["begin_date_included"] = date("Y-m-d");
                $arr_material_info_ary[0]["end_date_included"] = date("Y-m-d", strtotime('+180 day'));
                $arr_material_info_ary[0]["creative_spec"] = $this->gdtConfig->gdt_creative_spec_list[$str_size_temp];
                $arr_send_json = array();
                $arr_send_json["data"] = $arr_material_info_ary;
                $request_json = json_encode($arr_send_json);
                if($item['uploadStatus'] == 0):
                    $str_return = $this->postCurl($this->gdtConfig->gdt_material_add, $request_json, $this->gdtConfig->gdt_token);
                else:
                    $str_return = $this->postCurl($this->gdtConfig->gdt_material_update, $request_json, $this->gdtConfig->gdt_token);
                endif;
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return)) {
                        if ($arr_return['code'] === 0) {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 49 and advertId = {$materialId}",$update_data);
                        } elseif ($arr_return['code'] === 2) {
                            $update_data = array();
                            $update_data['adxStatus'] = 2;
                            $update_data['reason'] = $arr_return['data'][0]['message'];
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 49 and advertId = {$materialId}",$update_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new gdtMaterialUpload();
$obj->run();
?>
    
