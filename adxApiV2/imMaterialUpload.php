<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/imConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\imConfig as imConfig;
use \library\base as base;

class imMaterialUpload extends base{

    public $appName = 'imMaterialUpload';

    public function run(){
        $this->imConfig = new imConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAry = $this->getAllowAdvertiser(19);
        $arr_db_info = $this->getBannerMaterial(19,'3,99','0,1');
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
            $orderIdStr = implode(',', $orderIdAry);
            $sql = "select startDate,endDate,id from order_info where id in ($orderIdStr)";
            $arr_db_info = $this->select('again_main',$sql);
            $orderDateArr = array();
            foreach ($arr_db_info as $k => $item) {
                $arr_db_info[$k]['endDate'] = date("Y-m-d", strtotime("+1 year"));
                $arr_db_info[$k]['startDate'] = date("Y-m-d", strtotime($arr_db_info[$k]['startDate']));
                $orderDateArr[$item['id']] = $arr_db_info[$k];
            }
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select beheAccountId,companyName from behe_account where beheAccountId in ($accountIdStr)";
            $arr_db_info = $this->select("again_main",$sql);
            $companyArr = array();
            foreach ($arr_db_info as $k => $item) {
                $companyArr[$item['beheAccountId']] = $arr_db_info[$k];
            }
            $i = 0;
            $total = count($uploadMaterialList);
            $request = array();
            $db_data = array();
            foreach ($uploadMaterialList as $k => $item) {
                $fileUrl = $this->config['file_path'] . $item["fileUrl"];
                $creativeId = $item['id'];
                $orderId=$item['orderId'];
                $adAccountId=$item['beheAccountId'];
                $goUrl = $item["goUrl"];
                $companyName = $companyArr[$item["beheAccountId"]]['companyName'];
                $monitorUrl = $item["turl"];
                $startDate = $orderDateArr[$item["orderId"]]["startDate"];
                $endDate = $orderDateArr[$item["orderId"]]["endDate"];
                $width = $item['width'];
                $height = $item['height'];
                $name = $item["name"];

                $request[$i]["url"] = $fileUrl;
                $request[$i]["landingpage"] = $goUrl;
                $request[$i]["monitor"][] = $monitorUrl;
                $request[$i]["advertiser"] = $companyName;
                $request[$i]["startdate"] = $startDate;
                $request[$i]["size"] = $width . "*" . $height;
                $request[$i]["enddate"] = $endDate;
                $request[$i]["name"] = $name;

                if (count($request) == 1 || $k == ($total - 1)) {
                    $request_json_arr = array();
                    $request_json_arr['material'] = $request;
                    $request_json_arr['token'] = $this->imConfig->im_token;
                    $request_json_arr['dspid'] = $this->imConfig->im_dsp_id;
                    $request_json = json_encode($request_json_arr);
                    $str_return = $this->postCurl($this->imConfig->im_upload, $request_json, "json");
                    $arr_return = $str_return['response'];
                    if (!empty($arr_return)) {
                        if (($arr_return['result'] === 0 && empty($arr_return['message'])) || $arr_return['message'][0]['error'] == 'url已经上传了') {
                            $update_data = array();
                            $update_data['fileType'] = 1;
                            $update_data['reason'] = '';
                            $update_data['adxStatus'] = 0;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId ='{$item['id']}' and adxId = 19",$update_data);
                        } else {
                            $update_data = array();
                            $update_data['fileType'] = 1;
                            $update_data['reason'] = $arr_return['message'][0]['error'];
                            $update_data['adxStatus'] = 2;
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId ='{$item['id']}' and adxId = 19",$update_data);
                        }
                    }
                    $request = array();
                    $db_data = array();
                    $i = 0;
                }
            }
        }


        $arr_db_info = $this->getVideoMaterial(9,'3,99','0,1');
        $allowAccountIdAry = $this->getAllowAdvertiser(9);
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
            $orderIdStr = implode(',', $orderIdAry);
            $sql = "select startDate,endDate,id from order_info where id in ($orderIdStr)";
            $arr_db_info = $this->select('again_main',$sql);
            $orderDateArr = array();
            foreach ($arr_db_info as $k => $item) {
                $arr_db_info[$k]['endDate'] = date("Y-m-d", strtotime("+1 year"));
                $arr_db_info[$k]['startDate'] = date("Y-m-d", strtotime($arr_db_info[$k]['startDate']));
                $orderDateArr[$item['id']] = $arr_db_info[$k];
            }
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select beheAccountId,companyName from behe_account where beheAccountId in ($accountIdStr)";
            $arr_db_info = $this->select('again_main',$sql);
            $companyArr = array();
            foreach ($arr_db_info as $k => $item) {
                $companyArr[$item['beheAccountId']] = $arr_db_info[$k];
            }
            $i = 0;
            $total = count($uploadMaterialList);
            $request = array();
            $db_data = array();
            foreach ($uploadMaterialList as $k => $item) {
                $fileUrl = $item["youkuFileUrl"];
                $creativeId = $item['id'];
                $goUrl = $item["goUrl"];
                $companyName = $companyArr[$item["beheAccountId"]]['companyName'];
                $monitorUrl = $item_material["turl"] ? $item_material["turl"] : '';
                $startDate = $orderDateArr[$item["orderId"]]["startDate"];
                $endDate = $orderDateArr[$item["orderId"]]["endDate"];
                $orderId=$item['orderId'];
                $adAccountId=$item['beheAccountId'];
                $width = $item['width'];
                $height = $item['height'];
                $name = $item["name"];
                $request[$i]["size"] = $width . "*" . $height;
                $request[$i]["name"] = $name;
                $request[$i]["url"] = $fileUrl;
                $request[$i]["landingpage"] = $goUrl;
                $request[$i]["monitor"][] = $monitorUrl;
                $request[$i]["advertiser"] = $companyName;
                $request[$i]["startdate"] = $startDate;
                $request[$i]["enddate"] = $endDate;
                if (count($request) == 1 || $k == ($total - 1)) {
                    $request_json_arr = array();
                    $request_json_arr['material'] = $request;
                    $request_json_arr['token'] = $this->imConfig->im_token;
                    $request_json_arr['dspid'] = $this->imConfig->im_dsp_id;
                    $request_json = json_encode($request_json_arr);
                    $str_return = $this->postCurl($this->imConfig->im_upload, $request_json, "json");
                    $arr_return = $str_return['response'];
                    if (!empty($arr_return)) {
                        if (($arr_return['result'] === 0 && empty($arr_return['message'])) || $arr_return['message'][0]['error'] == 'url已经上传了') {
                            $update_data = array();
                            $update_data['fileType'] = 2;
                            $update_data['reason'] = '';
                            $update_data['adxStatus'] = 0;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId='{$item['id']}' and adxId = 19",$update_data);
                        } else {
                            $update_data = array();
                            $update_data['fileType'] = 2;
                            $update_data['reason'] = $arr_return['message'][0]['error'];
                            $update_data['exchangeStatus'] = 2;
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId='{$item['id']}' and adxId = 19",$update_data);
                        }
                    }
                    $request = array();
                    $db_data = array();
                    $i = 0;
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new imMaterialUpload();
$obj->run();
?>