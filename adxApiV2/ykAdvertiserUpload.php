<?php

namespace adxApiV2;

define('APP_PATH', realpath(dirname(__FILE__).'/../'));
require_once APP_PATH . '/config/youkuConfig.php';
require_once APP_PATH . '/library/base.php';

use adxApiV2\config\youkuConfig as ykConfig;
use \library\base as base;

class ykAdvertiserUpload extends base {

    public $appName = "ykAdvertiserUpload";

    public function run() {
        $this->ykConfig = new ykConfig();
        $this->appEnv = 'development';
        $this->createPid($this->appName);
        $uploadAdvertiserList = $this->getAdvertiser(9,"99");
        if (!empty($uploadAdvertiserList)) {
            /*
             * {
              "dspid":"11268",
              "token":"92205dff8f9d48e1b7a26b0b88af7dc1",
              "advertiser":{
              "name":"合一网络技术有限公司",
              "brand":"优酷土豆",
              "firstindustry":3
              "secondindustry":5
              "qualifications":[{
              "name": "营业执照",    // 必须填写
              "url": "http://material.client.com/123.jpg", // 资质文件的URL地址
              "md5": "38b8c2c1093dd0fec383a9d9ac940515",
              "operation": "add"
              },{
              "name": "网络文化经营许可证",    // 必须填写
              "url": "http://material.client.com/456.jpg", // 资质文件的URL地址
              "md5": "38b8c2c1093dd0fec383a9d9ac940sdf",
              "operation": "add"
              }]
              }
              }
             */
            foreach ($uploadAdvertiserList as $key => $uploadInfo) {
                $companyName = $uploadInfo['companyName'];
                $companyAddress = $uploadInfo['companyAddress'];
                $mobile = $uploadInfo['mobile'];
                $realName = $uploadInfo['realName'];
                $brandName = $uploadInfo['brandName'];
                if(empty($brandName)):
                    $update_data = array();
                    $update_data['reason'] = "未填写品牌名称";
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveAdvertiser($uploadInfo['beheAccountId'],9,$update_data);
                    continue;
                endif;
                $brand = $brandName;
                $companyCateId = $uploadInfo['companyCateId'];
                $companySubCateId = $uploadInfo['companySubCateId'];
                $adAccountId = $uploadInfo['beheAccountId'];

                $sql = "select * from ig_industry_youku where xId='{$companyCateId}'";
                $firstindustryInfo = $this->select("again_info", $sql, true);
                if (!$firstindustryInfo) {
                    $update_data = array();
                    $update_data['reason'] = "行业不存在";
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveAdvertiser($uploadInfo['beheAccountId'],9,$update_data);
                    continue;
                }
                $sql = "select * from ig_industry_youku where xId='{$companySubCateId}'";
                $secondindustryInfo = $this->select("again_info", $sql, true);
                if (!$secondindustryInfo) {
                    $update_data = array();
                    $update_data['reason'] = "行业不存在";
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveAdvertiser($uploadInfo['beheAccountId'],9,$update_data);
                    continue;
                }
                $sql="select name,beheAccountId from adx_account_qualification where adxId = 9 and beheAccountId = {$adAccountId}";
                $uploadQualificationDbList=$this->select('again_main',$sql);
                $uploadQualificationNameList=array();
                foreach($uploadQualificationDbList as $qlist){
                    $uploadQualificationNameList[$qlist['beheAccountId']][$qlist['name']]=true;
                }
                $firstindustry = $firstindustryInfo[0]['sId'];
                $secondindustry = $secondindustryInfo[0]['sId'];
                $qualificationFilesAry = array();
                $file_logo_system_path = $this->config['file_logo_system_path'];
                $account_file_domain = $this->config['account_file_domain'];
                $offest = 0;
                $intelligence = $uploadInfo['qualificationInfo'];
                $qflag=true;
                if (!empty($intelligence['organization']) && file_exists($file_logo_system_path . $intelligence['organization'])) {
                    $qualificationFilesAry[$offest]['name'] = "组织机构资质";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['organization'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['organization']);
                    if(isset($uploadQualificationNameList[$adAccountId]["组织机构资质"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }

                    $offest++;
                }
                if (!empty($intelligence['businessLicense']) && file_exists($file_logo_system_path . $intelligence['businessLicense'])) {
                    $qualificationFilesAry[$offest]['name'] = "营业执照";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['businessLicense'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['businessLicense']);
                    if(isset($uploadQualificationNameList[$adAccountId]["营业执照"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $qflag=true;
                    $offest++;
                }
                if (!empty($intelligence['idCard']) && file_exists($file_logo_system_path . $intelligence['idCard'])) {
                    $qualificationFilesAry[$offest]['name'] = "法人身份证";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['idCard'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['idCard']);
                    if(isset($uploadQualificationNameList[$adAccountId]["法人身份证"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $offest++;
                }
                if (!empty($intelligence['ICP']) && file_exists($file_logo_system_path . $intelligence['ICP'])) {
                    $qualificationFilesAry[$offest]['name'] = "ICP";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['ICP'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['ICP']);
                    if(isset($uploadQualificationNameList[$adAccountId]["ICP"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $offest++;
                }
                if (!empty($intelligence['dutyPaid']) && file_exists($file_logo_system_path . $intelligence['dutyPaid'])) {
                    $qualificationFilesAry[$offest]['name'] = "组织机构完税证";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['dutyPaid'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['dutyPaid']);
                    if(isset($uploadQualificationNameList[$adAccountId]["组织机构完税证"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $offest++;
                }
                if (!empty($intelligence['networkBusinessLicense']) && file_exists($file_logo_system_path . $intelligence['networkBusinessLicense'])) {
                    $qualificationFilesAry[$offest]['name'] = "网络文化经营许可证";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['networkBusinessLicense'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['networkBusinessLicense']);
                    if(isset($uploadQualificationNameList[$adAccountId]["网络文化经营许可证"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $offest++;
                }
                if (!empty($intelligence['medicalBusinessLicense']) && file_exists($file_logo_system_path . $intelligence['medicalBusinessLicense'])) {
                    $qualificationFilesAry[$offest]['name'] = "医疗机构执业许可证";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['medicalBusinessLicense'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['medicalBusinessLicense']);
                    if(isset($uploadQualificationNameList[$adAccountId]["[医疗机构执业许可证]"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $offest++;
                }
                if (!empty($intelligence['medicalAdBusinessLicense']) && file_exists($file_logo_system_path . $intelligence['medicalAdBusinessLicense'])) {
                    $qualificationFilesAry[$offest]['name'] = "医疗广告审查证明";
                    $qualificationFilesAry[$offest]['url'] = $account_file_domain . $intelligence['medicalAdBusinessLicense'];
                    $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $intelligence['medicalAdBusinessLicense']);
                    if(isset($uploadQualificationNameList[$adAccountId]["医疗广告审查证明"])){
                        $qualificationFilesAry[$offest]['operation'] = 'update';
                    }else{
                        $qualificationFilesAry[$offest]['operation'] = 'add';

                    }
                    $offest++;
                }
                $gdtQualifications = $uploadInfo['adQualifications'];
                if (!empty($gdtQualifications)) {
                    foreach ($gdtQualifications as $gdtItem) {
                        if (file_exists($file_logo_system_path . $gdtItem['file'])) {

                            $qualificationFilesAry[$offest]['name'] = $gdtItem['name'];
                            $qualificationFilesAry[$offest]['url'] = $account_file_domain . $gdtItem['file'];
                            $qualificationFilesAry[$offest]['md5'] = md5_file($file_logo_system_path . $gdtItem['file']);
                            if(isset($uploadQualificationNameList[$adAccountId][$gdtItem['name']])){
                                $qualificationFilesAry[$offest]['operation'] = 'update';
                            }else{
                                $qualificationFilesAry[$offest]['operation'] = 'add';

                            }
                            $offest++;
                        }
                    }
                }

                if (empty($qualificationFilesAry)||$qflag==false) {
                    $update_data = array();
                    $update_data['reason'] = "没有上传资质";
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveAdvertiser($uploadInfo['beheAccountId'],9,$update_data);
                    continue;
                }


                $request = array();
                $request['dspid'] = $this->ykConfig->dspId;
                $request['token'] = $this->ykConfig->token;
                $request['advertiser']['name'] = $companyName;
                $request['advertiser']['brand'] = $brand;
                $request['advertiser']['contacts'] = "崔天琪";
                $request['advertiser']['tel'] = "85591131";
                $request['advertiser']['address'] = $companyAddress;
                if($uploadInfo['accountId']==1697006376){
                    $request['advertiser']['firstindustry'] = 8;
                    $request['advertiser']['secondindustry'] = 14;
                 
                }else{
                $request['advertiser']['firstindustry'] = (int)$firstindustry;
                $request['advertiser']['secondindustry'] = (int)$secondindustry;
               } 
               $request['advertiser']['qualifications'] = $qualificationFilesAry;
                $response = $this->postCurl($this->ykConfig->advertiserUploadApi, json_encode($request), 'json');

                if ($response['response']['result'] === 0) {
                    $update_data = array();

                    $update_data['reason'] = "";

                    $update_data['exchangeStatus'] = 0;
                    $update_data['uploadStatus'] = 1;
                    $update_data['mtime'] = date('Y-m-d H:i:s');
                    $this->saveAdvertiser($uploadInfo['beheAccountId'],9,$update_data);
                }else{
                    if($response['response']['result']==3){
                        $this->log($uploadInfo['accountId'],$this->appName);
                        $this->log(json_encode($request),$this->appName);
                        $this->log(json_encode($response),$this->appName);
                        $this->log($companyCateId.$firstindustryInfo[0]['name']);
                        $this->log($companySubCateId.$secondindustryInfo[0]['name']);
                        $update_data = array();
                    
                        foreach($response['response']['message'] as $c=>$error){
                            $update_data['reason'] = $error[0];
                        }
                        $update_data['mtime'] = date('Y-m-d H:i:s');
                        $this->saveAdvertiser($uploadInfo['beheAccountId'],9,$update_data);
                    }
                }

            }
        }
        $this->destoryPid($this->appName);
    }

}

$obj = new ykAdvertiserUpload();
$obj->run();
?>
