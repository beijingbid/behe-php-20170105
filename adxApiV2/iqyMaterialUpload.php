<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/iqyConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\iqyConfig as iqyConfig;
use \library\base as base;

class iqyMaterialUpload extends base{

    public $appName = 'iqyMaterialUpload';

    public function run(){
        $this->iqyConfig = new iqyConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $allowAccountIdAry = $this->getAllowAdvertiser(55);
        $uploadUrl = $this->iqyConfig->iqy_material_video_add;
        $dsp_token = $this->iqyConfig->iqy_dsp_token;
        $arr_db_info = $this->getBannerMaterial(55,'3,99','0,1');
        foreach ($arr_db_info as $key => $value) {
            $beheAccountId = $allowAccountIdAry[$value['adAccountId']];
            if(!in_array($beheAccountId,$allowAccountIdAry)):
                continue;
            endif;
            $ext = $this->config['file_type'][$value['type']];
            $orderId = $value['orderId'];
            $header = array(
                "dsp_token:" . $dsp_token,
                "ad_id:" . $beheAccountId,
                "Content-Type: application/octet-stream;charset=UTF-8",
                "click_url:" . urlencode($value['goUrl']),
                "video_id:" . $value["id"],
                "ad_type:2",
                "file_name:" . $this->config['file_path'] . $value["fileUrl"],
                "platform:1",
                "end_date:" . date("Ymd", strtotime("+360 day"))
            );
            $str_return = iqyPost($uploadUrl, $header, $this->config['file_path'] . $value["fileUrl"]);
            $arr_return = json_decode($str_return, true);
            if (!empty($arr_return)) {
                if ($arr_return['code'] == 0) {
                    $update_data = array();
                    $update_data['adxStatus'] = 0;
                    $update_data['reason'] = "";
                    $update_data['uploadStatus'] = 1;
                    $update_data['fileType'] = 1;
                    $update_data['orderId'] = $orderId;
                    $update_data['exFileId'] = $arr_return['m_id'];
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 55 and advertId = {$value["id"]}",$update_data);
                } else {
                    $this->log(json_encode($header));
                    $this->log("advertId：" . $value["id"] . "上传失败！" . $str_return);
                }
            }
        }

        $arr_db_info = $this->getVideoMaterial(55);

        foreach ($arr_db_info as $key => $value) {
            $beheAccountId = $allowAccountIdAry[$value['adAccountId']];
            if(!in_array($beheAccountId,$allowAccountIdAry)):
                continue;
            endif;
            $ext = $this->config['file_type'][$value['type']];
            $orderId = $value['orderId'];
            $header = array(
                "dsp_token:" . $dsp_token,
                "ad_id:" . $beheAccountId,
                "Content-Type: application/octet-stream;charset=UTF-8",
                "click_url:" . urlencode($value['goUrl']),
                "video_id:" . $value["id"],
                "ad_type:1",
                "file_name:" . $this->config['video_file_path'] . $value["fileUrl"],
                "platform:1",
                "duration:" . $value["duration"],
                "end_date:" . date("Ymd", strtotime("+360 day"))
            );
            $str_return = iqyPost($uploadUrl, $header, $this->config['video_file_path'] . $value["fileUrl"]);
            if (!empty($str_return)) {
                $arr_return = json_decode($str_return, true)['response'];
                if ($arr_return['code'] == 0) {
                    $update_data = array();
                    $update_data['adxStatus'] = 0;
                    $update_data['reason'] = "";
                    $update_data['orderId'] = $orderId;
                    $update_data['uploadStatus'] = 1;
                    $update_data['fileType'] = 2;
                    $update_data['exFileId'] = $arr_return['m_id'];
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 55 and advertId = {$value["id"]}",$update_data);
                } else {
                    $update_data = array();
                    $update_data['adxStatus'] =2;
                    $update_data['reason'] =$arr_return['code'].$arr_return['desc'];
                    $update_data['orderId'] = $orderId;
                    $update_data['uploadStatus'] = 0;
                    $update_data['fileType'] = 2;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 55 and advertId = {$value["id"]}",$update_data);
                }
            } else {
                $this->log("advertId：" . $value["id"] . "上传失败！" . $str_return);
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new iqyMaterialUpload();
$obj->run();
?>
