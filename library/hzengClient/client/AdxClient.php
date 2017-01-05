<?php
date_default_timezone_set('Asia/Shanghai');
class AdxClient
{
    public $dspId;
    public $token;
    public $gatewayUrl = "http://api.ex.hzeng.net/";

    public $format = "json";
    public $connectTimeout;
    public $readTimeout=60;

    public $checkRequest = true;
    protected $apiVersion = "1";
    protected $log = null;

    public function __construct()
    {
        $this->log = new SimpleLogger(sprintf("logs/%s.log", date("Y-m-d")));
    }

    public function __destruct()
    {
        unset($this->log);
    }

    public function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_INTERFACE, "125.94.212.22");
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && 0 < count($postFields)) {
            $postBodyString = "";
            $postMultipart = false;
            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }
        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);
        return $reponse;
    }

    public function execute($request)
    {
        $result = array();
        if ($this->checkRequest) {
            try {
                $request->check();
                
            } catch (Exception $e) {
                print_r(1231);
                $result['code'] = $e->getCode();
                $result['msg'] = $e->getMessage();
                return $result;
            }
        }
        $sysParams["dspId"] = $this->dspId;
        $sysParams["token"] = $this->token;
        $sysParams["format"] = $this->format;
        $sysParams["method"] = $request->getApiMethodName();
        $sysParams['v'] = $this->apiVersion;
        
        $apiParams = array('request' => json_encode($request->getApiParams()));
        $requestUrl = $this->gatewayUrl . "?";
        $requestUrl .= http_build_query($sysParams);
 
        try {
            $resp = $this->curl($requestUrl, $apiParams);
        } catch (Exception $e) {
            $this->log->error(array($sysParams["method"], $requestUrl, json_encode($apiParams), json_encode($apiParams), "HTTP_ERROR_" . $e->getCode(), $e->getMessage()));
            $result['code'] = $e->getCode();
            $result['msg'] = $e->getMessage();
            return $result;
        }

        $respWellFormed = false;
        if ("json" == $this->format) {
            $respObject = json_decode($resp, true);
            if (null !== $respObject) {
                $respWellFormed = true;
            }
        }

        if (false === $respWellFormed) {
            $this->log->error(array($sysParams["method"], $requestUrl, json_encode($apiParams), "HTTP_RESPONSE_NOT_WELL_FORMED", $resp));
            $result['code'] = 0;
            $result['msg'] = "HTTP_RESPONSE_NOT_WELL_FORMED";
            return $result;
        }

        if (isset($respObject['code'])) {
            $this->log->debug(array($this->appkey, $requestUrl, json_encode($apiParams), $resp));
        }
        return $respObject;
    }
}