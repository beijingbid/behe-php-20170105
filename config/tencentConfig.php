<?php 
namespace adxApiV2\config;

class tencentConfig{

	public $tencent_dsp_id = "10030";
	public $tencent_token = "d24da2badadf58c2dd8ac4c4a048a8e9";
	public $tencent_advertiser_add = "http://open.adx.qq.com/client/sync";
	public $tencent_advertiser_status = "http://open.adx.qq.com/client/info";
	public $tencent_material_banner_add = "http://open.adx.qq.com/order/sync";
	public $tencent_material_status = "http://open.adx.qq.com/order/getstatus";
	public $tencent_ck = 'http://rtb.behe.com/ck?info=${EXT}&landing=';
	public $tencent_vw = 'http://rtb.behe.com/vw?info=${EXT}&wp=${AUCTION_ENCRYPT_PRICE}';
	public $tencent_error = array(
	    601 => "文件加载失败",
	    602 => "未知的文件格式，文件的格式无法识别",
	    603 => "不支持的文件格式,目前支持的文件格式：jpg,gif,png,swf,flv",
	    604 => "Flv素材获取不到时长信息",
	    605 => "URL对应的素材发生了变化，请换一个URL",
	    606 => "执行插入过程中发生了错误，请关注是否是同时上传",
	    607 => "文件过大",
	    609 => "素材URL为空或者是地址不合法",
	    610 => "目标地址为空或者是地址不合法",
	    611 => "客户名称为空",
	    612 => "第三方曝光监测地址错误",
	    613 => "素材过大，超过素材的大小限制",
	    614 => "传入的file_info格式错误",
	    615 => "URL对应的客户发生变化，不能上传",
	    616 => "同一次请求中，一个素材URL出现了多次",
	    617 => "限制素材上传个数，在一次请求中素材个数不能超过50个",
	    618 => "第三方曝光监测地址不在白名单里",
	    619 => "第三方曝光监测数目超出限制",
	    620 => "dsp_order_id 含有不合法字符或长度超过了64",
	    621 => "内部错误，请稍后再尝试调用接口",
	    622 => "内部错误，请稍后再尝试调用接口",
	    623 => "内部错误，请稍后再尝试调用接口",
	    624 => "内部错误，请稍后再尝试调用接口",
	    635 => "同一个DSP侧的素材ID下的文件有一些参数的值不相等",
	    636 => "文字链素材，file_text参数不合法",
	    637 => "文字链素材，必须使用dsp_order_id",
	    638 => "该dsp_order_id 下有素材检验不通过，跳过该创意单",
	    639 => "同一个dsp_order_id下的素材过多，达到上限",
	    640 => "传入的order_info格式错误，无法解析成数组",
	    641 => "同一次API请求中，一个dsp_order_id出现了多次",
	    642 => "Flv素材中监测不到音频信息",
	    643 => "该用户不允许上传曝光监测点");
}
?>