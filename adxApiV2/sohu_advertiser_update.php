<?php

// 调入配置参数
$app_name = "sohu_advertiser_update";
define('APP_PATH', realpath(dirname(__FILE__)));
require_once APP_PATH . '/library/config.php';
require_once APP_PATH . '/library/sohu.php';
G('begin');
pid_init();
$key = $config->sohu_dsp_id;
$secret = $config->sohu_token;
$apiurl = $config->sohu_advertiser_update;
$auth = new Auth($key, $secret);
$arr_account = array();
$arr_client_list = array();
$str_sql = "SELECT customerKey,representInfo,companySubCateId,gameInfo,otherIntelligence,intelligence,companyName,a.accountId,companyUrl FROM `again_main`.`account` as a left join `again_center`.`center_account` as c on a.`accountId`=c.`accountId` WHERE c.exchangeId =17 AND c.exchangeStatus =99 AND c.uploadStatus =1 and a.STATUS =1 ";
//0 组织机构代码 1组织机构资质2 营业执照3法人 4ICP 5完税
$arr_account_info = $db->getDb($str_sql, "again_center")->fetchAll($str_sql);
foreach ($arr_account_info as $account_item) {
    if (!$account_item['customerKey']) {
        continue;
    }
    $intelligence_arr = explode('|', $account_item['intelligence']);
    if ($intelligence_arr[0] != '' && $intelligence_arr[1]) {
        $accountId = $account_item['accountId'];
        if ($intelligence_arr[1]) {

             $post = array(
                'oganization_code' => trim($intelligence_arr[0]),
                'customer_name' => trim($account_item['companyName']),
                 'customer_key' => $account_item['customerKey']
            );
            $auth = new Auth($key, $secret);
            $qstring = $auth->setMethod('post')->setUrl($apiurl)->setParams($post)->queryString(); //post

            $file_post = array(
                'oganization_license' => $config->file_logo_system_path . $intelligence_arr[1]
            );
            $q_arr = explode('&', $qstring);
            $file_post['oganization_license'] = new CURLFile($file_post['oganization_license']);
            foreach ($q_arr as $item) {
                $temp = explode('=', $item);
              
                $param = rawurldecode($temp[1]);
                $file_post[$temp[0]] = $param;
            }
            

            $str_return = $auth->postCurl($apiurl, $file_post);
            info_log(json_encode($file_post));
            if (!empty($str_return)) {
                info_log($accountId . "|||" . $str_return);
                $return_arr = json_decode($str_return, true);

                if ($return_arr['status'] == true) {
                    try {
                        $db_data = array();
                        $db_data['exchangeStatus'] = 0;
                        $db_data['uploadName'] = $account_item['companyName'];
                        $db_data['mtime'] = date('Y-m-d H:i:s');
                        $db->getDb("insert", "again_center")->update("center_account", $db_data, "accountId={$accountId} and exchangeId=17");
                        $db_data = array();
                        $db_data['accountId'] = $accountId;
                        $db_data['qualification'] = $intelligence_arr[0];
                        $db_data['exchangeId'] = 17;
                        $db_data['name'] = "组织机构号码";
                        $db_data['mtime'] = date('Y-m-d H:i:s');
                        $db->getDb("insert", "again_center")->update("center_account_qualification", $db_data, "qid=1"); //
                        $db_data = array();
                        $db_data['accountId'] = $accountId;
                        $db_data['qualification'] = $intelligence_arr[1];
                        $db_data['exchangeId'] = 17;
                        $db_data['name'] = "组织机构证";
                        $db_data['mtime'] = date('Y-m-d H:i:s');
                        $db->getDb("insert", "again_center")->update("center_account_qualification", $db_data, "qid=2"); //
                    } catch (Exception $exc) {
                        sendMail($exc->getMessage());
                        info_error_log($exc->getMessage());
                    }
                }
            }
        }
    }
}

G('end');
info_log(G('begin', 'end', 6) . "s"); // 统计区间运行时间 精确到小数后6位
info_log(G('begin', 'end', 'm') . "kb"); // 统计区间内存使用情况
pid_destory();
?>