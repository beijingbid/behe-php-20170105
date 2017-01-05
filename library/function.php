<?php
function sendWeiXinMessage($message,$app,$author=""){
        $weixinConfig=config('weixin');
        $corpid=$weixinConfig['corpid'];//'wx9f2b242af3b79e7b';
        $secret=$weixinConfig['secret'];//'Eg8KwfnSXXq3bTC3fM3c-VVRtXtTc_8N7EXw4e5llA0GwJ-oo1ku6mv1H3rwARBC';
        $agentid=$weixinConfig['agentid'];//1;
        $toparty=$weixinConfig['toparty'];//3;
        $touser=$weixinConfig['touser'];//1;
        $url="https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
        $tokenJson=file_get_contents($url);
        $tokenInfo=json_decode($tokenJson,true);
        $accessToken=$tokenInfo['access_token'];
        $url="https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$accessToken}";
        $time=date('Y-m-d H:i:s');
        $content = "报警主机：125.94.212.22 192.168.1.168\r\n";
        $content.="告警时间：{$time}\r\n";
        $content.="告警程序：{$app}\r\n";
        $content.="问题详情：{$message}\r\n";
        $content.="程序作者：{$author}";
        $messageAry=array(
            'safe'=>0,
            'touser'=>$touser,
            'toparty'=>$toparty,
            'msgtype'=>'text',
            'agentid'=>$agentid,
            'text'=>array('content'=>$content)
        );
        $arr=postCurl($url,json_encode($messageAry,JSON_UNESCAPED_UNICODE),'json');
        info_log(json_encode($arr),'weixin');
}


function config($name) {
    if(empty($name)){
        return false;
    }else{
        $file=APP_PATH."/config/{$name}.php";
        $result=file_get_contents($file);
        return $result;
    }
}

?>
