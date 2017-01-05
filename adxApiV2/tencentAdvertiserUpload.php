<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/tencentConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tencentConfig as tencentConfig;
use \library\base as base;

class tencentAdvertiserUpload extends base{

    public $appName = 'tencentAdvertiserUpload';

    public function run(){
        $this->tencentConfig = new tencentConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $arr_client_list = $this->getAdvertiser(5,99);
        $total = count($arr_client_list);
        $date = date("Y-m-d H:i:s");
        //0 组织机构代码 1组织机构资质2 营业执照3法人 4ICP 5完税
        $intelligenceNameAry = array(1 => "组织机构资质", 2 => "营业执照", 3 => "法人身份证", 4 => "ICP", 5 => "完税证");
        $otherIntelligenceNameAry = array(0 => "网络文化经营许可证");
        $qualification_file = array("组织机构资质" => 2, "营业执照" => 3, "法人身份证" => 5, "ICP" => 4, "完税证" => 6 ,"网络文化经营许可证" => 7);
        if ($arr_client_list) {
            foreach ($arr_client_list as $k => $account_item) {
                $request = array();
                $accountId = $account_item['beheAccountId'];
                $request[0]['name'] = $account_item['companyName'];
                if (strpos($account_item['companyUrl'], 'http') !== false) {
                    $request[0]['url'] = $account_item['companyUrl'];
                } else {
                    $request[0]['url'] = "http://" . $account_item['companyUrl'];
                }

                $request[0]['overwrite_qualification'] = true;
                $intelligenceAry = explode('|', $account_item['intelligence']);
                $offest = 0;
                foreach ($intelligenceAry as $ik => $file) {
                    $temp = explode('.', $file);
                    $ext = $temp[count($temp) - 1];
                    $allow_img = array('jpg' => true, 'jpeg' => true, 'png' => true, 'gif' => true);
                    if ($allow_img[$ext]) {
                        $request[0]['qualification_files'][$offest]['file_name'] = $intelligenceNameAry[$ik];
                        $request[0]['qualification_files'][$offest]['file_url'] = $this->config['img'] . $file;
                        $offest++;
                    }
                }
                $otherIntelligenceAry = explode('|', $account_item['otherIntelligence']);
                if (!empty($otherIntelligenceAry[0])) {
                    $request[0]['qualification_files'][$offest]['file_name'] = $otherIntelligenceNameAry[0];
                    $request[0]['qualification_files'][$offest]['file_url'] = $this->config['img'] . $otherIntelligenceAry[0];
                    $offest++;
                }
                if (!empty($account_item['gdtQualifications'])) {
                    $gdtQualificationsAry = json_decode($account_item['gdtQualifications'],true);
                    foreach ($gdtQualificationsAry as $gdtItem) {
                        $request[0]['qualification_files'][$offest]['file_name'] = $gdtItem['name'];
                        $request[0]['qualification_files'][$offest]['file_url'] = $this->config['img'] . $gdtItem['file'];
                        $offest++;
                    }
                }
                $request[0]['memo'] = $account_item['companyAddress'];
                if (empty($request[0]['qualification_files'])) {
                    continue;
                }
                $str_client_info = json_encode($request);
                $str_post = "dsp_id=" . $this->tencentConfig->tencent_dsp_id . "&token=" . $this->tencentConfig->tencent_token . "&client_info=" . $str_client_info;
                $str_return = $this->postCurl($this->tencentConfig->tencent_advertiser_add, $str_post);
                if (!empty($str_return)) {
                    $arr_return = $str_return['response'];
                    if (!empty($str_return)) {
                        if ($arr_return['ret_code'] == 0) {
                            $update_data = array();
                            $update_data['reason'] = '';
                            $update_data['adxStatus'] = 0;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = $date;
                            $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 5",$update_data);
                            foreach ($request[0]["qualification_files"] as $qk => $qitem) {
                                $qualification_arr = array();
                                $qualification_arr['beheAccountId'] = $accountId;
                                $qualification_arr['qualification'] = $qitem['file_url'];
                                $qualification_arr['adxId'] = 5;
                                $qualification_arr['name'] = $qitem['file_name'];
                                $qualification_arr['ctime'] = date('Y-m-d H:i:s');
                                $this->add('again_main','adx_account_qualification',$qualification_arr);
                            }
                        }
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tencentAdvertiserUpload();
$obj->run();
?>

