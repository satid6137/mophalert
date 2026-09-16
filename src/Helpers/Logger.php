<?php
class Logger
{
    public static function write($message, $data = [])
    {
        $logFile = __DIR__ . '/../../logs/system.log';

        $log = "[" . date("Y-m-d H:i:s") . "] " . $message;

        if (!empty($data)) {
            $log .= " | " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($logFile, $log . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
