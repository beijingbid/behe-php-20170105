<?php 
namespace adxApiV2\config;

class sohuConfig{

	public $sohu_dsp_id = "3aeb891d61d0808889f6b9a49895c288";
	public $sohu_token = "62ff390de140d10bd0f41713e70848bf";
	public $sohu_size = array(
	    '100×100' => 30,
	    '120×170' => 30,
	    '120×270' => 30,
	    '125×125' => 30,
	    '160×140' => 30,
	    '160×205' => 30,
	    '160×260' => 30,
	    '260×105' => 30,
	    '260×160' => 30,
	    '260×210' => 30,
	    '260×300' => 30,
	    '260×70' => 30,
	    '290×105' => 30,
	    '300×100' => 30,
	    '300×105' => 30,
	    '300×250' => 30,
	    '300×300' => 30,
	    '300×500' => 30,
	    '320×100' => 30,
	    '375×100' => 30,
	    '590×105' => 30,
	    '600×150' => 30,
	    '640×320' => 30,
	    '710×100' => 30,
	    '728×90' => 30,
	    '760×100' => 30,
	    '950×100' => 30,
	    '950×60' => 30,
	    '960×90' => 30,
	    '970×90' => 30,
	    '980×100' => 30
	);
	public $sohu_vw = "http://rtb.behe.com/vw?info=%%EXT%%&wp=%%WINPRICE%%";
	public $sohu_ck = "http://rtb.behe.com/ck?info=%%EXT%%&landing=";
	public $sohu_material_add = 'http://api.ad.sohu.com/exchange/material/create';
	public $sohu_material_delete = 'http://api.ad.sohu.com/exchange/material/delete';
	public $sohu_material_status = 'http://api.ad.sohu.com/exchange/material/list';
	public $sohu_advertiser_status = 'http://api.ad.sohu.com/exchange/customer/list';
	public $sohu_advertiser_create = 'http://api.ad.sohu.com/exchange/customer/create';
	public $sohu_advertiser_update = 'http://api.ad.sohu.com/exchange/customer/update';
}
?>