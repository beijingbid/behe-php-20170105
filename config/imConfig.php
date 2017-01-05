<?php 
namespace adxApiV2\config;

class imConfig{

	public $im_error_config = array(
    '101' => '文件加载失败',
    '102' => '不支持的文件格式,目前支持的文件格式：jpg,gif,png,swf,flv',
    '103' => '素材获取不到时长信息',
    '104' => '执行插入过程中发生了错误',
    '105' => '物料所属的广告主为空',
    '106' => '物料生效时间为空或者不能解析',
    '107' => '物料失效时间为空或者不能解析',
    '108' => '未知错误，请稍候再试',
    '109' => '物料尺寸不符合广告位要求',
    '110' => '物料时长不符合广告位要求');
	public $im_token = "95af0e44-3379-6392-a1ea-4405f0a57b4a";//xhkom0hexrvwuuo8jdsq0v9cdjg62ouu
	public $im_dsp_id = "oaklfoesfd";
	public $im_upload = "http://exch.valuecome.com/serv/dsp/api/upload.action";
	public $im_upload_status = "http://exch.valuecome.com/serv/dsp/api/status.action";
}
?>