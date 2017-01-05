<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/tanxConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\tanxConfig as tanxConfig;
use \library\base as base;

class tanxMaterialUpload extends base{

    public $appName = 'tanxMaterialUpload';

    public function run(){
        $this->tanxConfig = new tanxConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAry = $this->getAllowAdvertiser(3);
        $arr_db_info = $this->getBannerMaterial(3,'3,99','0,1');
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
            $imgCode = "<divstyle='position:relative;width:%%ADWIDTH%%px;height:%%ADHEIGHT%%px;padding:0px;margin:0px;'>"
                    . "<a href='%%CLICK_URL%%' target='_blank' style='position:absolute;top:0;left:0;bottom:0;right:0;display:block;width:100%;height:expression(this.parentNode.scrollHeight);filter:alpha(opacity=0);opacity:0;background:#FFF;'></a>"
                    . "<img src='%%CREATIVE_URL%%' width='%%ADWIDTH%%' height='%%ADHEIGHT%%' />"
                    . "</div>"
                    . "<script src='http://v.behe.com/js/0.js'>"
                    . "</script>"
                    . "<iframe width='0' height='0' frameborder='0' src='http://v.behe.com/js/0.html'>"
                    . "</iframe>";
            $swfCode = "<div style='position:relative;width:%%ADWIDTH%%px;height:%%ADHEIGHT%%px;padding:0px;margin:0px;'>"
                    . "<a href='%%CLICK_URL%%' target='_blank' style='position:absolute;top:0;left:0;bottom:0;right:0;display:block;width:100%;height:expression(this.parentNode.scrollHeight);filter:alpha(opacity=0);opacity:0;background:#FFF;'></a>"
                    . "<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0' width='%%ADWIDTH%%' height='%%ADHEIGHT%%' align='middle'>"
                    . "<param name='allowScriptAccess' value='always'/>"
                    . "<param name='quality' value='high'/>"
                    . "<param name='wmode' value='opaque'/>"
                    . "<param name='movie' value='%%CREATIVE_URL%%'/>"
                    . "<embed wmode='opaque' quality='high' allowscriptaccess='always' type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer' align='middle' src='%%CREATIVE_URL%%' width='%%ADWIDTH%%' height='%%ADHEIGHT%%' />"
                    . "</object>"
                    . "</div>"
                    . "<script src='http://v.behe.com/js/0.js'></script>"
                    . "<iframe width='0' height='0' frameborder='0' src='http://v.behe.com/js/0.html'></iframe>";
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select exAccountId,beheAccountId,adxStatus from adx_account where adxId=3 and exAccountId>0 and beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $accountList[$item['beheAccountId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCateId as companySubCateId,sensitiveCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();
            $sql = "select xId,parent,sId as id from ig_industry_tanx";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }
            $sql = "select xId,sId as id from ig_sensitive_tanx";
            $categoryDb = $this->select('again_info',$sql);
            $categorySensitiveList = array();
            foreach ($categoryDb as $item) {
                $categorySensitiveList[$item['xId']] = $item;
            }
            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categorySensitiveList[$item['sensitiveCateId']]) {
                    $campaignList[$item['id']]['sensitiveCateId'] = $categorySensitiveList[$item['sensitiveCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['sensitiveCateId'] = '';
                }
                if ($categoryList[$item['companySubCateId']]) {
                    $campaignList[$item['id']]['creativeTradeId'] = $categoryList[$item['companySubCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['creativeTradeId'] = '';
                }
            }
            foreach ($uploadMaterialList as $k => $item) {
                $adboard_type = $campaignList[$item['campaignId']]['creativeTradeId'];
                if (!$adboard_type) {

                    continue;
                }

                if (!$accountList[$item['adAccountId']]) {
                    continue;
                }
                $advertiser_ids = $accountList[$item['beheAccountId']]['exAccountId'];
                $type = $item['type'];
                if ($type == 5) {
                    $adboard_data = $swfCode;
                } else {
                    $adboard_data = $imgCode;
                }
                $sensitive_type = $campaignList[$item['campaignId']]['sensitiveCateId'];
                $fileUrl = $this->config['file_path'] . $item['fileUrl'];
                $destination_url = $item['goUrl'];
                $width = $item['width'];
                $height = $item['height'];
                $adboard_size = $width . "x" . $height;
                $creative_package_format = 1;
                $creative_id = $item['id'];
                $orderId = $item['orderId'];
                $adAccountId = $item['adAccountId'];
                $adboard_data = str_replace("%%CLICK_URL%%", $destination_url, $adboard_data);
                $adboard_data = str_replace("%%ADWIDTH%%", $width, $adboard_data);
                $adboard_data = str_replace("%%ADHEIGHT%%", $height, $adboard_data);
                $adboard_data = str_replace("%%CREATIVE_URL%%", $fileUrl, $adboard_data);
                $arr_send_json = array();
                $creative_array = array();
                $creative_array['creative_id'] = $creative_id;
                $creative_array['creative_package_format'] = $creative_package_format;
                $creative_array['adboard_size'] = $adboard_size;
                $creative_array['adboard_data'] = $adboard_data;
                $creative_array['destination_url'] = $destination_url;
                $creative_array['sensitive_type'] = $sensitive_type;
                $creative_array['adboard_type'] = $adboard_type;
                $creative_array['advertiser_ids'] = $advertiser_ids;
                $arr_send_json['creative'] = json_encode($creative_array);
                $arr_send_json['member_id'] = $this->tanxConfig->tanx_memberId;
                $arr_send_json['method'] = $this->tanxConfig->tanx_material_add;
                $arr_send_json['format'] = 'json';
                $arr_send_json["v"] = "2.0";
                $arr_send_json['sign_method'] = 'md5';
                $arr_send_json['app_key'] = $this->tanxConfig->tanx_appkey;
                $time = time();
                $token = md5($this->tanxConfig->tanx_userKey . $time);
                $arr_send_json['timestamp'] = date('Y-m-d H:i:s');
                $arr_send_json['token'] = $token;
                $arr_send_json['sign_time'] = $time;
                
                ksort($arr_send_json);
                $sign = $this->getSign($arr_send_json);
                $arr_send_json['sign'] = $sign;
                $str_return = $this->postCurl($this->tanxConfig->tanx_api_url, $arr_send_json);
                if (!empty($str_return)) {
                    $return_arr = $str_return['response'];
                    if ($return_arr['"tanx_audit_creative_add_response']['is_ok'] === true) {
                        $update_data = array();
                        $update_data['reason'] = '';
                        $update_data['adxStatus'] = 0;
                        $update_data['uploadStatus'] = 1;
                        $update_data['mtime'] = date("Y-m-d H:i:s");
                        $this->saveMaterial("advertId = {$item['id']} and adxId = 3",$update_data);
                    } elseif ($return_arr['error_response']['code'] === 15 && $return_arr['error_response']['sub_msg'] == '你要添加的创意已经存在') {
                        $update_data = array();
                        $update_data['reason'] = '';
                        $update_data['uploadStatus'] = 1;
                        $update_data['mtime'] = date("Y-m-d H:i:s");
                        $this->saveMaterial("advertId = {$item['id']} and adxId = 3",$update_data);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new tanxMaterialUpload();
$obj->run();
?>
