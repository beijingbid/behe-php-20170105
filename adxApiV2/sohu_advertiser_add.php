<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/sohuConfig.php';
require_once APP_PATH . '/library/sohu.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\sohuConfig as sohuConfig;
use \library\base as base;

class sohuAdvertiserUpload extends base{

    public $appName = 'sohuAdvertiserUpload';

    public function run(){
        $this->sohuConfig = new sohuConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $key = $this->sohuConfig->sohu_dsp_id;
        $secret = $this->sohuConfig->sohu_token;
        $arr_account = array();
        $arr_client_list = array();
        //0 组织机构代码 1组织机构资质2 营业执照3法人 4ICP 5完税
        $arr_account_info = $this->getAdvertiser(17,99,'0,1');
        foreach ($arr_account_info as $account_item) {
            if($account_item['uploadStatus'] == 0):
                $apiurl = $this->sohuConfig->sohu_advertiser_create;
            else:
                $apiurl = $this->sohuConfig->sohu_advertiser_update;
            endif;
            $intelligence_arr = $account_item['qualificationInfo'];
            if ($intelligence_arr['organization'] != '' && $intelligence_arr['businessLicense']) {
                $accountId = $account_item['beheAccountId'];
                //if($accountId != "1697005811"){continue;print_r($account_item);}
                if ($intelligence_arr['businessLicense']) {
          
                    $post = array(
                        'oganization_code' => trim($intelligence_arr['organization']),
                        'customer_name' => trim($account_item['companyName']),
                        'customer_key' => $account_item['customerKey']
                    );
                    $auth = new \Auth($key, $secret);
                    $qstring = $auth->setMethod('post')->setUrl($apiurl)->setParams($post)->queryString();
                    $file_post = array(
                        'oganization_license' => $this->config['file_logo_system_path'] . $intelligence_arr['businessLicense']
                    );
                    $q_arr = explode('&', $qstring);
                    $file_post['oganization_license'] = new \CURLFile($file_post['oganization_license']);
                    foreach ($q_arr as $item) {
                        $temp = explode('=', $item);
                        $param = rawurldecode($temp[1]);
                        $file_post[$temp[0]] = $param;
                    }
                    $str_return = $auth->postCurl($apiurl, $file_post);
                    if (!empty($str_return)) {
                        $return_arr = json_decode($str_return, true);
                        if ($return_arr['status'] == true) {
                            $db_data = array();
                            $db_data['adxStatus'] = 0;
                            $db_data['uploadStatus'] = 1;
                            $db_data['uploadName'] = $account_item['companyName'];
                            $db_data['mtime'] = date('Y-m-d H:i:s');
                            $db_data['customerKey'] = $return_arr['content'];
                            $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 17",$db_data);
                            $db_data = array();
                            $db_data['beheAccountId'] = $accountId;
                            $db_data['qualification'] = $intelligence_arr['organization'];
                            $db_data['adxId'] = 17;
                            $db_data['qid'] = 1;
                            $db_data['name'] = "组织机构号码";
                            $db_data['ctime'] = date('Y-m-d H:i:s');
                            $db_data['mtime'] = date('Y-m-d H:i:s');
                            $this->add("again_v1_main","adx_account_qualification",$db_data);
                            $db_data = array();
                            $db_data['beheAccountId'] = $accountId;
                            $db_data['qualification'] = $intelligence_arr['businessLicense'];
                            $db_data['adxId'] = 17;
                            $db_data['name'] = "组织机构证";
                            $db_data['qid'] = 2;
                            $db_data['ctime'] = date('Y-m-d H:i:s');
                            $db_data['mtime'] = date('Y-m-d H:i:s');
                            $this->add("again_v1_main","adx_account_qualification",$db_data);
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new sohuAdvertiserUpload();
$obj->run();
?>
