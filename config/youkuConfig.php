<?php

namespace adxApiV2\config;

class youkuConfig {

    public $dspId = "11114";
    public $token = "16d9c1194a9d4a3f9c3ee393131bded9";
    public $reportApi = "http://miaozhen.atm.youku.com/dsp/api/report";
    public $advertiserUploadApi = "http://miaozhen.atm.youku.com/dsp/api/uploadadvertiser";
    public $advertiserStatusApi = "http://miaozhen.atm.youku.com/dsp/api/getadvertiser";
    public $materialUploadApi = "http://miaozhen.atm.youku.com/dsp/api/upload";
    public $materialStatusApi = "http://miaozhen.atm.youku.com/dsp/api/status";
    public $errorConfig = array('101' => '文件加载失败',
        '102' => '不支持的文件格式,目前支持的文件格式：jpg,gif,png,swf,flv',
        '103' => '素材获取不到时长信息',
        '104' => '执行插入过程中发生了错误',
        '105' => '物料所属的广告主为空',
        '106' => '物料生效时间为空或者不能解析',
        '107' => '物料失效时间为空或者不能解析',
        '108' => '未知错误，请稍候再试',
        '109' => '物料尺寸不符合广告位要求',
        '110' => '物料时长不符合广告位要求');
    public $advertiserStatus = array('待审核' => 0, '通过' => 1, '拒绝' => 2);
    public $materialStatus = array('待审核' => 0, '通过' => 1, '不通过' => 2);

}

?>