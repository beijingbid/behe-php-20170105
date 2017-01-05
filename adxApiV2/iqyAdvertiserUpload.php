<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once  APP_PATH .'/config/iqyConfig.php';
require_once  APP_PATH .'/library/base.php';

use adxApiV2\config\iqyConfig as iqyConfig;
use \library\base as base;

class iqyAdvertiserUpload extends base{

    public $appName = 'iqyAdvertiserUpload';

    public function run(){
        $this->iqyConfig = new iqyConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadUrl = $this->iqyConfig->iqy_advertiser_add;
        $dsp_token = $this->iqyConfig->iqy_dsp_token;
        $arr_db_info = $this->getAdvertiser(55);
        if ($arr_db_info) {
            foreach ($arr_db_info as $item) {
                $header = array(
                    "dsp_token:" . $dsp_token,
                    "Content-Type: application/octet-stream;charset=UTF-8",
                    "ad_id:" . $item["accountId"],
                    "op:create",
                    "name:" . urlencode($item["companyName"])
                );
                $header[]="file_name:". urlencode($item["beheAccountId"] . ".zip");
                $filename = $this->config['zipPath'] . $item["beheAccountId"] . ".zip";
                $zip = new ZipArchive();
                if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
                    $this->log('无法打开文件，或者文件创建失败');
                    $this->destoryPid($this->appName);
                    die;
                }

                $fileListAry = array();
                $temp = $item['qualificationInfo'];
                foreach ($temp as $k => $file) {
                    if (!empty($file) && $k > 0) {
                        $fileListAry[$this->config['file_logo_system_path'] . $file] = $this->config['file_logo_system_path'] . $file;
                    }
                }

                foreach ($fileListAry as $val) {
                    if (file_exists($val)) {
                        $zip->addFile($val, basename($val)); //第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下   
                    }
                }
                $zip->close(); //关闭   
                $str_return = iqyPost($uploadUrl, $header, $filename);
                $arr_return = json_decode($str_return, true);
                if($item['uploadStatus'] == 0){
                    if (!empty($arr_return)) {
                        if ($arr_return['code'] == 0) {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser("adxId=55 and beheAccountId={$item["beheAccountId"]}",$update_data);
                        } elseif ($arr_return['desc'] == 'advertiser exists') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser("adxId=55 and beheAccountId={$item["beheAccountId"]}",$update_data);
                        } else {
                            $this->log("accountId：" . $item["accountId"] . "{$item["companyName"]}上传失败！" . $str_return);
                        }
                    }
                }else{
                    if ($str_return != '') {
                        if ($arr_return['code'] == 0) {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser("adxId=55 and beheAccountId={$item["beheAccountId"]}",$update_data);
                        } elseif ($arr_return['desc'] == 'advertiser exists') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser("adxId=55 and beheAccountId={$item["beheAccountId"]}",$update_data);
                        } elseif ($arr_return['desc'] == '"op exception') {
                            $update_data = array();
                            $update_data['adxStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $this->saveAdvertiser("adxId=55 and beheAccountId={$item["beheAccountId"]}",$update_data);
                        } else {
                            $this->log("accountId：" . $item["accountId"] . "上传失败！" . $str_return);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new iqyAdvertiserUpload();
$obj->run();
?>
