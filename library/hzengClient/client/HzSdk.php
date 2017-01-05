<?php
date_default_timezone_set('Asia/Shanghai');
$lib_path = array();
array_push($lib_path, dirname(__FILE__));
array_push($lib_path, 'request');
function autoLoad($className) {
    global $lib_path;
    foreach($lib_path as $path) {
        $filePath = $path . '/' . $className . '.php';
        if(file_exists($filePath)) {
          
            require_once $filePath;
        }
    }
}

spl_autoload_register('autoLoad');