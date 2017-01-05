<?php 
namespace adxApiV2\config;

class allyesConfig{

	public $allyes_dsp_id = "mediamax-behe";
	public $allyes_token = "behe20131011";
	public $allyes_material_banner_add = "http://mediamax.quantone.com/creatives/add";
	public $allyes_material_banner_update = "http://mediamax.quantone.com/creatives/update";
	public $allyes_material_video_add = "http://mediamax.quantone.com/api/buyer/v1/creativevideo/add";
	public $allyes_material_video_update = "http://mediamax.quantone.com/api/buyer/v1/creativevideo/update";
	public $allyes_material_status = "http://mediamax.quantone.com/creatives/status";
	public $allyes_material_video_status = "http://mediamax.quantone.com/api/buyer/v1/creativevideo/status";
	public $allyes_error_config = array(
	    '202010' => '缺少创意名称',
	    '202011' => '创意名称输入不合法',
	    '202030' => '缺少创意编号',
	    '202031' => '创意编号输入不合法',
	    '202032' => '创意编号重复',
	    '202051' => '创意类型输入值过小或缺少创意类型',
	    '202052' => '创意类型输入值过大',
	    '202053' => '视频创意类型选择为txt',
	    '202071' => '创意宽度输入值过小',
	    '202072' => '创意宽度输入值过大',
	    '202073' => '缺少创意宽度',
	    '202091' => '创意高度输入值过小',
	    '202092' => '创意高度输入值过大',
	    '202093' => '缺少创意高度',
	    '202111' => '缺少创意分类标签',
	    '202112' => '创意分类标签不存在',
	    '202131' => '缺少创意代码',
	    '202151' => '缺少创意ID',
	    '202191' => '修改失败创意状态为(人工/机器)待审核或已过期',
	    '202211' => '创意已过期',
	    '202251' => '缺少xmltype',
	    '202252' => 'xmltype输入值错误',
	    '202221' => '缺少视频时长',
	    '202222' => '时长输入值错误',
	    '202231' => '缺少视频素材URL',
	    '202232' => '视频素材URL不合法',
	    '202241' => '缺少跳转URL',
	    '202242' => '跳转URL不合法');
}
?>