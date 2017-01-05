<?php
if(ENV == 'development'){
  ini_set('display_errors', 'ON'); //open display error
  error_reporting ( E_ERROR ); //show error
}
define('PHP_PATH','/home/system/php/bin/php');
date_default_timezone_set('Asia/Shanghai');
ini_set('max_execution_time', '0'); //No execution time limit
ini_set('memory_limit', '-1'); //No execution memory limit
ini_set('default_socket_timeout', -1);  //不超时
//common func
require_once APP_PATH . '/library/function.php';

