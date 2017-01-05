<?php 
namespace adxApiV2\config;

class snkConfig{

	public $snk_dsp_id = 100001;
	public $snk_token = "e5ec67e017619764b735c8104b0e6451";
	public $snk_ck = "http://rtb.behe.com/ck?info=%%EXT%%&landing=";
	public $snk_vw = "http://rtb.behe.com/vw?info=%%EXT%%&wp=%%WINPRICE%%";
	public $snk_advertiser_add = "http://api.rtbs.cn/v1/advertiser/add";
	public $snk_advertiser_status = "http://api.rtbs.cn/v1/advertiser/queryQualification";
	public $snk_material_update = "http://api.rtbs.cn/v1/creative/update";
	public $snk_material_add = "http://api.rtbs.cn/v1/creative/add";
	public $snk_material_status = "http://api.rtbs.cn/v1/creative/queryAuditState";
	public $snk_material_file_type = array('swf' => true, 'flv' => true, 'mp4' => true, 'gif' => true, 'jpg' => true);
	public $snk_file_ext_type = array('png' => 1, 'gif' => 1, 'jpg' => 1, 'flv' => 3, 'swf' => 2, 'mp4' => 3);
}
?>