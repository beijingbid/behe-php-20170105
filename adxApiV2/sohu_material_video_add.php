<?php

try {
// 调入配置参数
    $app_name = "sohu_material_video_add";
    define('APP_PATH', realpath(dirname(__FILE__)));
    require_once APP_PATH . '/library/config.php';
    require_once APP_PATH . '/library/sohu.php';
    G('begin');
    pid_init();
    $key = $config->sohu_dsp_id;
    $secret = $config->sohu_token;
    $vw = $config->sohu_vw;
    $ck = $config->sohu_ck;
    $apiAddUrl = $config->sohu_material_add;
    $apiDeleteUrl = $config->sohu_material_delete;
    $auth = new Auth($key, $secret);
    $sizeArr = $config->sohu_size;

    $account_db_sql = "select accountId,customerKey from center_account where exchangeStatus=1 and exchangeId=17";
    $account_result = $db->getDb("select", "again_center")->fetchAll($account_db_sql);
    foreach ($account_result as $account_item) {
        $adAccountId = $account_item["accountId"];
        $customerKey = $account_item['customerKey'];
        $sql = "SELECT om.adAccountId,m.name,m.fileStatus,om.materialId,om.materialType, om.id, om.orderId,om.goUrl,om.turl, om.campaignId, m.name, m.fileUrl, m.width, m.height, m.type
    FROM `order_material` AS om
    LEFT JOIN video_material AS m ON om.materialId = m.id
    WHERE om.materialType =2
    AND om.id
    IN (
    SELECT adId
    FROM again_center.`center_material`
    WHERE exchangeStatus
    IN ( 3, 99 )
    AND exchangeId =17
    AND uploadStatus=0
    )
    AND om.status =2 AND m.status =1 AND om.adAccountId={$adAccountId}";
//查询需要上传的素材Id
        $uploadMaterialList = array();
        $orderIdAry = array();
        $campaignIdAry = array();
        $adAccountIdAry = array();

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

                    $systemFilePath = $config->video_file_system_path . $item['fileUrl'];
                    $ext = $config->file_type[$item['type']];
                    $newFilePath = $config->video_file_system_path . '/' . date("Y/m/d") . "/";
                    $newFileName = md5(time() . rand(0, 1000000)) . "." . $ext;

                    $result = create_folder($newFilePath);
                    if ($result) {
                        if (copy($systemFilePath, $newFilePath . $newFileName)) {
                            try {
                                $update_data = array();
                                $update_data['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                                $update_data['fileStatus'] = 0;
                                $db->getDb("update", "again_main")->update("video_material", $update_data, "id='{$item['materialId']}'");
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
                $submit_to = "2";
                $delivery_type = 1;
                $orderId = $item['orderId'];
                $sohu_campaign_id = 0;
                if ($orderDealInfo[$orderId]) {
                    $delivery_type = 2;
                    $sohu_campaign_id = $orderDealInfo[$orderId];
                }
                $goUrl = trim($item['goUrl']);
                $adAccountId = $item['adAccountId'];
                $monitorUrl = $ck . urlencode($goUrl);
                $file_url = $config->video_file_path . $item["fileUrl"];
                $sizeId = $item['width'] . '×' . $item['height'];
                $size = round(filesize($config->video_file_system_path . $item["fileUrl"]) / 1024, 2);
                $materialId = $item['id'];
                //if ($size <= $sizeArr[$sizeId]) {

                $imp = array($vw);
                if ($item["turl"] != '' && $item["turl"] != 'http://' && $item["goUrl"] != $item["turl"]) {
                    $imp[] = $item["turl"];
                }
                $post = array(
                    'customer_key' => $customerKey,
                    'advertising_type' => $advertising_type,
                    'submit_to' => $submit_to,
                    'delivery_type' => $delivery_type,
                    'material_name' => trim($item['name']),
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
                    if (is_array($return_arr)&&!empty($str_return)) {
                        if ($return_arr['status'] == true) {
                            $update_data = array();
                            $update_data['exchangeStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['orderId'] = $orderId;
                            $update_data['accountId'] = $adAccountId;
                            $update_data['fileType'] = 2;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
                        } elseif ($return_arr['status'] == false && $return_arr['code'] == 96000201) {
                            $update_data = array();
                            $update_data['exchangeStatus'] = 0;
                            $update_data['reason'] = "";
                            $update_data['orderId'] = $orderId;
                            $update_data['accountId'] = $adAccountId;
                            $update_data['fileType'] = 2;
                            $update_data['uploadStatus'] = 1;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
                        }else{
                           $update_data = array();
                            $update_data['exchangeStatus'] =2;
                            $update_data['reason'] = $return_arr['message'] ;
                            $update_data['orderId'] = $orderId;
                            $update_data['accountId'] = $adAccountId;
                            $update_data['fileType'] = 2;
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}"); 
                        }
                    } else {
                        info_log($str_return);
                        info_log(json_encode($post));
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    info_log($e->getMessage());
    info_log($e->getTraceAsString());
}
G('end');
info_log(G('begin', 'end', 6) . "s"); // 统计区间运行时间 精确到小数后6位
info_log(G('begin', 'end', 'm') . "kb"); // 统计区间内存使用情况
pid_destory();
?>

