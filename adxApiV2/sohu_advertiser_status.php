<?php
namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/sohuConfig.php';
require_once APP_PATH . '/library/sohu.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\sohuConfig as sohuConfig;
use \library\base as base;

class sohuAdvertiserStatus extends base{

    public $appName = 'sohuAdvertiserStatus';

    public function run(){
        $this->sohuConfig = new sohuConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $key = $this->sohuConfig->sohu_dsp_id;
        $secret = $this->sohuConfig->sohu_token;
        $apiurl = $this->sohuConfig->sohu_advertiser_status;
        $str_sql = "select count(*) as num from adx_account where adxId = 17";
        $totalAry = $this->select("again_main",$str_sql);
        $total = $totalAry['num'];
        $pageTotal = ceil($total / 50);
        for ($i = 1; $i <= $pageTotal; $i++) {

            $post = array(
                'perpage' => 50,
                'page' => $i
            );
            $auth = new \Auth($key, $secret);
            $queryString = $auth->setMethod('post')->setUrl($apiurl)->setParams($post)->queryString();
            $str_return = $auth->curl($apiurl, '', $queryString);
            $account_list_info = json_decode($str_return, true);
            if ($account_list_info['status']) {
                foreach ($account_list_info['content']['items'] as $item) {
                    $db_data = array();
                    if ($item["status"] == 1 || $item["tv_status"] == 1) {
                        $db_data['adxStatus'] = 1;
                    } else {
                        $db_data['adxStatus'] = $item["status"];
                    }
                    $db_data['reason'] = $item["audit_info"];
                    $db_data['mtime'] = date('Y-m-d H:i:s');
                    $db_data['customerKey'] = $item['customer_key'];
                    $this->saveAdvertiser("companyName = '{$item['customer_name']}' and adxId = 17");
                }
            }
        }
        $this->destoryPid($this->appName);
    }
}

$obj = new sohuAdvertiserStatus();
$obj->run();
?>

