<?php

// setup errors handling
error_reporting(-1);
set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
);
set_exception_handler(
    function (\Exception $e) {
        error_log($e);
        exit(1);
    }
);

// autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// read configure
$configure = new \Zebooka\PD\Configure($_SERVER['argv']);

// setup logger
$logger = \Zebooka\PD\LoggerFactory::logger($configure);

// translations
$translator = \Zebooka\PD\TranslatorFactory::translator(__DIR__ . '/../res', setlocale(LC_CTYPE, 0));

$version = trim(file_get_contents(__DIR__ . '/../res/VERSION'));
$logger->addInfo($translator->translate('appName', array($version)));
$logger->addInfo($translator->translate('copyrightInfo'));

if ($configure->help) {
    $logger->addInfo(new \Zebooka\PD\ConfigureView($configure, $translator, 100));
    exit(0);
}
