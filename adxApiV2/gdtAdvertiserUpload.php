<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/gdtConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\gdtConfig as gdtConfig;
use \library\base as base;

class gdtAdvertiserUpload extends base{

    public $appName = 'gdtAdvertiserUpload';

    public function run(){
        $this->gdtConfig = new gdtConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $str_dsp_id = $this->gdtConfig->gdt_dsp_id;
        $str_token = $this->gdtConfig->gdt_token;
        $ad_qualifications = $this->gdtConfig->gdt_material_qualifications;
        $str_auth = $str_token;
        $ig_old_db_arr = $this->select("again_info","select * from ig_industry_gdt");
        $ig_old_arr = array();
        foreach ($ig_old_db_arr as $item) {
            $ig_old_arr[$item['xId']] = $item;
        }
        $arr_client_list = $this->getAdvertiser(49,99);
        //0 组织机构代码 1组织机构资质2 营业执照3法人 4ICP 5完税
        $date = date('Y-m-d H:i:s');
        $qualification_file = array(0 => 3, 1 => 4);
        foreach ($arr_client_list as $item) {
            $accountId = $item['beheAccountId'];
            $companyName = $item["businessLicenseName"] ? $item["businessLicense"] : $item["companyName"];
            $business_license = $item['qualificationInfo']['businessLicense'];
            $icp = $item['qualificationInfo']['ICP']; 
            $request = array();
            if ($ig_old_arr[$item["companySubCateId"]]) {
                $request[0]["industry_id"] = $ig_old_arr[$item["companySubCateId"]]['sId'];
            } else {
                continue;
            }

            $request[0]["advertiser_id"] = $accountId;
            $request[0]["name"] = $companyName;
            $request[0]["homepage"] = $item["companyUrl"];

            if($item['uploadStatus'] == 0):
                //$request[0]["brand_name"] = $brandInfoAry[0] ? $brandInfoAry[0] : '';
                //$request[0]["brand_logo_image_url"] = $brandInfoAry[1] ? $config->domain . $brandInfoAry[1] : '';
                $api_url = $this->gdtConfig->gdt_advertiser_add;
            else:
                $request[0]["brand_name"] = $item['brandName'] ? $item['brandName'] : '';
                $request[0]["brand_logo_image_url"] = $item['brandLogo'] ? $this->config['domain'] . $item['brandLogo'] : '';
                $api_url = $this->gdtConfig->gdt_advertiser_update;
            endif;

            $offest = 0;
            if ($business_license) {
                $request[0]["qualifications"][$offest]['name'] = "营业执照";
                $request[0]["qualifications"][$offest]['file_url'] = $this->config['domain'] . $business_license;
                $offest++;
            }
            if ($icp) {
                $request[0]["qualifications"][$offest]['name'] = "ICP";
                $request[0]["qualifications"][$offest]['file_url'] = $this->config['domain'] . $icp;
                $offest++;
            }

            if (!empty($item['gdtQualifications'])) {
                $gdtQualificationsAry = json_decode($item['gdtQualifications'], true);
                foreach ($gdtQualificationsAry as $qualificationk => $qualificationItem) {
                    $request[0]["qualifications"][$offest]['name'] = $qualificationItem['name'];
                    $request[0]["qualifications"][$offest]['file_url'] = $this->config['domain'] . $qualificationItem['file'];
                    $offest++;
                }
            }
            if (!$request[0]["qualifications"]) {
                $reason = "ICP,营业执照必须上传一个";
                $update_data = array();
                $update_data['reason'] = $reason;
                $update_data['adxStatus'] = 99;
                $update_data['uploadStatus'] = 0;
                $update_data['mtime'] = $date;
                $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 49",$update_data);
                continue;
            }
            $request_arr = array();
            $request_arr['data'] = $request;
            $request_json = json_encode($request_arr);
            $str_return = $this->postCurl($api_url, $request_json, $str_auth);
            if (!empty($str_return)) {
                $arr_return = $str_return['response'];
                if ($arr_return['data'][0]['code'] == '0') {

                    $update_data = array();
                    $update_data['reason'] = "";
                    $update_data['adxStatus'] = 0;
                    $update_data['uploadStatus'] = 1;
                    $update_data['mtime'] = $date;
                    $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 49",$update_data);
                    foreach ($request[0]["qualifications"] as $qk => $qitem) {
                        $qualification_arr = array();
                        $qualification_arr['beheAccountId'] = $accountId;
                        $qualification_arr['qualification'] = $qitem['file_url'];
                        $qualification_arr['adxId'] = 49;
                        $qualification_arr['name'] = $qitem['name'];
                        $qualification_arr['ctime'] = date('Y-m-d H:i:s');
                        $qualification_arr['qid'] = $qualification_file[$qk];
                        $this->add('again_main','adx_account_qulification',$qualification_arr);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new gdtAdvertiserUpload();
$obj->run();
?>
