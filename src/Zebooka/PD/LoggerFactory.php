<?php

namespace Zebooka\PD;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{
    public static function logger()
    {
        $id = substr(md5(date('r') . '_' . rand(0, 1000000000)), 0, 5);
        $handler = new StreamHandler('php://stderr', Logger::DEBUG);
        $handler->setFormatter(new LineFormatter("%datetime% | %message%\n", 'Y-m-d H:i:s'));
        $monolog = new Logger($id, array($handler));
        return $monolog;
    }
}
