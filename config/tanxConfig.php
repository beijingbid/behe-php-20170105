<?php 
namespace adxApiV2\config;

class tanxConfig{

	public $tanx_api_url = "http://gw.api.taobao.com/router/rest"; //线上环境调用地址
	public $tanx_api_advertiser_add_url = 'taobao.tanx.qualification.advertiser.add';
	public $tanx_material_add = 'taobao.tanx.audit.creative.add';

	public $tanx_material_status = 'taobao.tanx.creative.get';
	public $tanx_appkey = 23138453; //淘宝开放平台appkey(请自行申请)
	public $tanx_secret = "86dfeaaf7374b1e5d1561b2cf3f4614c"; //淘宝开放平台secret(跟appkey相匹配)
	public $tanx_memberId = 34016883; //DSP用户ID(请修改)
	public $tanx_userKey = "guc27xe55adithymniieccxxz9rfgfgb"; //每个DSP用户私有的key(请修改)
}
?>