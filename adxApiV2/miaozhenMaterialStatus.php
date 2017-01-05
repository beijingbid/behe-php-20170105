<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/miaozhenConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\miaozhenConfig as miaozhenConfig;
use \library\base as base;

class miaozhenMaterialStatus extends base{

    public $appName = 'miaozhenMaterialStatus';

    public function run(){
        $this->miaozhenConfig = new miaozhenConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_db_info = $this->getWaitStatusMaterial("adxId = 7 and adxStatus = 0 order by id desc");
        $date = date('Y-m-d H:i:s');
        $arr_materialurl = array();
        $total = count($arr_db_info);
        $db_file_url_ary = array();
        foreach ($arr_db_info as $k => $item) {
            $orderId=$item['orderId'];
            if ($item['fileType'] == 2) {
                $arr_materialurl[] = $this->config['video_file_path'] . $item['fileUrl'];
                $db_file_url_ary[$this->config['video_file_path'] . $item['fileUrl']] = $item['fileUrl'];
            } else {
                $arr_materialurl[] = $this->config['file_path'] . $item['fileUrl'];
                $db_file_url_ary[$this->config['file_path'] . $item['fileUrl']] = $item['fileUrl'];
            }

            if (count($arr_materialurl) == 1 || ($total - 1) == $k) {

                //获取优酷素材审核状态
                $request = array();
                $request['dspid'] = $this->miaozhenConfig->miaozhen_dsp_id;
                $request['token'] = $this->miaozhenConfig->miaozhen_token;
                $request['materialurl'] = $arr_materialurl;
                $request_json = json_encode($request);
                $str_return = $this->postCurl($this->miaozhenConfig->miaozhen_upload_status, $request_json, "json");
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (is_array($arr_return) && !empty($arr_return)) {
                        $arr_temp = $arr_return["message"]["records"];
                        if (!empty($arr_temp)) {
                            foreach ($arr_temp as $item_file_url) {
                                $result = $item_file_url["result"];
                                $reason = $item_file_url["reason"];
                                $str_file_url = $item_file_url["url"];
                                $db_file_url = $db_file_url_ary[$str_file_url];
                                $str_sql = "insert";
                                $update_data = array();
                                $update_data['reason'] = $reason ? $reason : '';
                                $update_data ['mtime'] = $date;
                                if ($result == "通过") {
                                    $update_data['adxStatus'] = 1;
                                    $this->saveMaterial("fileUrl = '{$db_file_url}' and adxId = 7",$update_data);
                                    if($orderId>0){
                                        order_queue($orderId);
                                    }
                                } else if ($result == "不通过") {
                                    $update_data['adxStatus'] = 2;
                                    $this->saveMaterial("fileUrl = '{$db_file_url}' and adxId = 7",$update_data);
                                } else if ($result == "待审核") {
                                    $update_data['adxStatus'] = 0;
                                    $this->saveMaterial("fileUrl = '{$db_file_url}' and adxId = 7",$update_data);
                                }
                            }
                        } else {
                            $update_data = array();
                            $update_data['uploadStatus'] = 0;
                            $update_data ['adxStatus'] = 2;
                            $this->saveMaterial("fileUrl = '{$db_file_url}' and adxId = 7",$update_data);
                        }
                    }
                }
                $arr_materialurl = array();
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new miaozhenMaterialStatus();
$obj->run();
?>