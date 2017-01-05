<?php
namespace library;
require_once APP_PATH . '/config/common.php';
use config\common as config;
class base {
    public $appName;
    public $appEnv;
    public $dbDebug=true;
    public $dbConnect;
    public $db;
    public $cacheTime=3000;
    public $tagReasonAry = array(
        12 => true
    );

    public function __construct() {
        $configObj = new config();
        $this->config = $configObj->config;
    }
    public function setRedis($key, $content, $configSet = "local") {
        $redisConfig = $this->config[$configSet];
        $redis_list = new \Redis();
        $redis_host = $redisConfig['host'] ? $redisConfig['host'] : '127.0.0.1';
        $redis_port = $redisConfig['port'] ? $redisConfig['port'] : '6379';
        $redis_persistent = $redisConfig['persistent'] ? $redisConfig['persistent'] : 'connect';
        $redis_expire = $redisConfig['expire'] ? $redisConfig['expire'] : $this->cacheTime;
        $result = $redis_list->$redis_persistent($redis_host, $redis_port);
        $redis_prefix = $redisConfig['prefix'] ? $redisConfig['prefix'] : '';
        if ($result) {
            if ($redis_expire == 0) {
                $list = $redis_list->set($redis_prefix . $key, $content);
            } else {
                $list = $redis_list->SETEX($redis_prefix . $key, $redis_expire, $content);
            }
            return $list;
        } else {
            return false;
        }
    }

    public function getRedis($key, $configSet = "local") {
        $redisConfig = $this->config[$configSet];
        $redis_list = new \Redis();
        $redis_host = $redisConfig['host'] ? $redisConfig['host'] : '192.168.1.152';
        $redis_port = $redisConfig['port'] ? $redisConfig['port'] : '6372';
        $redis_persistent = $redisConfig['persistent'] ? $redisConfig['persistent'] : 'connect';
        $redis_expire = $redisConfig['expire'] ? $redisConfig['expire'] : '60';
        $redis_prefix = $redisConfig['prefix'] ? $redisConfig['prefix'] : '';
        $result = $redis_list->$redis_persistent($redis_host, $redis_port);
        if ($result) {
            $list = $redis_list->get($redis_prefix . $key);
            return $list;
        } else {
            return false;
        }
    }

    public function select($db, $sql, $cache = false) {
        if ($cache) {
            $redis_key = md5($sql);
            $result = $this->getRedis($redis_key);
            if ($result != false && $result != 'false') {
                return json_decode($result, true);
            } else {

                if ($this->dbConnect[$this->appEnv][$db]) {
                    $this->db = $this->dbConnect[$this->appEnv][$db];
                } else {
                    $this->connect($db);
                }
                if($this->dbDebug){
                    $this->log($sql,'debDebug');
                }
                $result = $this->db->query($sql);
                $list = array();
                if ($result) {
                    $list = $result->fetchAll(\PDO::FETCH_ASSOC);
                    $this->setRedis($redis_key, json_encode($list));
                }
                return $list;
            }
        } else {
            if ($this->dbConnect[$this->appEnv][$db]) {
                $this->db = $this->dbConnect[$this->appEnv][$db];
            } else {
                $this->connect($db);
            }
            if($this->dbDebug){
                    $this->log($sql,'debDebug');
            }
            $result = $this->db->query($sql);

            $list = array();
            if ($result) {

                $list = $result->fetchAll(\PDO::FETCH_ASSOC);
            }
            return $list;
        }
    }

    private function connect($dbname) {
     //   echo $this->config['allow_db'][$this->appEnv][$dbname];die();
        if (isset($this->config['allow_db'][$this->appEnv][$dbname])) {
            if (!$this->dbConnect[$this->appEnv][$dbname]) {

                $realdb=$this->config['allow_db'][$this->appEnv][$dbname]['dbname'];
                $offset=$this->config['allow_db'][$this->appEnv][$dbname]['offset'];
                $username = $this->config['db'][$offset]['username'];
                $password = $this->config['db'][$offset]['password'];
                $options = $this->config['db'][$offset]['options'];
                $port = $this->config['db'][$offset]['port'];
                $ip=$this->config['db'][$offset]['ip'];
                $dsn = "mysql:host={$ip};dbname={$realdb};port={$port}";
                $this->log($dsn,'db');
                try {
                    $dbconnect = new \PDO($dsn, $username, $password, $options);
                } catch (\PDOException $e) {
                    $message=$e->getMessage();
                   
                    $this->sendWeiXinMessage($message.$dsn,$this->appName);
                    $realdb=$this->config['allow_db'][$dbname]['dbname'];
                    $offset=$this->config['allow_db'][$dbname]['offset'];
                    $username = $this->config['db'][$offset]['username'];
                    $password = $this->config['db'][$offset]['password'];
                    $options = $this->config['db'][$offset]['options'];
                    $port = $this->config['db'][$offset]['port'];
                    $ip=$this->config['db'][$offset]['ip'];
                    $dsn = "mysql:host={$ip};dbname={$realdb};port={$port}";
                    $dbconnect = new \PDO($dsn, $username, $password, $options);
                }
                $this->dbConnect[$this->appEnv][$dbname]=$dbconnect;
                $this->db = $dbconnect;

            }
        } else {
            $this->log('db error'.$dbname, 'db');
            die();
        }
    }

    public function delete($db, $sql) {
        if ($this->dbConnect[$this->appEnv][$db]) {
            $this->db = $this->dbConnect[$this->appEnv][$db];
        } else {
            $this->connect($db);
        }
        if($this->dbDebug){
                    $this->log($sql,'debDebug');
        }
        return $this->db->exec($sql);
    }
    public function sql($db,$sql){
        if ($this->dbConnect[$this->appEnv][$db]) {
            $this->db = $this->dbConnect[$this->appEnv][$db];
        } else {
            $this->connect($db);
        }
        if($this->dbDebug){
             $this->log($sql,'debDebug');
        }
        return $this->db->exec($sql);
    }

    public function add($db, $tableName, $data) {
        if (!empty($data) && is_array($data)) {
            if ($this->dbConnect[$this->appEnv][$db]) {
                $this->db =$this->dbConnect[$this->appEnv][$db];
            } else {
                $this->connect($db);
            }
            $fieldAry = array();
            $fieldValueAry = array();
            foreach ($data as $field => $value) {
                $fieldAry[] = "`{$field}`";
                $fieldValueAry[] = "'{$value}'";
            }
            
            $sql = "INSERT INTO `{$tableName}` (" . implode(',', $fieldAry) . ") VALUES (" . implode(',', $fieldValueAry) . ")";
            if($this->dbDebug){
                    $this->log($sql,'debDebug');
            }
            return $this->db->exec($sql);
        } else {
            return false;
        }
    }

    public function save($db, $tableName, $where, $data) {
        if (!empty($data) && is_array($data)&&!empty($where)) {
            if ($this->dbConnect[$this->appEnv][$db]) {
                $this->db = $this->dbConnect[$this->appEnv][$db];
            } else {
                $this->connect($db);
            }
            $fieldValueAry = array();
            foreach ($data as $field => $value) {
                $fieldValueAry[] = "`{$field}`='{$value}'";
            }
            try {
                $sql = "UPDATE  `{$tableName}` SET  " . implode(',', $fieldValueAry) . " WHERE {$where}";
                if($this->dbDebug){
                    $this->log($sql,'debDebug');
                }
                return $this->db->exec($sql);
            } catch (Exception $exc) {
                $this->log($exc->getTraceAsString(), 'db');

                die;
            }
        } else {
            return false;
        }
    }
    function createPid($appName) {
        if (!empty($appName)) {
            //检查pid文件
            $this->G('begin');
            $pid = getmypid();
            $filename = APP_PATH . "/" . $appName . ".pid";
            if (file_exists($filename)) {
                $info = file_get_contents($filename);
                if ($info) {
                    $info_ary = explode('^', $info);
                    $old_pid = $info_ary[0];
                   
                    $app = $_SERVER['_'] . " " . APP_PATH . "/" . $appName . ".php";
                    //| wc -l
                    $php_output = array();
                    @exec("ps -ef|grep '{$app}' |grep -v 'grep' |grep -v '/bin/sh'|awk '{print $2}'", $php_output);
                    if (!in_array($old_pid, $php_output)) {
                        unlink($filename);
                    }
                    unset($php_output);
                }
                die;
            }
            //创建pid文件

            $src_pid = fopen($filename, "w");
            if ($src_pid === false) {
                echo "create pid error!";
                die;
            }

            fwrite($src_pid, $pid . '^' . time());
            fclose($src_pid);
        }
    }

    function destoryPid($appName) {
        if(!empty($appName)){
            $filename = APP_PATH . "/" . $appName . ".pid";
            $time=$this->G('begin','end',3);
            $this->log("end at time:{$time}s", $appName);
            unlink($filename);
        }
    }
     /**
     * 记录和统计时间（微秒）和内存使用情况
     * 使用方法:
     * <code>
     * G('begin'); // 记录开始标记位
     * // ... 区间运行代码
     * G('end'); // 记录结束标签位
     * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
     * echo G('begin','end','m'); // 统计区间内存使用情况
     * 如果end标记位没有定义，则会自动以当前作为标记位
     * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
     * </code>
     * @param string $start 开始标签
     * @param string $end 结束标签
     * @param integer|string $dec 小数位或者m
     * @return mixed
     */
    function G($start, $end = '', $dec = 4) {
    
        static $_info = array();
        static $_mem = array();
        if (is_float($end)) { // 记录时间
            $_info[$start] = $end;
        } elseif (!empty($end)) { // 统计时间和内存使用
            if (!isset($_info[$end]))
                $_info[$end] = microtime(TRUE);
            if (MEMORY_LIMIT_ON && $dec == 'm') {
                if (!isset($_mem[$end]))
                    $_mem[$end] = memory_get_usage();
                return number_format(($_mem[$end] - $_mem[$start]) / 1024);
            }else {
                return number_format(($_info[$end] - $_info[$start]), $dec);
            }
        } else { // 记录时间和内存使用
            $_info[$start] = microtime(TRUE);
            if (MEMORY_LIMIT_ON)
                $_mem[$start] = memory_get_usage();
        }
    }
    function getCurl($url, $type = "", $auth_info = "", $httpCode = false,$get_string=array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_INTERFACE, "116.31.70.42");
        if ($type == "json") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json'));
        } elseif ($type == "octet-stream") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/octet-stream'));
        }elseif($type=='pptv'){
            $dspId=$get_string['dspId'];
            $token=$get_string['token'];
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'dspId:'.$dspId,
                    'token:'.$token,
                ));

        }

        if ($auth_info != "") {
            //info_log($auth_info);
            curl_setopt($ch, CURLOPT_USERPWD, $auth_info);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);

        //info_log(curl_getinfo($ch,CURLINFO_HTTP_CODE));
        if ($httpCode) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);
        if ($httpCode) {
            return $httpCode;
        } else {
            return $data;
        }
    }

    
    function postCurl($url, $post_string, $type = "", $auth_info = "") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_INTERFACE, "116.31.70.42");
        if ($type == "json") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json'));
        } elseif ($type == "octet-stream") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/octet-stream'));
        } elseif ($type == 'multipart/form-data') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: multipart/form-data'));
        } elseif ($type == 'pptv') {
            $dspId = $post_string['dspId'];
            $token = $post_string['token'];
            unset($post_string['dspId']);
            unset($post_string['token']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'dspId:' . $dspId,
                    'token:' . $token,
                ));

            $post_string = $post_string['info'];
        }
        /*else {
            
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/x-www-form-urlencoded'));
                $post_data = $post_string;
                $o = "";
                foreach ($post_data as $k => $v) {
                    $o.= "$k=" . urlencode($v) . "&";
                }
                $post_string = substr($o, 0, -1);
            
        }*/
        if ($auth_info != "") {
            //log($auth_info);
            curl_setopt($ch, CURLOPT_USERPWD, $auth_info);
        }


        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		if ($this->dbDebug){
		$this->log($url);
		$this->log(json_encode($post_string));
		}else{
			$data = curl_exec($ch);
		}
        //
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('httpCode' => $httpCode, 'response' => json_decode($data, true));
    }


    function sendWeiXinMessage($message, $app, $author = "") {
        $weixinConfig =array(
            "corpid"=>'wx9f2b242af3b79e7b',
            "secret"=>'Eg8KwfnSXXq3bTC3fM3c-VVRtXtTc_8N7EXw4e5llA0GwJ-oo1ku6mv1H3rwARBC',
            "agentid"=>1,
            "toparty"=>3,
            "touser"=>1);
        $corpid = $weixinConfig['corpid']; //'wx9f2b242af3b79e7b';
        $secret = $weixinConfig['secret']; //'Eg8KwfnSXXq3bTC3fM3c-VVRtXtTc_8N7EXw4e5llA0GwJ-oo1ku6mv1H3rwARBC';
        $agentid = $weixinConfig['agentid']; //1;
        $toparty = $weixinConfig['toparty']; //3;
        $touser = $weixinConfig['touser']; //1;
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}";
        $tokenJson = file_get_contents($url);
        $tokenInfo = json_decode($tokenJson, true);
        $accessToken = $tokenInfo['access_token'];
        $url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$accessToken}";
        $time = date('Y-m-d H:i:s');
        $content = "报警主机：125.94.212.22 192.168.1.168\r\n";
        $content.="告警时间：{$time}\r\n";
        $content.="告警程序：{$app}\r\n";
        $content.="问题详情：{$message}\r\n";
        $content.="程序作者：{$author}";
        $messageAry = array(
            'safe' => 0,
            'touser' => $touser,
            'toparty' => $toparty,
            'msgtype' => 'text',
            'agentid' => $agentid,
            'text' => array('content' => $content)
        );
        $arr = $this->postCurl($url, json_encode($messageAry, JSON_UNESCAPED_UNICODE), 'json');
        
    }
    public function log($str_content, $appName = 'system') {
        $filename = APP_PATH . "/log/" . $appName . '/' . date('Y-m-d') . ".log";
        $today = date("Y/m/d") . " " . date("H:i:s");
        if ($this->createFolder(APP_PATH . "/log/" . $appName . '/')) {
            if (!$handle = fopen($filename, 'a')) {
                print "$today cannot open file：$filename";
                exit;
            }
            $str_content = $today . " " . $str_content;
            if (!fwrite($handle, $str_content . "\r\n")) {
                print "$today wirte error：$filename";
                exit;
            }
            echo $str_content . "\r\n";

            fclose($handle);
        } else {
            print "$today wirte error：$filename";
            exit;
        }
    }
    public function createFolder($path) {
        $result = is_dir($path) or ( $this->createFolder(dirname($path)) and mkdir($path, 0777));
        @exec("chown daemon:daemon $path");
        return true;
    }
    public function eventQueue($orderId,$type='order'){
        if($orderId>0){
            $result = $this->select('again_main',"select orderId from event_queue where orderId='{$orderId}' and processed='no' and status_memo=''");
            if (!$result) {
                $data=array();
                $data['type']=$type;
                $data['orderId']=$orderId;
                $data['processed']='no';
                $data['ctime']=time();
                $data['indexTime']=date('Y-m-d H:i:s');
                $this->add('again_v1_main','event_queue',$data);
            }
           
        }
    }

    public function getAdvertiser($exchangeId,$exchangeStatus=99) {

        $data = array();
        if ($exchangeId > 0) {
            $sql = "SELECT a.brandName,a.brandLogo,a.companyAddress,a.mobile,a.realName,a.companyCateId,a.beheAccountId,a.companySubCateId,a.qualificationInfo,b.adxId, b.beheAccountId, a.companyName,a.adQualifications,b.uploadStatus,a.companyUrl,a.businessLicenseName,b.customerKey
                FROM again_v1_main.behe_account AS a 
                RIGHT JOIN again_v1_main.adx_account AS b ON a.beheAccountId = b.beheAccountId
                WHERE b.adxId
                IN ( {$exchangeId} )
                AND b.adxStatus in ($exchangeStatus)";
            if ($this->dbDebug) {
                $this->log($sql);
            }
            $list = $this->select("again_v1_main", $sql);
        }
        if (!empty($list)){
            foreach($list as $k => $v):
                $list[$k]['qualificationInfo'] = json_decode($list[$k]['qualificationInfo'],true);
                $list[$k]['adQualifications'] = json_decode($list[$k]['adQualifications'],true);
            endforeach;
            $data = $list;
        }
        return $data;
    }

    public function getWaitStatusAdvertiser($where) {
        $data = array();
        $sql = "SELECT * from adx_account where $where";
        if ($this->dbDebug) {
            $this->log($sql);
        }
        $list = $this->select("again_v1_main", $sql);

        if (!empty($list)) {
            $data = $list;
        }
        return $data;
    }

    public function getWaitStatusMaterial($where) {
        $data = array();
        $sql = "SELECT adAccountId,orderId,id,fileType,fileUrl,advertId,exAdId,exCreativeId as exFileId from advert_adx_status where $where";
        if ($this->dbDebug) {
            $this->log($sql);
        }
        $list = $this->select("again_v1_main", $sql);

        if (!empty($list)) {
            $data = $list;
        }
        return $data;
    }

    public function saveAdvertiser($beheAccountId = '',$adxId = '',$updateData = array()){
         if(!empty($this->db) && intval($beheAccountId) && intval($adxId) && !empty($updateData)):
             $this->save('again_v1_main','adx_account',"  beheAccountId = {$beheAccountId} and adxId = {$adxId} ",$updateData);
         else:
             $this->log('更新数据信息有误,请重新提交');
         endif;
    }

    public function saveMaterial($where,$updateData,$adxId = ''){
         if(intval($adxId)):
              if($updateData['adxStatus'] == 2 && $updateData['reason']!=''):
                  $updateData['reason']="[adx机审]".$updateData['reason'];
              endif;
         endif;
         if(!empty($this->db) && !empty($where) && !empty($updateData)):
             $this->save('again_v1_main','advert_adx_status',$where,$updateData);
         else:
            $this->log('更新数据信息有误,请重新提交?');
         endif;
    }

    public function getVideoMaterial($exchangeId, $exchangeStatus = 99, $uploadStatus = 0, $ctime = "-1 week") {
        
        //获取近一周等待上传的素材
        if (empty($exchangeId)) {
            return array();
        }
        $ctime = date('Y-m-d H:i:s', strtotime($ctime));

        $uploadMaterialList = array();
        //查询所有审核通过的视频创意

        if($exchangeId == 19 || $exchangeId == 9):
            $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.type,m.youkuFileUrl, m.duration,om.id, om.orderId,om.jumpUrl as goUrl,om.monitorUrl as turl, om.campaignId, m.`name`, m.fileUrl, m.width, m.height
                FROM again_v1_main.`advert` AS om
                LEFT JOIN again_v1_main.video_material AS m ON om.materialId = m.id
                WHERE om.type =2
                AND om.id in ( select advertId
                FROM again_v1_main.`advert_adx_status`
                 WHERE adxStatus
                IN ({$exchangeStatus})
                AND uploadStatus in ({$uploadStatus})
                AND adxId in ({$exchangeId})
                and ctime>'{$ctime}'
                )
                AND om.status =2 AND m.status = 1 and m.youkuFileUrl <> ''";
        else:
            $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.type,m.youkuFileUrl, m.duration,om.id, om.orderId,om.jumpUrl as goUrl,om.monitorUrl as turl, om.campaignId, m.`name`, m.fileUrl, m.width, m.height
                FROM again_v1_main.`advert` AS om
                LEFT JOIN again_v1_main.video_material AS m ON om.materialId = m.id
                WHERE om.type =2
                AND om.id in ( select advertId
                FROM again_v1_main.`advert_adx_status`
                 WHERE adxStatus
                IN ({$exchangeStatus})
                AND uploadStatus in ({$uploadStatus})
                AND adxId in ({$exchangeId})
                and ctime>'{$ctime}'
                )
                AND om.status =2 AND m.status =1";
        endif;
        if ($this->dbDebug) {
            $this->log($sql);
        }
        $list = $this->select("again_v1_main", $sql);

        if (!empty($list)) {
            $uploadMaterialList = $list;
        }

        return $this->copyMaterial($uploadMaterialList);
    }

    public function copyMaterial($material) {
        $newMaterial = array();
        if (!empty($material)) {
            foreach ($material as $k => $item) {
                if ($item['fileStatus'] == 1) {

                    $systemFilePath = $this->config['file_system_path'] . $item['fileUrl'];
                    $ext = $this->config['file_type'][$item['type']];
                    $newFilePath = $this->config['file_system_path'] . '/' . date("Y/m/d") . "/";
                    $newFileName = md5(time() . rand(0, 1000000)) . "." . $ext;
                    $result = $this->create_folder($newFilePath);
                    if ($result) {
                        if (copy($systemFilePath, $newFilePath . $newFileName)) {
                            try {
                                $update_data = array();
                                $update_data['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                                $update_data['fileStatus'] = 0;
                                print_r($item);
                                if ($item['type'] == 1) {
                                    $this->save('again_v1_main', 'static_material', "id={$item['materialId']}", $update_data);
                                } elseif ($item['type'] == 2) {
                                    $this->save('again_v1_main', 'video_material', "id={$item['materialId']}", $update_data);
                                } else {
                                    $this->save('again_v1_main', 'static_material', "id={$item['materialId']}", $update_data);
                                }
                                $center_material_arr = array();
                                $center_material_arr['fileUrl'] = '/' . date("Y/m/d") . "/" . $newFileName;
                                
                                $this->save('again_v1_main', 'advert_adx_status', "advertId = {$item['id']} ", $center_material_arr);
                            } catch (Exception $exc) {
                                $this->log($exc->getMessage(), 'db');
                            }
                            continue;
                        } else {
                            $this->log("copy $systemFilePath to " . $newFilePath . $newFileName . "failed", "system");
                            continue;
                        }
                    } else {
                        $this->log("create file failed $newFilePath", "system");
                        continue;
                    }
                } else {
                    $newMaterial[] = $item;
                }
            }
        }
        return $newMaterial;
    }

    public function getAllowAdvertiser($adxId){
        if(intval($adxId)):
            $allowAccountIdAry = array();
            switch($adxId){
                case 9:
                    $data = $this->select('again_v1_main',"select accountId,beheAccountId from account where beheAccountId in (select beheAccountId from adx_account where adxStatus in (0,1) and adxId = 9) ");
                    foreach($data as $k => $v):
                        $allowAccountIdAry[$v['accountId']] = $v['beheAccountId'];
                    endforeach;
                    break;
                default:
                    $data = $this->select('again_v1_main',"select accountId,beheAccountId from account where beheAccountId in (select beheAccountId from adx_account where adxId = $adxId) ");
                    foreach($data as $k => $v):
                        $allowAccountIdAry[$v['accountId']] = $v['beheAccountId'];
                    endforeach;
            }
            
            return $allowAccountIdAry;
        else:
            return array();
        endif;
    }

    public function getBannerMaterial($exchangeId, $exchangeStatus = 99, $uploadStatus = 0, $ctime = "-1 week") {
        if (empty($exchangeId)) {
            return array();
        }
        $ctime = date('Y-m-d H:i:s', strtotime($ctime));


        $uploadMaterialList = array();
        //查询所有审核通过的静态创意
        if($exchangeId == 19 || $exchangeId == 9):
            $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.type, om.id, om.orderId,om.jumpUrl,om.monitorUrl, om.campaignId, m.name, m.fileUrl, m.width, m.height
                FROM again_v1_main.`advert` AS om
                LEFT JOIN again_v1_main.banner_material AS m ON om.materialId = m.id
                WHERE om.type =1
                AND om.id in ( select advertId
                FROM again_v1_main.`advert_adx_status`
                WHERE adxStatus
                IN ({$exchangeStatus})
                AND uploadStatus in ({$uploadStatus})
                AND adxId in ({$exchangeId})
                and ctime>'{$ctime}'
                )
                AND om.status =2 AND m.status =1 and m.youkuFileUrl <> ''";
        else:
            $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.type, om.id, om.orderId,om.jumpUrl,om.monitorUrl, om.campaignId, m.name, m.fileUrl, m.width, m.height
                FROM again_v1_main.`advert` AS om
                LEFT JOIN again_v1_main.banner_material AS m ON om.materialId = m.id
                WHERE om.type =1
                AND om.id in ( select advertId
                FROM again_v1_main.`advert_adx_status`
                WHERE adxStatus
                IN ({$exchangeStatus})
                AND uploadStatus in ({$uploadStatus})
                AND adxId in ({$exchangeId})
                and ctime>'{$ctime}'
                )
                AND om.status =2 AND m.status =1";
        endif;
        if ($this->dbDebug) {
            $this->log($sql);
        }
        $list = $this->select("again_v1_main", $sql);

        if (!empty($list)) {
            $uploadMaterialList = $list;
        }
        if($exchangeId == 19 || $exchangeId == 9):
            $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.type, om.id, om.orderId,om.jumpUrl,om.monitorUrl, om.campaignId, m.name, m.fileUrl, m.width, m.height
                FROM again_v1_main.`advert` AS om
                LEFT JOIN again_v1_main.banner_material AS m ON om.materialId = m.id
                WHERE om.type =4
                AND om.id
                IN (
                SELECT advertId
                FROM again_v1_main.`advert_adx_status`
                WHERE adxStatus
                IN ({$exchangeStatus})
                AND uploadStatus in ({$uploadStatus})
                AND adxId in ({$exchangeId})
                and ctime>'{$ctime}'
                )
                AND om.status =2 AND m.status =1 and m.youkuFileUrl <> ''";
        else:
            $sql = "SELECT om.adAccountId,m.fileStatus,om.materialId,om.type, om.id, om.orderId,om.jumpUrl,om.monitorUrl, om.campaignId, m.name, m.fileUrl, m.width, m.height
                FROM again_v1_main.`advert` AS om
                LEFT JOIN again_v1_main.banner_material AS m ON om.materialId = m.id
                WHERE om.type =4
                AND om.id
                IN (
                SELECT advertId
                FROM again_v1_main.`advert_adx_status`
                WHERE adxStatus
                IN ({$exchangeStatus})
                AND uploadStatus in ({$uploadStatus})
                AND adxId in ({$exchangeId})
                and ctime>'{$ctime}'
                )
                AND om.status =2 AND m.status =1";
        endif;
        $list = $this->select("again_v1_main", $sql);
        if (!empty($list)) {
            $uploadMaterialList = array_merge($uploadMaterialList, $list);
        }
        return $this->copyMaterial($uploadMaterialList);
    }

    public function event_queue($orderId,$type='order'){
        if($orderId>0){
            $result = $this->select('again_v1_main',"select orderId from event_queue where orderId='{$orderId}' and processed='no' and status_memo=''");
            if (!$result) {
                $data=array();
                $data['type']=$type;
                $data['orderId']=$orderId;
                $data['processed']='no';
                $data['ctime']=time();
                $data['indexTime']=date('Y-m-d H:i:s');
                $this->add('again_v1_main','event_queue',$data);
            }
           
        }
    }

    public function iqyPost($url, $header, $file = '') {
        $streamContent = "";
        if ($file) {


            $handle = fopen($file, 'rb');

            do {
                $dataUnit = fread($handle, 8192);
                if (strlen($dataUnit) == 0) {
                    break;
                }
                $streamContent .= $dataUnit;
            } while (true);
            fclose($handle);
        }
        $ch = curl_init();
        //设置请求方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $streamContent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, FALSE);
        $data = curl_exec($ch);
        return $data;
    }

    public function getdomain($url) {
        $host = strtolower($url);
        $host = str_replace('http//', '', $host);
        $hostAry = explode('.', $host);
        $newDomain = array();
        foreach ($hostAry as $k => $v) {
            if ($k > 0) {
                $newDomain[] = $v;
            }
        }
        $domain = implode('.', $newDomain);
        $domainAry = explode('/', $domain);
        $domainAry = explode('?', $domainAry[0]);

        return $domainAry[0];
    }

    public function getSign($arr) {
        $secret = "86dfeaaf7374b1e5d1561b2cf3f4614c";
        $str = $secret;
        foreach ($arr as $k => $v) {
            $str .= $k . $v;
        }
        $str .= $secret;
        return strtoupper(md5($str));
    }
}

?>
