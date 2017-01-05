<?php
date_default_timezone_set('Asia/Shanghai');
/**
 * Created by PhpStorm.
 * User: Ray
 * Date: 14-4-24
 * Time: 下午3:00
 */
class ServiceError extends Exception {

    const ERROR_SYSTEM_ERROR = 500;
    const ERROR_JSONVALID_ERROR = 1000;

    public $violateInfo;
    public $violateErrorType = null;
    public static $error_message = array(
        self::ERROR_SYSTEM_ERROR => 'System inner error',
        self::ERROR_JSONVALID_ERROR => 'Json validator error'
    );

    public function __construct($errorId, $errorMsg = '') {
        $this->code = $errorId;
        $this->message = self::$error_message[$errorId];
        if (!empty($errorMsg)) {
            $this->message = $errorMsg;
        }
    }

    public  function getViolateInfo() {
        $violateInfo = json_decode($this->violateInfo,TRUE);
        $index = null;
        $field = null;
        if (!empty($violateInfo['violateEntity'])) {
            $entityStr = explode('.', $violateInfo['violateEntity']);
            $entityFather = str_replace(array('[', ']'), "_", $entityStr[0]);

            $entityFather = trim($entityFather, '_');
            $entityFatherStr = explode('_', $entityFather);
            $index = $entityFatherStr[1];
            $field = $entityStr[1];
        }
        $message = $violateInfo['violateMessage'];

        $errorMessage = array(
            'field' => $field,
            'message' => $message,
            'index' => $index,
        );
        return json_encode($errorMessage);
    }

}
