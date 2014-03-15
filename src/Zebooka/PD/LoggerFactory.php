<?php

namespace Zebooka\PD;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{
    public static function logger(Configure $configure)
    {
        $id = substr(md5(date('r') . '_' . rand(0, 1000000000)), 0, 5);
        $ttyHandler = new StreamHandler('php://stderr', $configure->verboseLevel);
        $ttyHandler->setFormatter(new LineFormatter("%message%\n"));
        $monolog = new Logger($id);
        $monolog->pushHandler($ttyHandler);
        if (null !== $configure->logFile) {
            $logFileHandler = new StreamHandler($configure->logFile, $configure->logLevel);
            $monolog->pushHandler($logFileHandler);
        }
        return $monolog;
    }
}
