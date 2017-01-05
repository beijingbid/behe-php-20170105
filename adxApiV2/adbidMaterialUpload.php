<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/adbidConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\adbidConfig as adbidConfig;
use \library\base as base;

class adbidMaterialUpload extends base{

    public $appName = 'adbidMaterialUpload';

    public function run(){
        $this->adbidConfig = new adbidConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAry = $this->getAllowAdvertiser(43);
        $arr_db_info = $this->getBannerMaterial(43,'3,99','0,1');
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
            $str_user_name = $this->adbidConfig->adbid_dsp_id;
            $str_token = $this->adbidConfig->adbid_token;
            $str_auth = base64_encode($str_user_name . ":" . $str_token);
            $str_auth = $str_user_name . ":" . $str_token;
            $str_sql = "select * from adbid_material_size";
            $arr_size_db = $this->select('again_info',$str_sql);
            $arr_size_list = array();
            foreach ($arr_size_db as $item) {
                $arr_size_list[$item['width'] . "x" . $item['height']] = $item;
            }
            $tmp_arr = array();
            $tmp_arr['swf'] = file_get_contents(APP_PATH . "/tmp/adbid/swf.html");
            $tmp_arr['img'] = file_get_contents(APP_PATH . "/tmp/adbid/img.html");
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCategyId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();
            $sql = "select xId,parent,sId , id from ig_industry_adbid order by level asc";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList['behe'][$item['xId']] = $item;
                $categoryList['adbid'][$item['sId']] = $item;
            }
            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categoryList['behe'][$item['companySubCateId']]) {
                    $campaignList[$item['id']]['smlCate'] = $categoryList['behe'][$item['companySubCateId']]['sId'];
                    $campaignList[$item['id']]['midCate'] = $categoryList['behe'][$item['companySubCateId']]['parent'];
                    $campaignList[$item['id']]['bigCate'] = $categoryList['adbid'][$campaignList[$item['id']]['midCate']]['parent'];
                } else {
                    $campaignList[$item['id']]['smlCate'] = 0;
                }
            }
            foreach ($uploadMaterialList as $item) {
                $campaignId = $item['campaignId'];
                $adAccountId = $item['beheAccountId'];
                $orderId = $item['orderId'];
                $bigCate = $campaignList[$campaignId]['bigCate'];
                $midCate = $campaignList[$campaignId]['midCate'];
                $smlCate = $campaignList[$campaignId]['smlCate'];
                if ($smlCate == 0) {
                    continue;
                }
                $materialId = $item['id'];
                $fileUrl = $this->config['file_path'] . $item['fileUrl'];
                $accountId = $item['adAccountId'];
                $monitorUrl = $item["monitorUrl"];
                $upload = "@" . $this->config['banner_file_system_path'] . $item["fileUrl"];
                $name = $item["name"];
                $width = $item['width'];
                $height = $item['height'];
                $materialFormat = $this->config['file_type'][$item['type']];
                if ($materialFormat == 'swf') {
                    $creativeCode = $tmp_arr['swf'];
                } else {
                    $creativeCode = $tmp_arr['img'];
                }
                $urlencodeGoUrl = urlencode($goUrl);
                $creativeCode = str_replace("%%goUrl%%", $urlencodeGoUrl, $creativeCode);
                $creativeCode = str_replace("%%creative_url%%", $fileUrl, $creativeCode);
                $creativeCode = str_replace("%%width%%", $width, $creativeCode);
                $creativeCode = str_replace("%%height%%", $height, $creativeCode);
                if (!$arr_size_list[$width . "x" . $height]) {
                    continue;
                }
                $creativeSize = $arr_size_list[$width . "x" . $height]['id'];
                $send_arr = array();
                $send_arr['upload'] = $upload;
                $send_arr['name'] = $name;
                $str_return_file = $this->postCurl($this->adbidConfig->adbid_material_add, $send_arr, "", $str_auth);
                $arr_return_file = $str_return_file['response'];

                if ($arr_return_file['id'] && $arr_return_file['url']) {
                    $send_arr = array();
                    $send_arr['creativeName'] = $name;
                    $send_arr['materialFormat'] = $materialFormat;
                    $send_arr['creativeNo'] = (string) $materialId;
                    $send_arr['creativeSize'] = (string) $creativeSize;
                    $send_arr['bigCate'] = (string) $bigCate;
                    $send_arr['midCate'] = (string) $midCate;
                    $send_arr['smlCate'] = (string) $smlCate;
                    $send_arr['creativeCode'] = $creativeCode;
                    $str_return_code = $this->postCurl($this->adbidConfig->adbid_creative_add, json_encode($send_arr), "json", $str_auth);
                    $arr_return_code =$str_return_code['response'];

                    if ($arr_return_code['id']) {
                        $update_data = array();
                        $update_data['adxStatus'] = 0;
                        $update_data['reason'] = "";
                        $update_data['orderId'] = $orderId;
                        $update_data['uploadStatus'] = 1;
                        $update_data['exFileUrl'] = $arr_return_file['url'];
                        $update_data['exFileId'] = $arr_return_file['id'];
                        $update_data['exAdId'] = $arr_return_code['id'];
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 43 and advertId = {$materialId}",$update_data);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new adbidMaterialUpload();
$obj->run();
?>
