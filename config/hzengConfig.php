<?php 
namespace adxApiV2\config;

class hzengConfig{

	public $hz_dsp_id = 100008;
	public $hz_token = "43dbd01365f08205f892b028251a886b";
	public $hz_version = "1";
	public $hz_format = "json";
	public $hz_gatewayUrl = "http://api.ex.hzeng.net/";
	public $hz_ck = 'http://rtb.behe.com/ck?hzext=${EXT}&landing=';
	public $hz_vw = 'http://rtb.behe.com/vw?hzext=${EXT}&wp=${AUCTION_PRICE}';
	public $hz_material_file_type = array('swf' => true, 'flv' => true, 'gif' => true, 'jpg' => true);
	public $hz_file_ext_type = array('png' => 1, 'gif' => 1, 'jpg' => 1, 'flv' => 3, 'swf' => 2, 'mp4' => 3);
	public $hz_size = array(
	    '120*240' => 1,
	    '125*125' => 1,
	    '160*80' => 1,
	    //'160*520' => 1, //test
	    '256*58' => 1,
	    '300*50' => 1,
	    '480*70' => 1,
	    '180*150' => 1,
	    '200*200' => 1,
	    '240*180' => 1,
	    '250*250' => 1,
	    '264*160' => 1,
	    '300*100' => 1,
	    '300*250' => 1,
	    '336*280' => 1,
	    '480*160' => 1,
	    '500*200' => 1,
	    '468*60' => 1,
	    '580*90' => 1,
	    '640*60' => 1,
	    '650*90' => 1,
	    '728*90' => 1,
	    '760*90' => 1,
	    '950*90' => 1,
	    '960*90' => 1,
	    '960*60' => 1,
	    '1000*90' => 1,
	    '120*600' => 1,
	    '120*270' => 1,
	    '160*600' => 1
	);
}
?>