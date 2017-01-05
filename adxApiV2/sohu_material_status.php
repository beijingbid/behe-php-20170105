<?php

// 调入配置参数
$app_name = "sohu_material_status";
define('APP_PATH', realpath(dirname(__FILE__)));
require_once APP_PATH . '/library/config.php';
require_once APP_PATH . '/library/sohu.php';
G('begin');
pid_init();
$key = $config->sohu_dsp_id;
$secret = $config->sohu_token;
$auth = new Auth($key, $secret);
$apiurllist = $config->sohu_material_status;
$str_sql = "select id,adId,fileUrl,orderId,fileType from center_material where exchangeId=17 and exchangeStatus=0 order by id desc";
$materialList = $db->getDb($str_sql, "again_center")->fetchAll($str_sql);
foreach ($materialList as $materialItem) {
    if ($materialItem['fileType'] == 1) {
        $get = array(
            "file_source" => $config->file_path . $materialItem['fileUrl']
        );
    } else {
        $get = array(
            "file_source" => $config->video_file_path . $materialItem['fileUrl']
        );
    }
    $adId = $materialItem['adId'];
    $id = $materialItem['id'];
    $orderId = $materialItem['orderId'];
    $auth = new Auth($key, $secret);

    $string = $auth->setMethod('get')->setUrl($apiurllist)->setParams($get)->url();
    $str_return = $auth->curl($string);
    info_log($str_return);
    $content = json_decode($str_return, true);
    if (is_array($content)) {

        if ($content['content']['count'] > 0) {
            foreach ($content['content']['items'] as $item) {
                info_log($adId . ":" . $item['file_source']);
                info_log($adId . ":" . $item["status"]);
                if ($item["status"] == 3) {
                    continue;
                }
                $update_data = array();
                $update_data['exchangeStatus'] = $item["status"];
                $update_data['reason'] = "";
                $update_data['mtime'] = date('Y-m-d H:i:s');
                $db->getDb("insert", "again_center")->update('center_material', $update_data, "id={$id}");
                if ($item['status'] == 1) {
                    order_queue($orderId);
                }
            }
        }
    } else {
        info_log($str_return);
    }
}
G('end');
info_log(G('begin', 'end', 6) . "s"); // 统计区间运行时间 精确到小数后6位
info_log(G('begin', 'end', 'm') . "kb"); // 统计区间内存使用情况
pid_destory();
?>

