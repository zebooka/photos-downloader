<?php

namespace Zebooka\PD;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;

class LoggerFactory
{
    public static function logger(InputInterface $input)
    {
        $id = substr(md5(date('r') . '_' . rand(0, 1000000000)), 0, 5);
        $ttyHandler = new StreamHandler('php://stderr', Configure::verboseLevel($input));
        $ttyHandler->setFormatter(new LineFormatter("%message%\n", null, true));
        $monolog = new Logger($id);
        $monolog->pushHandler($ttyHandler);
        if (null !== Configure::logFile($input)) {
            $logFileHandler = new StreamHandler(Configure::logFile($input), Configure::logLevel($input));
            $monolog->pushHandler($logFileHandler);
        }
        return $monolog;
    }
}
