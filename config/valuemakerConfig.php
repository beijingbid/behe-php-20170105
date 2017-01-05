<?php 
namespace adxApiV2\config;

class valuemakerConfig{

	public $valuemaker_error_config = array(
    1000 => 'json 格式错误',
    1001 => '创意 ID 缺少或者错误',
    1002 => '创意 ID 重复',
    1003 => '创意代码缺少',
    1004 => '创意行业分类缺少或者错误',
    1005 => '创意格式缺少或者错误',
    1006 => '创意宽度缺少或者错误',
    1007 => '创意高度缺少或者错误',
    1008 => 'adomain_list 缺少或者错误',
    1009 => '系统中创意 ID 不存在',
    1010 => '修改失败，创意状态异常',
    1011 => '创意已过期（预留）',
    1012 => '创意时长缺少或者错误',
    1013 => '素材地址缺少或者错误',
    1014 => 'landingpage 缺少或者错误',
    1015 => 'landingpage 缺少点击检测宏{!vam_click_url}',);
	public $valuemaker_dsp_id = "vamaker-behe";
	public $valuemaker_token = "behe201409";
	public $valuemaker_material_banner_add = "http://ssp.vamaker.com/api/banner/add";
	public $valuemaker_material_banner_update = "http://ssp.vamaker.com/api/banner/update";
	public $valuemaker_material_video_add = "http://ssp.vamaker.com/api/video/add";
	public $valuemaker_material_video_update = "http://ssp.vamaker.com/api/video/update";
	public $valuemaker_material_status = "http://ssp.vamaker.com/api/banner/status";
	public $valuemaker_material_video_status = "http://ssp.vamaker.com/api/video/status";
	public $valuemaker_file_type = array('jpg' => 1, 'gif' => 2, 'png' => 1, 'swf' => 3, 'flv' => 5, 'mp4' => 6);
}

?>