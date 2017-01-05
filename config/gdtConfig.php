<?php 
namespace adxApiV2\config;

class gdtConfig{

	public $gdt_dsp_id = 620222;
	public $gdt_token = "NjgyMzgxLDE0MzYyMzYwODIsNTEyNGI4NzBkZTlkNjIxZmRiYThjZmZmNTRkOWI0Nzk=";
	public $gdt_ck = "http://rtb.behe.com/ck?info=%%CLICK_PARAM%%&landing=";
	public $gdt_vw = "http://rtb.behe.com/vw?info=%%IMPRESSION_PARAM%%&wp=%%WIN_PRICE%%";
	public $gdt_advertiser_add = "https://api.e.qq.com/adx/v1/advertiser/add";
	public $gdt_advertiser_update = "https://api.e.qq.com/adx/v1/advertiser/update";
	public $gdt_advertiser_qualifications = "https://api.e.qq.com/adx/v1/advertiser/update_ad_qualifications";
	public $gdt_advertiser_status = "https://api.e.qq.com/adx/v1/advertiser/get_review_status";
	public $gdt_material_update = "https://api.e.qq.com/adx/v1/creative/update";
	public $gdt_material_add = "https://api.e.qq.com/adx/v1/creative/add";
	public $gdt_material_status = "https://api.e.qq.com/adx/v1/creative/get_review_status";
	public $gdt_material_file_type = array('png' => true, 'jpg' => true, 'gif' => true);
	public $gdt_size = array(
	    "140*425" => 50,
	    "200*162" => 30,
	    "198*40" => 30
	);
	public $gdt_creative_spec_list = array(
	    "140*425" => 96,
	    "200*162" => 99,
	    "198*40" => 12
	);
	public $gdt_material_qualifications = "https://api.e.qq.com/adx/v1/advertiser/update_ad_qualifications";
}
?>