<?php 
namespace adxApiV2\config;

class baiduConfig{
	public $baidu_dsp_id = 6770363;
	public $baidu_token = "aff0dee7ab65ab560e9b91499a308ac5";
	public $baidu_ck = "http://rtb.behe.com/ck?info=%%EXT_DATA%%&landing=";
	public $baidu_vw = "http://rtb.behe.com/vw?info=%%EXT_DATA%%&wp=%%PRICE%%";
	public $baidu_advertiser_add = "https://api.es.baidu.com/v1/advertiser/add";
	public $baidu_advertiser_update = "https://api.es.baidu.com/v1/advertiser/update";
	public $baidu_advertiser_status = "https://api.es.baidu.com/v1/advertiser/queryQualification";
	public $baidu_advertiser_white_status = "https://api.es.baidu.com/v1/advertiser/get";
	public $baidu_material_update = "https://api.es.baidu.com/v1/creative/update";
	public $baidu_material_add = "https://api.es.baidu.com/v1/creative/add";
	public $baidu_material_status = "https://api.es.baidu.com/v1/creative/queryAuditState";
	public $baidu_material_dynamic_status = "https://api.es.baidu.com/v1/creative/dynamic/getAll";
	public $baidu_material_file_type = array('swf' => true, 'flv' => true, 'gif' => true, 'jpg' => true);
	public $baidu_file_ext_type = array('png' => 1, 'gif' => 1, 'jpg' => 1, 'flv' => 3, 'swf' => 2, 'mp4' => 3);
	public $baidu_size = array(
	    "580*90" => true,
	    "180*150" => true,
	    "640*90" => true,
	    "760*90" => true,
	    "125*125" => true,
	    "500*200" => true,
	    "300*100" => true,
	    "120*240" => true,
	    "300*250" => true,
	    "960*90" => true,
	    "336*280" => true,
	    "200*200" => true,
	    "728*90" => true,
	    "640*60" => true,
	    "640*80" => true,
	    "960*60" => true,
	    "468*60" => true,
	    "160*600" => true,
	    "120*600" => true,
	    "250*250" => true,
	);
}
?>