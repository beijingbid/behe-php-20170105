<?php
date_default_timezone_set('Asia/Shanghai');
class SimpleLogger
{
    protected $separator = "\t";
    protected $logFile = "";

    private $fileHandle;

    public function __construct($filename = "logger.log") {
        $this->logFile = $filename;
    }

    public function __destruct() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }

    public function __call($method, $arguments) {
        if (is_array($arguments[0])) {
            array_unshift($arguments[0], '['.$method.']');
            $this->log($arguments[0]);
        } else {
            $this->log(sprintf("[%s]\t%s", $method, $arguments[0]));
        }
    }

    protected function getFileHandle()
    {
        if (null === $this->fileHandle) {
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            $this->fileHandle = fopen($this->logFile, "a");
        }
        return $this->fileHandle;
    }

    public function log($logData)
    {
        if ("" == $logData || array() == $logData) {
            return false;
        }
        if (is_array($logData)) {
            $logData = implode($this->separator, $logData);
        }
        $logData = $logData . "\n";
        $currentTime = date("Y-m-d H:i:s\t");
        fwrite($this->getFileHandle(), $currentTime . $logData);
    }
}