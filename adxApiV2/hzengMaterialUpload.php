<?php
namespace adxApiV2;

error_reporting(1);
define('APP_PATH', realpath(dirname(__file__).'/../'));

require_once APP_PATH . '/config/hzengConfig.php';
require_once APP_PATH . '/library/base.php';
require_once APP_PATH . '/library/hzengClient/client/HzSdk.php';
require_once APP_PATH . '/library/hzengClient/client/request/CreativeAddRequest.php';

use adxApiV2\config\hzengConfig as hzengConfig;
use \library\base as base;

class hzengMaterialUpload extends base{

    public $appName = 'hzengMaterialUpload';

    public function run(){
        $this->hzengConfig = new hzengConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();
        $allowAccountIdAry = $this->getAllowAdvertiser(25);
        $arr_db_info = $this->getBannerMaterial(25,'3,99','0,1');
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
            $accountIdStr = implode(',', $adAccountIdAry);
            $sql = "select beheAccountId ,adxStatus from adx_account where adxId = 25 and adxStatus = 1 and beheAccountId in ($accountIdStr)";
            $accountDb = $this->select('again_main',$sql);
            $accountList = array();
            foreach ($accountDb as $item) {
                $accountList[$item['beheAccountId']] = $item;
            }
            $campaignIdStr = implode(',', $campaignIdAry);
            $sql = "select id,industrySubCateId as companySubCateId from campaign where id in ($campaignIdStr)";
            $campaignDb = $this->select('again_main',$sql);
            $campaignList = array();
            $sql = "select xId,parent,sId as id from ig_industry_hz";
            $categoryDb = $this->select('again_info',$sql);
            $categoryList = array();
            foreach ($categoryDb as $item) {
                $categoryList[$item['xId']] = $item;
            }
            foreach ($campaignDb as $item) {
                $campaignList[$item['id']] = $item;
                if ($categoryList[$item['companySubCateId']]) {
                    $campaignList[$item['id']]['creativeTradeId'] = $categoryList[$item['companySubCateId']]['id'];
                } else {
                    $campaignList[$item['id']]['creativeTradeId'] = 0;
                }
            }
            foreach ($uploadMaterialList as $item) {

                $materialId = $item['id'];
                $creativeUrl = $this->config['file_path'] . $item['fileUrl'];
                $advertiserId = $item['beheAccountId'];
                $orderId=$item['orderId'];
                $landingPage = $item['goUrl'];
                $targetUrl = $this->hzengConfig->hz_ck . urlencode($item['goUrl']);
                $monitorUrls = $this->hzengConfig->hz_vw;
                $monitorUrlsAry=array($monitorUrls);
                if($item['turl']!=''&&$item['turl']!='http://'){
                    $monitorUrlsAry[]=$item['turl'];
                }
                $width = $item['width'];
                $height = $item['height'];
                $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];

                if ($creativeTradeId == 0) {
                    continue;
                }
                $str_size_temp = $width . "*" . $height;
                $file_ext_name = $this->config['file_type'][$item['type']];
                $type = $this->hzengConfig->hz_file_ext_type[$file_ext_name];
                if (!$this->hzengConfig->hz_material_file_type[$file_ext_name]) {
                    $update_data = array();
                    $update_data['adxStatus'] = 2;
                    $update_data['reason'] = "不符合的创意类型";
                    $update_data['uploadStatus'] = 0;
                    $update_data['fileType'] = 1;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 25 and advertId = {$materialId}",$update_data);
                    continue;
                }
                if ($accountList[$advertiserId]['adxStatus'] != 1) {
                    $update_data = array();
                    $update_data['adxStatus'] = 99;
                    if ($accountList[$advertiserId]['adxStatus'] == 0) {
                        $update_data['reason'] = "广告主审核中";
                    } elseif ($accountList[$advertiserId]['adxStatus'] == 2) {
                        $update_data['reason'] = "广告主审核被拒";
                    }
                    $update_data['uploadStatus'] = 0;
                    $update_data['fileType'] = 1;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveMaterial("adxId = 25 and advertId = {$materialId}",$update_data);
                    continue;
                }
                $entity = array(
                    "targetUrl" => $targetUrl,
                    "landingPage" => $landingPage,
                    "monitorUrls" => $monitorUrlsAry,
                    "creativeId" => (int) $materialId,
                    "creativeTradeId" => (int) $creativeTradeId,
                    "advertiserId" => (int) $advertiserId,
                    "creativeUrl" => $creativeUrl,
                    "type" => (int) $type,
                    "height" => (int) $height,
                    "width" => (int) $width,
                );
                $client = new AdxClient();
                $client->dspId = $this->hzengConfig->hz_dsp_id;
                $client->token = $this->hzengConfig->hz_token;
                if($item['uploadStatus'] == 0):
                    $req = new CreativeAddRequest();
                else:
                    $req = new CreativeUpdateRequest();
                endif;
                $req->addEntity($entity);
                $arr_return = $client->execute($req);
                if (is_array($arr_return)) {
                    if ($arr_return['status'] === 0||$arr_return['error'][0]=="duplicate insert creative :{$materialId}") {
                        $update_data = array();
                        $update_data['adxStatus'] = 0;
                        $update_data['reason'] = "";
                        $update_data['uploadStatus'] = 1;
                        $update_data['fileType'] = 1;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 25 and advertId = {$materialId}",$update_data);
                    } elseif ($arr_return['status'] === 2 && $arr_return['error'] != 'too many api requests') {
                        info_log(json_encode($arr_return));
                        $update_data = array();
                        $update_data['adxStatus'] = 2;
                        $update_data['reason'] = $arr_return['error'][0];
                        $update_data['uploadStatus'] = 0;
                        $update_data['fileType'] = 1;
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveMaterial("adxId = 25 and advertId = {$materialId}",$update_data);
                    }
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new hzengMaterialUpload();
$obj->run();
?>
    
