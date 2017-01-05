<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/miaozhenConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\miaozhenConfig as miaozhenConfig;
use \library\base as base;

class miaozhenMaterialUpload extends base{

    public $appName = 'miaozhenMaterialUpload';

    public function run(){
        $this->miaozhenConfig = new miaozhenConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getBannerMaterial(7,'3,99','0,1');
        $allowAccountIdAry = $this->getAllowAdvertiser(7);
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
            $sql = "select startDate,endDate,id as orderId from order_info where id in ($orderIdStr)";
            $arr_db_info = $this->select('again_main',$sql);
            $orderDateArr = array();
            foreach ($arr_db_info as $k => $item) {
                $arr_db_info[$k]['endDate'] = date("Y-m-d", strtotime("+1 year"));
                $arr_db_info[$k]['startDate'] = date("Y-m-d", strtotime($arr_db_info[$k]['startDate']));
                $orderDateArr[$item['orderId']] = $arr_db_info[$k];
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
                $orderId = $item['orderId'];
                $adAccountId = $allowAccountIdAry[$item['adAccountId']];
                $goUrl = $item["goUrl"];
                $companyName = $companyArr[$adAccountId]['companyName'];
                $monitorUrl = $item["turl"];
                $startDate = $orderDateArr[$item["orderId"]]["startDate"];
                $endDate = $orderDateArr[$item["orderId"]]["endDate"];
                $request[$i]["url"] = $fileUrl;
                $request[$i]["landingpage"] = $goUrl;
                $request[$i]["monitor"][] = $monitorUrl;
                $request[$i]["advertiser"] = $companyName;
                $request[$i]["startdate"] = $startDate;
                $request[$i]["enddate"] = $endDate;
                $db_data[$fileUrl]['id'] = $item['id'];
                $db_data[$fileUrl]['code'] = 1;
                $db_data[$fileUrl]['fileUrl'] = $item["fileUrl"];
                if (count($request) == 1 || $k == ($total - 1)) {
                    $request_json_arr = array();
                    $request_json_arr['material'] = $request;
                    $request_json_arr['token'] = $this->miaozhenConfig->miaozhen_token;
                    $request_json_arr['dspid'] = $this->miaozhenConfig->miaozhen_dsp_id;
                    $request_json = json_encode($request_json_arr);
                    $str_return = $this->postCurl($this->miaozhenConfig->miaozhen_upload, $request_json, "json");
                    $arr_return = $str_return['response'];
                    if ($arr_return['result'] === 0) {
                        foreach ($arr_return['message'] as $code => $item_message) {
                            foreach ($item_message as $item_file) {
                                $db_data[$item_file]['code'] = $code;
                            }
                        }
                        foreach ($db_data as $file => $db_message) {
                            $update_data = array();
                            $update_data['fileType'] = 1;
                            if ($db_message['code'] != 1) {
                                $update_data['reason'] = $this->miaozhenConfig->miaozhen_error_config[$db_message['code']];
                                $update_data['adxStatus'] = 2;
                                $update_data['uploadStatus'] = 1;
                            } else {
                                $update_data['reason'] = '';
                                $update_data['adxStatus'] = 0;
                                $update_data['uploadStatus'] = 1;
                            }
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId = $adId and adxId = 7",$update_data);
                        }
                    }
                    $request = array();
                    $db_data = array();
                    $i = 0;
                }
            }
        }


        $uploadMaterialList = array();
        $orderIdAry = array();
        $adAccountIdAry = array();
        $arr_db_info = $this->getVideoMaterial(7,'3,99','0,1');
        $allowAccountIdAry = $this->getAllowAdvertiser(7);
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
            $sql = "select startDate,endDate,id as orderId from order_info where id in ($orderIdStr)";
            $arr_db_info = $db->getDb($sql, "again_main")->fetchAll($sql);
            $orderDateArr = array();
            foreach ($arr_db_info as $k => $item) {
                $arr_db_info[$k]['endDate'] = date("Y-m-d", strtotime("+1 year"));
                $arr_db_info[$k]['startDate'] = date("Y-m-d", strtotime($arr_db_info[$k]['startDate']));
                $orderDateArr[$item['orderId']] = $arr_db_info[$k];
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
                $fileUrl = $item["fileUrl"];
                $creativeId = $item['id'];
                $orderId=$item['orderId'];
                $adAccountId= $allowAccountIdAry[$item['adAccountId']];
                $goUrl = $item["goUrl"];
                $companyName = $companyArr[$adAccountId]['companyName'];
                $monitorUrl = $item["turl"] ? $item["turl"] : '';
                $startDate = $orderDateArr[$item["orderId"]]["startDate"];
                $endDate = $orderDateArr[$item["orderId"]]["endDate"];
                $request[$i]["url"] = $this->config['video_file_path'] . $fileUrl;
                $request[$i]["landingpage"] = $goUrl;
                $request[$i]["monitor"][] = $monitorUrl;
                $request[$i]["advertiser"] = $companyName;
                $request[$i]["startdate"] = $startDate;
                $request[$i]["enddate"] = $endDate;
                $db_data[$fileUrl]['id'] = $item['id'];
                $db_data[$fileUrl]['code'] = 1;
                if (count($request) == 1 || $k == ($total - 1)) {

                    $request_json_arr = array();
                    $request_json_arr['material'] = $request;
                    $request_json_arr['token'] = $this->miaozhenConfig->miaozhen_token;
                    $request_json_arr['dspid'] = $this->miaozhenConfig->miaozhen_dsp_id;
                    $request_json = json_encode($request_json_arr);
                    $str_return = $this->postCurl($this->miaozhenConfig->miaozhen_upload, $request_json, "json");
                    $arr_return = $str_return['response'];
                    if ($arr_return['result'] === 0) {
                        foreach ($arr_return['message'] as $code => $item_message) {
                            foreach ($item_message as $item_file) {
                                $db_data[str_replace($this->config['video_file_path'], '', $item_file)]['code'] = $code;
                            }
                        }
                        foreach ($db_data as $file => $db_message) {
                            $update_data = array();
                            $update_data['fileType'] = 2;
                            if ($db_message['code'] != 1) {
                                $update_data['reason'] = $this->miaozhenConfig->miaozhen_error_config[$db_message['code']];
                                $update_data['adxStatus'] = 2;
                                $update_data['uploadStatus'] = 1;
                            } else {
                                $update_data['reason'] = '';
                                $update_data['adxStatus'] = 0;
                                $update_data['uploadStatus'] = 1;
                            }
                            $update_data['mtime'] = date("Y-m-d H:i:s");
                            $this->saveMaterial("advertId = {$db_message['id']} and adxId = 7",$update_data);
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

$obj = new miaozhenMaterialUpload();
$obj->run();
?>