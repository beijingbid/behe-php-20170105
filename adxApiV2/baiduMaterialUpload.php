<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__file__).'/../'));

require_once APP_PATH . '/config/baiduConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\baiduConfig as baiduConfig;
use \library\base as base;

class baiduMaterialUpload extends base{

    public $appName = 'baiduMaterialUpload';

    public function run(){
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $this->baiduConfig = new baiduConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_db_info = $this->getBannerMaterial(11,99,'0,1');
        $allowAccountIdAry = $this->getAllowAdvertiser(11);
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
            $sql = "select isWhiteUser ,beheAccountId ,adxStatus from adx_account where adxId=11 and beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_v1_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $accountList[$item['beheAccountId']] = $item;
            }
            $sql = "select xId,parent,sId as id from ig_industry_baidu where level=2";
            $redisKey = md5($sql);
            $categoryDb = getRedis($redisKey, 'local');
            if (!$categoryDb) {
                $categoryDb = $this->select('again_v1_info',$sql);
                setRedis($redisKey, $categoryDb, 'local');
            }
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCategyId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_v1_main',$sql);
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
                $materialId = $item['id'];
                $creativeUrl = $this->config['file_path']. $item['fileUrl'];
                $advertiserId = $item['beheAccountId'];
                $adAccountId=$advertiserId;
                $orderId=$item['orderId'];
                $landingPage = $item['jumpUrl'];
                $targetUrl = $this->baiduConfig->baidu_ck . urlencode($item['jumpUrl']);
                $monitorUrls = $this->baiduConfig->baidu_vw;
                $monitorUrlsAry=array($monitorUrls);
                $width = $item['width'];
                $height = $item['height'];
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->baiduConfig->baidu_file_ext_type[$file_ext_name];
                if($item['monitorUrl']!=''&&$item['monitorUrl']!='http://'){
                    $monitorUrlsAry[]=$item['monitorUrl'];
                }
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                if ($creativeTradeId == 0) {
                    $update_data = array();
                    $update_data['adxStatus'] = 99;
                    $update_data['reason'] = "活动分类不正确";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 11 and advertId = {$materialId}",$update_data);
                    continue;
                }

                if (!$this->baiduConfig->baidu_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型{$file_ext_name}";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 11 and advertId = {$materialId}",$update_data);
                    continue;
                }
                if (!empty($accountList[$advertiserId])) {
                    if ($accountList[$advertiserId]['adxStatus'] == 99||($accountList[$advertiserId]['adxStatus'] == 2) || ($accountList[$advertiserId]['adxStatus'] == 0 && $accountList[$advertiserId]['isWhiteUser'] != 1)) {
                        $update_data = array();
                        $update_data['adxStatus'] = 99;
                        if ($accountList[$advertiserId]['adxStatus'] == 0) {
                            $update_data['reason'] = "广告主审核中";
                        } elseif ($accountList[$advertiserId]['adxStatus'] == 2) {
                            $update_data['reason'] = "广告主审核被拒";
                        }
                        $update_data['uploadStatus'] = 0;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId=11 and advertId={$materialId}",$update_data);
                        continue;
                    }
                } else {
                    $update_data = array();
                    $update_data['adxStatus'] = 99;
                    $update_data['reason'] = "广告主未上传";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId=11 and advertId={$materialId}",$update_data);
                    continue;
                }

                if (!$this->baiduConfig->baidu_size[$str_size_temp]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "{$str_size_temp}尺寸不符合要求";
                    $update_data['uploadStatus'] = 0;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 11 and advertId = {$materialId}",$update_data);
                    continue;
                }
                $arr_material_info_ary = array();
                $arr_material_info_ary[0]["creativeId"] = $materialId;
                $arr_material_info_ary[0]["creativeUrl"] = $creativeUrl;
                $arr_material_info_ary[0]["advertiserId"] = $advertiserId;
                $arr_material_info_ary[0]["landingPage"] = $landingPage;
                $arr_material_info_ary[0]["targetUrl"] = $targetUrl;
                $arr_material_info_ary[0]["monitorUrls"] = $monitorUrlsAry;
                $arr_material_info_ary[0]["type"] = $type;
                $arr_material_info_ary[0]["width"] = $width;
                $arr_material_info_ary[0]["height"] = $height;
                $arr_material_info_ary[0]["creativeTradeId"] = $creativeTradeId;
                $arr_send_json = array();
                $arr_send_json["request"] = $arr_material_info_ary;
                $arr_send_json["authHeader"]["dspId"] = $this->baiduConfig->baidu_dsp_id;
                $arr_send_json["authHeader"]["token"] = $this->baiduConfig->baidu_token;
                $str_json = json_encode($arr_send_json);
                if($item['uploadStatus'] == 0){
                    $str_return = $this->postCurl($this->baiduConfig->baidu_material_add, $str_json, "json");
                }else{
                    $str_return = $this->postCurl($this->baiduConfig->baidu_material_update, $str_json, "json");
                }
                if (!empty($str_return) && $str_return['httpCode'] == 200) {
                    $arr_return = $str_return['response'];
                    if (!empty($arr_return)) {
                        if ($arr_return['status'] === 0||$arr_return['errors'][0]['code']==2002) {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $update_data['orderId'] = $orderId;
                            $this->saveMaterial("adxId = 11 and advertId = {$materialId}",$update_data);

                        } elseif ($arr_return['status'] == 2) {
                            info_log($str_return);
                            info_log($str_json);
                            $update_data = array();
                            $update_data['adxStatus'] = 2;
                            $update_data['reason'] = $arr_return['errors'][0]['message'];
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $update_data['orderId'] = $orderId;
                            $this->saveMaterial("adxId = 11 and advertId = {$materialId}",$update_data);
                        } else {
                            $this->log($str_return);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new baiduMaterialUpload();
$obj->run();
?>
    
