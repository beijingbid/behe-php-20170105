<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/gdtConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\gdtConfig as gdtConfig;
use \library\base as base;

class gdtAdvertiserQualifications extends base{

    public $appName = 'gdtAdvertiserQualifications';

    public function run(){
        $this->gdtConfig = new gdtConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $str_dsp_id = $this->gdtConfig->gdt_dsp_id;
        $str_token = $this->gdtConfig->gdt_token;
        $api_url = $this->gdtConfig->gdt_advertiser_add;
        $ad_qualifications = $this->gdtConfig->gdt_material_qualifications;
        $str_auth = $str_token;
        $ig_old_db_arr = $this->select("again_info","select * from ig_industry_gdt");
        $ig_old_arr = array();
        foreach ($ig_old_db_arr as $item) {
            $ig_old_arr[$item['xId']] = $item;
        }
        $sql = "SELECT adQualifications,beheAccountId
        FROM `again_main`.`account`
        WHERE beheAccountId
        IN (
            SELECT beheAccountId
            FROM `again_main`.`adx_account`
            WHERE adxId = 49
            AND adxStatus = 1
            AND uploadStatus = 1
            and gdtAdStatus = 0
        )
        AND STATUS = 1 AND adQualifications!=''";
        $arr_client_list = $this->select('again_main',$sql);
        //0 组织机构代码 1组织机构资质2 营业执照3法人 4ICP 5完税
        $qualification_file = array(0 => 3, 1 => 4);
        foreach ($arr_client_list as $item) {
            $accountId = $item['beheAccountId'];
            $request[0]["advertiser_id"] = $accountId;
            $gdtQualificationsAry=  json_decode($item['adQualifications'],true);
            foreach ($gdtQualificationsAry as $qualificationk => $qualificationItem) {
                $request[0]["qualifications"][$qualificationk]['name'] = $qualificationItem['name'];
                $request[0]["qualifications"][$qualificationk]['file_url'] = $this->config['domain'] . $qualificationItem['file'];
            }
            $request_arr = array();
            $request_arr['data'] = $request;
            $request_json = json_encode($request_arr);

            $str_return = $this->postCurl($ad_qualifications, $request_json, $str_auth);
            if (!empty($str_return)) {
                $arr_return = $str_return['response'];
                if ($arr_return['data'][0]['code'] == '0') {
                    $update_data = array();
                    $update_data['gdtAdStatus'] = 1;
                    $this->saveAdvertiser("beheAccountId = {$accountId} and adxId = 49",$update_data);
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new gdtAdvertiserQualifications();
$obj->run();
?>