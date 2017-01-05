<?php

// 调入配置参数
$app_name = "sohu_material_banner_delete";
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
$sizeArr = $config->sohu_size;
$auth = new Auth($key, $secret);
$account_db_sql = "select accountId,customerKey from center_account where exchangeStatus=1 and exchangeId=17";
$account_result = $db->getDb("select", "again_center")->fetchAll($account_db_sql);
foreach ($account_result as $account_item) {
    $adAccountId = $account_item["accountId"];
    $customerKey = $account_item['customerKey'];
    $sql = "SELECT om.adAccountId, om.id, om.orderId,om.goUrl,om.turl, om.campaignId, m.name, m.fileUrl, m.width, m.height, m.type
    FROM `order_material` AS om
    LEFT JOIN static_material AS m ON om.materialId = m.id
    WHERE om.materialType =1
    AND om.id
    IN (
    SELECT adId
    FROM again_center.`center_material`
    WHERE exchangeStatus
    IN ( 3, 99 )
    AND exchangeId =17
    AND uploadStatus=1
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
            $campaignId = $item_db["capaignId"];
            $campaignIdAry[$campaignId] = $campaignId;
            $uploadMaterialList[] = $item_db;
        }
    }
    $sql = "SELECT om.adAccountId, om.id, om.orderId,om.goUrl,om.turl, om.campaignId, m.name, m.fileUrl, m.width, m.height, m.type
    FROM `order_material` AS om
    LEFT JOIN static_material AS m ON om.materialId = m.id
    WHERE om.materialType =4
    AND om.id
    IN (
    SELECT adId
    FROM again_center.`center_material`
    WHERE exchangeStatus
    IN ( 3, 99 )
    AND exchangeId =17
    AND uploadStatus=1
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
            $campaignId = $item_db["capaignId"];
            $campaignIdAry[$campaignId] = $campaignId;
            $uploadMaterialList[] = $item_db;
        }
    }
    if ($uploadMaterialList) {
        foreach ($uploadMaterialList as $material_item) {
            $materialId = $material_item['id'];
            $goUrl = trim($material_item['goUrl']);
            $monitorUrl = $ck . urlencode($goUrl);
            $file_url = $config->file_path . $material_item["fileUrl"];
            $sizeId = $material_item['width'] . '×' . $material_item['height'];
            $size = round(filesize($config->banner_file_system_path . $material_item["fileUrl"]) / 1024, 2);
            if ($size <= $sizeArr[$sizeId]) {

                $post = array(
                    'customer_key' => trim($account_item['customer_key']),
                    'file_source' => trim($file_url),
                );
                $auth = new Auth($key, $secret);
                $queryString = $auth->setMethod('post')->setUrl($apiDeleteUrl)->setParams($post)->queryString();

                $str_return = $auth->curl($apiDeleteUrl, '', $queryString);
                if (!empty($str_return)) {
                    $return_arr = json_decode($str_return, true);
                    if (is_array($return_arr)) {
                        if ($return_arr['status'] != '' || $return_arr['status'] != false) {
                            $update_data = array();
                            $update_data['exchangeStatus'] = 99;
                            $update_data['reason'] = "";
                            $update_data['uploadStatus'] = 0;
                            $update_data['mtime'] = date('Y-m-d H:i:s');
                            $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
                        }
                    } else {
                        info_log($str_return);
                    }
                }
            } else {
                $update_data = array();
                $update_data['exchangeStatus'] = 2;
                $update_data['reason'] = $sizeId . "大小为" . $size . "不符合sohu要求";
                $update_data['uploadStatus'] = 0;
                $update_data['mtime'] = date('Y-m-d H:i:s');
                $db->getDb("insert", "again_center")->update('center_material', $update_data, "exchangeId=17 and adId={$materialId}");
            }
        }
    }
}
G('end');
info_log(G('begin', 'end', 6) . "s"); // 统计区间运行时间 精确到小数后6位
info_log(G('begin', 'end', 'm') . "kb"); // 统计区间内存使用情况
pid_destory();
?>

