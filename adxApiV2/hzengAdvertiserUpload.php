<?php
namespace adxApiV2;

error_reporting(1);
define('APP_PATH', realpath(dirname(__FILE__).'/../'));

require_once APP_PATH . '/config/hzengConfig.php';
require_once APP_PATH . '/library/base.php';
require_once APP_PATH . '/library/hzengClient/client/HzSdk.php';
require_once APP_PATH . '/library/hzengClient/client/request/AdvertiserAddRequest.php';

use adxApiV2\config\hzengConfig as hzengConfig;
use \library\base as base;

class hzengAdvertiserUpload extends base{

	public $appName = 'hzengAdvertiserUpload';

	public function run(){
		$this->hzengConfig = new hzengConfig();
		$this->appEnv = 'development';
		$this->createPid($this->appName);
		$arr_client_list = $this->getAdvertiser(25,99);
		$total = count($arr_client_list);
		$date = date("Y-m-d H:i:s");
		if ($arr_client_list) {

		    foreach ($arr_client_list as $k => $account_item) {
		        $client = new \AdxClient();
		        $client->dspId = $this->hzengConfig->hz_dsp_id;
		        $client->token = $this->hzengConfig->hz_token;
		        if($account_item['uploadStatus'] == 0):
		        	$req = new \AdvertiserAddRequest();
		        else:
		        	$req = new \AdvertiserUpdateRequest();
		        endif;
		        if(strpos($account_item["companyUrl"],'://')!==false){
		            $url=$account_item["companyUrl"];
		            
		        }else{
		           $url="http://".$account_item["companyUrl"]; 
		        }
		        
		        $entity = array(
		            "advertiserId" => (int) $account_item['beheAccountId'],
		            "advertiserLiteName" => $account_item["companyName"],
		            "advertiserName" => $account_item['qualificationInfo']["businessLicense"]?$account_item['qualificationInfo']["businessLicense"]:$account_item["companyName"],
		            "siteName" => $account_item["companyName"],
		            "siteUrl" => $url,
		        );
		        $req->addEntity($entity);
		        $arr_return = $client->execute($req);
		        if (!empty($arr_return)) {
		            if ($arr_return['status'] === 0) {
		                $update_data = array();
		                $update_data['reason'] = '';
		                $update_data['adxStatus'] = 0;
		                $update_data['uploadStatus'] = 1;
		                $update_data['uploadName'] = $account_item['companyName'];
		                $update_data['mtime'] = $date;
		                $this->saveAdvertiser("beheAccountId = {$account_item['beheAccountId']} and adxId=25",$update_data);
		            }
		        }
		    }
		}
		$this->destoryPid($this->appName);
	}
}

$obj = new hzengAdvertiserUpload();
$obj->run();
?>


