<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__file__).'/../'));
require_once APP_PATH . '/config/tencentConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tencentConfig as tencentConfig;
use \library\base as base;

class tencentMaterialUpload extends base{

    public $appName = 'tencentMaterialUpload';

    public function run(){
        $this->tencentConfig = new tencentConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getBannerMaterial(5,'3,99','0,1');
        $allowAccountIdAry = $this->getAllowAdvertiser(5);
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
            $sql = "select beheAccountId,companyName,qualificationInfo from behe_account where beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $item['businessLicenceName'] = json_decode($item['qualificationInfo'],true)['businessLicense'];
                $accountList[$item['beheAccountId']] = $item;
            }
            $orderUploadMaterialList = array();
            foreach ($uploadMaterialList as $item) {
                if ($item['goUrl'] == '' || $item['goUrl'] == 'http://') {
                    continue;
                }
                $tencentDspOrderId=$item['id'];
                $dspOrderId = $item['orderId'];
                $aid = $item['beheAccountId'];

                $creativeUrl = $this->config['file_path'] . $item['fileUrl'];
                $arr_material_info_ary = array();
                $arr_material_info_ary[0]["dsp_order_id"] = $tencentDspOrderId;
                $arr_material_info_ary[0]["client_name"] = $accountList[$aid]["businessLicenceName"] ? $accountList[$aid]["businessLicenceName"] : $accountList[$aid]["companyName"];
                $arr_material_info_ary[0]["targeting_url"] = $this->tencentConfig->tencent_ck . urlencode($item["goUrl"]);
                $arr_material_info_ary[0]["monitor_url"][] = $this->tencentConfig->tencent_vw;
                $arr_material_info_ary[0]["file_info"][] = array("file_url" => $creativeUrl);
                if ($item["turl"] != "" && $item["turl"] != 'http://') {
                    $arr_material_info_ary[0]["monitor_url"][] = $item["turl"];
                }
                $str_json = json_encode($arr_material_info_ary);

                $str_post = "dsp_id=" . $this->tencentConfig->tencent_dsp_id . "&token=" . $this->tencentConfig->tencent_token . "&order_info=" . urlencode($str_json);
                $str_return = $this->postCurl($this->tencentConfig->tencent_material_banner_add, $str_post);
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return)) {
                        if ($arr_return['ret_code'] === 0 && $arr_return['ret_msg'] == '完全正确') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['orderId'] = $dspOrderId;
                            $update_data['reason'] = "";
                            $update_data['fileType'] = 1;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 5 and advertId = {$tencentDspOrderId}",$update_data);
                        } else {
                            foreach ($arr_return['ret_msg'] as $rCode => $rItem) {
                                $reason = $this->tencentConfig->tencent_error[$rCode] ? $this->tencentConfig->tencent_error[$rCode] : '';
                                $update_data = array();
                                $update_data['adxStatus'] = 2;
                                $update_data['reason'] = $reason;
                                $update_data['uploadStatus'] = 0;
                                $update_data['fileType'] = 1;
                                $update_data['orderId'] = $dspOrderId;
                                $update_data['mtime'] = date('Y-m-d H:i:s');
                                $this->saveMaterial("adxId = 5 and advertId = {$tencentDspOrderId}",$update_data);
                            }
                        }
                    }
                }
            }
        }

        $uploadMaterialList = array();
        $orderIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getVideoMaterial(5,'3,99','0,1');
        $allowAccountIdAry = $this->getAllowAdvertiser(5);
        if ($arr_db_info) {
            foreach ($arr_db_info as $item_db) {
                $orderId = $item_db["orderId"];
                $orderIdAry[$orderId] = $orderId;
                $adAccountId = $allowAccountIdAry[$item_db["adAccountId"]];
                $adAccountIdAry[$adAccountId] = $adAccountId;
                $uploadMaterialList[] = $item_db;
            }
        }
        if ($uploadMaterialList) {
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select beheAccountId,companyName,qualificationInfo from behe_account where beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $item['businessLicenceName'] = json_decode($item['qualificationInfo'],true)['businessLicense'];
                $accountList[$item['beheAccountId']] = $item;
            }
            $orderUploadMaterialList = array();
            foreach ($uploadMaterialList as $item) {
                if ($item['goUrl'] == '' || $item['goUrl'] == 'http://') {
                    continue;
                }
                if ($item['fileStatus'] == 1) {
                    $systemFilePath = $this->config['video_file_system_path'] . $item['fileUrl'];
                    $ext = $this->config['file_type'][$item['type']];
                    $newFilePath = $this->config['video_file_system_path'] . '/' . date("Y/m/d") . "/";
                    $newFileName = md5(time() . rand(0, 1000000)) . "." . $ext;

                    $result = create_folder($newFilePath);
                    if ($result) {
                        if (copy($systemFilePath, $newFilePath . $newFileName)) {
                            $update_data = array();
                            $update_data['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                            $update_data['fileStatus'] = 0;
                            if ($item['materialType'] == 1) {
                                $this->save('again_main','banner_material',"id = '{$item['materialId']}'",$update_data);
                            } elseif ($item['materialType'] == 2) {
                                $this->save('again_main','video_material',"id = '{$item['materialId']}'",$update_data);
                            } else {
                                $this->save('again_main','banner_material',"id = '{$item['materialId']}'",$update_data);
                            }
                            $center_material_arr = array();
                            $center_material_arr['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                            $this->saveMaterial("advertId = '{$item['id']}' and adxId = 5",$center_material_arr);
                            continue;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                    continue;
                }
                $tencentDspOrderId = $item['id'];
                $dspOrderId=$item['orderId'];
                $aid = $item['beheAccountId'];
                $creativeUrl = $this->config['video_file_path'] . $item['fileUrl'];
                $arr_material_info_ary = array();
                $arr_material_info_ary[0]["dsp_order_id"] = $tencentDspOrderId;
                $arr_material_info_ary[0]["client_name"] = $accountList[$aid]["businessLicenceName"] ? $accountList[$aid]["businessLicenceName"] : $accountList[$aid]["companyName"];
                $arr_material_info_ary[0]["targeting_url"] = $this->tencentConfig->tencent_ck . urlencode($item["goUrl"]);
                $arr_material_info_ary[0]["monitor_url"][] = $this->tencentConfig->tencent_vw;
                $arr_material_info_ary[0]["file_info"][] = array("file_url" => $creativeUrl);
                if ($item["turl"] != "" && $item["turl"] != 'http://') {
                    $arr_material_info_ary[0]["monitor_url"][] = $item["turl"];
                }
                $str_json = json_encode($arr_material_info_ary);
                $str_post = "dsp_id=" . $this->tencentConfig->tencent_dsp_id . "&token=" . $this->tencentConfig->tencent_token . "&order_info=" . urlencode($str_json);
                $str_return = $this->postCurl($this->tencentConfig->tencent_material_banner_add, $str_post);
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return)) {
                        if ($arr_return['ret_code'] === 0 && $arr_return['ret_msg'] == '完全正确') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['orderId'] = $dspOrderId;
                            $update_data['reason'] = "";
                            $update_data['fileType'] = 2;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveMaterial("adxId = 5 and advertId = {$tencentDspOrderId}",$update_data);
                        } else {
                            foreach ($arr_return['ret_msg'] as $rCode => $rItem) {
                                $reason = $this->tencentConfig->tencent_error[$rCode] ? $this->tencentConfig->tencent_error[$rCode] : '';
                                $update_data = array();
                                $update_data['adxStatus'] = 2;
                                $update_data['reason'] = $reason;
                                $update_data['fileType'] = 2;
                                $update_data['orderId'] = $dspOrderId;
                                $update_data['uploadStatus'] = 0;
                                $update_data['mtime'] = date('Y-m-d H:i:s');
                                $this->saveMaterial("adxId = 5 and advertId = {$tencentDspOrderId}",$update_data);
                            }
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tencentMaterialUpload();
$obj->run();
?>
    
