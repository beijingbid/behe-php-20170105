<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/sohuConfig.php';
require_once APP_PATH . '/library/sohu.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\sohuConfig as sohuConfig;
use \library\base as base;

class sohuMaterialUpload extends base{

    public $appName = 'sohuMaterialUpload';

    public function run(){
        $this->sohuConfig = new sohuConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $key = $this->sohuConfig->sohu_dsp_id;
        $secret = $this->sohuConfig->sohu_token;
        $vw = $this->sohuConfig->sohu_vw;
        $ck = $this->sohuConfig->sohu_ck;
        $apiAddUrl = $this->sohuConfig->sohu_material_add;
        $apiDeleteUrl = $this->sohuConfig->sohu_material_delete;
        $auth = new Auth($key, $secret);
        $sizeArr = $this->sohuConfig->sohu_size;
        try {
            $account_db_sql = "select a.beheAccountId,a.customerKey,GROUP_CONCAT(b.accountId) as accountId from adx_account as a left join account as b on a.beheAccountId = b.beheAccountId where a.adxStatus = 1 and a.adxId = 17 ";
            $account_result = $this->select('again_main',$account_db_sql);
            foreach ($account_result as $account_item) {
                $adAccountId = $account_item["accountId"];
                $customerKey = $account_item['customerKey'];
                $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.materialType, om.id, om.orderId,om.goUrl,om.turl, om.campaignId, m.name, m.fileUrl, m.width, m.height, m.type
                FROM `advert` AS om
                LEFT JOIN banner_material AS m ON om.materialId = m.id
                WHERE om.materialType =1
                AND om.id
                IN (
                SELECT adId
                FROM again_main.`advert_adx_status`
                WHERE adxStatus
                IN ( 3, 99 )
                AND adxId =17
                AND uploadStatus=0
                )
                AND om.status =2 AND m.status =1 AND om.adAccountId={$adAccountId}";
                //查询需要上传的素材Id
                $uploadMaterialList = array();
                $orderIdAry = array();
                $campaignIdAry = array();
                $adAccountIdAry = array();

                $arr_db_info = $this->select('again_main',$sql);
                if ($arr_db_info) {
                    foreach ($arr_db_info as $item_db) {
                        $orderId = $item_db["orderId"];
                        $orderIdAry[$orderId] = $orderId;
                        $adAccountId = $item_db["adAccountId"];
                        $adAccountIdAry[$adAccountId] = $adAccountId;
                        $campaignId = $item_db["campaignId"];
                        $campaignIdAry[$campaignId] = $campaignId;
                        $uploadMaterialList[] = $item_db;
                    }
                }
                $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.materialType, om.id, om.orderId,om.goUrl,om.turl, om.campaignId, m.name, m.fileUrl, m.width, m.height, m.type
                FROM `advert` AS om
                LEFT JOIN banner_material AS m ON om.materialId = m.id
                WHERE om.materialType =4
                AND om.id
                IN (
                SELECT adId
                FROM again_main.`advert_adx_status`
                WHERE adxStatus
                IN ( 3, 99 )
                AND adxId =17
                AND uploadStatus=0
                )
                AND om.status =2 AND m.status =1 AND om.adAccountId={$adAccountId}";
                //查询需要上传的素材Id
                $arr_db_info = $db->getDb($sql, "again_main")->fetchAll($sql);
                if ($arr_db_info) {
                    foreach ($arr_db_info as $item_db) {
                        $orderId = $item_db["orderId"];
                        $orderIdAry[$orderId] = $orderId;
                        $adAccountId = $item_db["adAccountId"];
                        $adAccountIdAry[$adAccountId] = $adAccountId;
                        $campaignId = $item_db["campaignId"];
                        $campaignIdAry[$campaignId] = $campaignId;
                        $uploadMaterialList[] = $item_db;
                    }
                }
                if ($uploadMaterialList) {
                    $orderDealInfo = array();
                    $orderIdStr = implode(',', $orderIdAry);
                    $sql = "select orderId ,dealInfo from order_direct_base where orderId in ($orderIdStr)";
                    $dealInfoDb = $db->getDb("select", "again_main")->fetchAll($sql);
                    foreach ($dealInfoDb as $item) {
                        $orderDealInfo[$item['orderId']] = $item['dealInfo'];
                    }

                    $accountIdStr = implode(',', $adAccountIdAry);
                    $sql = "select accountId ,exchangeStatus from center_account where exchangeId=17 and accountId in ($accountIdStr)";
                    $accountDb = $db->getDb("select", "again_center")->fetchAll($sql);
                    $accountList = array();
                    foreach ($accountDb as $item) {
                        $accountList[$item['accountId']] = $item;
                    }
                    $campaignIdStr = implode(',', $campaignIdAry);
                    $sql = "select id,companySubCateId from campaign where id in ($campaignIdStr)";
                    $campaignDb = $db->getDb("select", "again_main")->fetchAll($sql);
                    $campaignList = array();
                    $sql = "select xId,parent,sId as id from ig_industry_sohu";
                    $categoryDb = $db->getDb("select", "again_ip_database_v1")->fetchAll($sql);
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
                        if ($item['fileStatus'] == 1) {

                            $systemFilePath = $config->banner_file_system_path . $item['fileUrl'];
                            $ext = $config->file_type[$item['type']];
                            $newFilePath = $config->banner_file_system_path . '/' . date("Y/m/d") . "/";
                            $newFileName = md5(time() . rand(0, 1000000)) . "." . $ext;
                            $result = create_folder($newFilePath);
                            if ($result) {
                                if (copy($systemFilePath, $newFilePath . $newFileName)) {
                                    try {
                                        $update_data = array();
                                        $update_data['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                                        $update_data['fileStatus'] = 0;
                                        if ($item['materialType'] == 1) {
                                            $db->getDb("update", "again_main")->update("static_material", $update_data, "id='{$item['materialId']}'");
                                        } else {
                                            $db->getDb("update", "again_main")->update("static_dmaterial", $update_data, "id='{$item['materialId']}'");
                                        }
                                        $center_material_arr = array();
                                        $center_material_arr['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                                        $db->getDb("update", "again_center")->update("center_material", $center_material_arr, "adId='{$item['id']}' and exchangeId=17");
                                    } catch (Exception $exc) {
                                        info_log(json_encode($update_data));
                                        info_log($exc->getMessage());
                                    }
                                    continue;
                                } else {
                                    info_log("copy $systemFilePath to " . $newFilePath . $newFileName . "failed");
                                    continue;
                                }
                            } else {
                                info_log("create file failed $newFilePath");
                                continue;
                            }

                            continue;
                        }
                        $creativeTradeId = $campaignList[$item['campaignId']]['creativeTradeId'];
                        if ($creativeTradeId == 0) {
                            continue;
                        }
                        $advertising_type = $creativeTradeId;
                        $submit_to = 1;
                        $delivery_type = 1;
                        $orderId = $item['orderId'];
                        $sohu_campaign_id = 0;
                        if ($orderDealInfo[$orderId]) {
                            $delivery_type = 2;
                            $sohu_campaign_id = $orderDealInfo[$orderId];
                        }
                        info_log(json_encode($orderDealInfo));
                        info_log($orderId.":".$delivery_type);
                        $adAccountId = $item['adAccountId'];
                        $goUrl = trim($item['goUrl']);
                        $monitorUrl = $ck . urlencode($goUrl);
                        $file_url = $config->file_path . $item["fileUrl"];
                        $sizeId = $item['width'] . '×' . $item['height'];
                        $size = round(filesize($config->banner_file_system_path . $item["fileUrl"]) / 1024, 2);
                        $materialId = $item['id'];
                        $imp = array($vw);
                        if ($item["turl"] != '' && $item["turl"] != 'http://' && $item["goUrl"] != $item["turl"]) {
                            $imp[] = $item["turl"];
                        }
                        if ($size <= $sizeArr[$sizeId]) {
                            $post = array(
                                'customer_key' => $customerKey,
                                'material_name' => trim($item['name']),
                                'advertising_type' => $advertising_type,
                                'submit_to' => $submit_to,
                                'delivery_type' => $delivery_type,
                                'file_source' => $file_url,
                                'imp' => json_encode($imp),
                                'click_monitor' => $monitorUrl,
                                'gotourl' => $goUrl,
                                'expire' => 60 * 60 * 24 * 180
                            );
                            if ($sohu_campaign_id) {
                                $post['campaign_id'] = $sohu_campaign_id;
                            }
                            $auth = new Auth($key, $secret);

                            $queryString = $auth->setMethod('post')->setUrl($apiAddUrl)->setParams($post)->queryString();

                            $str_return = $auth->curl($apiAddUrl, '', $queryString);
                            if (!empty($str_return)) {
                                 info_log($str_return);
                                $return_arr = json_decode($str_return, true);
                                if (is_array($return_arr)) {
                                    if ($return_arr['status'] ===true) {
                                        $update_data = array();
                                        $update_data['exchangeStatus'] = 0;
                                        $update_data['reason'] = "";
                                        $update_data['uploadStatus'] = 1;
                                        $update_data['orderId'] = $orderId;
                                        $update_data['accountId'] = $adAccountId;
                                        $update_data['mtime'] = date('Y-m-d H:i:s');
                                        $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
                                    } elseif ($return_arr['status'] === false && $return_arr['message'] == '该素材已上传，请不要重复提交') {
                                        $update_data = array();
                                        $update_data['exchangeStatus'] = 0;
                                        $update_data['reason'] = "";
                                        $update_data['uploadStatus'] = 1;
                                        $update_data['orderId'] = $orderId;
                                        $update_data['accountId'] = $adAccountId;
                                        $update_data['mtime'] = date('Y-m-d H:i:s');
                                        $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
                                    }
                                }
                            }
                        } else {
                            $update_data = array();
                            if($sizeArr[$sizeId]){
                                $update_data['reason'] = $sizeId . "大小为" . $size . "kb不符合sohu要求{$sizeArr[$sizeId]}kb以下";
                            }else{
                                $update_data['reason'] = "sohu没有{$sizeId}尺寸";
                            }
                            
                            $update_data['exchangeStatus'] = 2;
                            
                            $update_data['uploadStatus'] = 0;
                            $update_data['orderId'] = $orderId;
                            $update_data['accountId'] = $adAccountId;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
                        }
                    }
                }
            }
        } catch (Exception $exc) {
            info_log($exc->getMessage());
            info_log($exc->getTraceAsString());
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new sohuMaterialUpload();
$obj->run();
?>

