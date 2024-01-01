<?php

//// setup errors handling
//error_reporting(-1);
//set_exception_handler(
//    function (\Throwable $e) {
//        if (isset($GLOBALS['logger']) && $GLOBALS['logger'] instanceof \Monolog\Logger) {
//            $GLOBALS['logger']->addCritical($e);
//        } else {
//            error_log($e);
//        }
//        exit(1);
//    }
//);
//mb_internal_encoding('UTF-8');

// autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Zebooka\PD\Command;

// get locale
$locale = 'en';
foreach ([LC_ALL, LC_COLLATE, LC_CTYPE, LC_MESSAGES] as $lc) {
    if (preg_match('/^([a-z]{2})(_|$)/i', setlocale($lc, 0))) {
        $locale = setlocale($lc, 0);
        break;
    }
}
setlocale(LC_ALL, $locale);

// translations
$translator = \Zebooka\Translator\TranslatorFactory::translator(__DIR__ . '/../res', $locale);

// symfony console application
$application = new Application($translator->translate('appName'), VERSION);
$command = new Command($_SERVER['argv'][0], $locale);
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();

