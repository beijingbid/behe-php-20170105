<?php 
namespace adxApiV2\config;

class miaozhenConfig{

	public $miaozhen_error_config = array(
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
	public $miaozhen_token = "161c895d663b45ea8095d313158a0a6d";
	public $miaozhen_dsp_id = "11163";
	public $miaozhen_upload = "http://adexchange.thextrader.cn/dsp/api/upload";
	public $miaozhen_upload_status = "http://adexchange.thextrader.cn/dsp/api/status";
}
?>