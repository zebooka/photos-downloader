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
        if (isset($GLOBALS['logger']) && $GLOBALS['logger'] instanceof \Monolog\Logger) {
            $GLOBALS['logger']->addCritical($e);
        } else {
            error_log($e);
        }
        exit(1);
    }
);
mb_internal_encoding('UTF-8');

// autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// read configure
$configure = new \Zebooka\PD\Configure(
    $_SERVER['argv'],
    json_decode(file_get_contents(__DIR__ . '/../res/tokens.json'), true)
);

// setup logger
$logger = \Zebooka\PD\LoggerFactory::logger($configure);

// translations
$translator = \Zebooka\Translator\TranslatorFactory::translator(__DIR__ . '/../res', setlocale(LC_CTYPE, 0));

$version = trim(file_get_contents(__DIR__ . '/../res/VERSION'));
$logger->addInfo($translator->translate('appName', array(VERSION, $version)));
$logger->addInfo($translator->translate('copyrightInfo'));

if ($configure->help || 1 === count($_SERVER['argv'])) {
    $view = new \Zebooka\PD\ConfigureView($configure, $translator, \Zebooka\Utils\Cli\Size::getTerminalWidth() ? : 80);
    $logger->addInfo($view->render());
    exit(0);
} else {
    $view = new \Zebooka\PD\ConfigureView($configure, $translator, \Zebooka\Utils\Cli\Size::getTerminalWidth() ? : 80);
    $logger->addInfo($view->renderConfiguration());
}

// validate regexp
try {
    preg_match($configure->regexpFilter ? : '/test/', 'test');
} catch (\ErrorException $e) {
    $logger->addCritical($translator->translate('error/regexpInvalid', array($configure->regexpFilter)));
    exit(1);
}
try {
    preg_match($configure->regexpNegativeFilter ? : '/test/', 'test');
} catch (\ErrorException $e) {
    $logger->addCritical($translator->translate('error/regexpInvalid', array($configure->regexpNegativeFilter)));
    exit(1);
}

// processing
$processor = new \Zebooka\PD\Processor(
    $configure,
    new \Zebooka\PD\Tokenizer($configure, new \Zebooka\PD\ExifAnalyzer($configure)),
    new \Zebooka\PD\Assembler($configure, new \Zebooka\PD\Hashinator()),
    new \Zebooka\Utils\Executor(),
    $logger,
    $translator
);
$i = 0;
foreach (new \Zebooka\PD\ScannerIterator($configure->from, $configure->recursive) as $fileBunch) {
    $processor->process($fileBunch);
    $i++;
    if ($configure->limit && $i >= $configure->limit) {
        $logger->addInfo($translator->translate('processedFilesLimitWasReached', array($configure->limit)));
        break;
    }
}

$logger->addInfo($translator->translate('xFilesProcessed', array($i)));
$logger->addInfo(
    $translator->translate(
        'xBytesProcessed',
        array(\Zebooka\Utils\Size::humanReadableSize($processor->bytesProcessed()))
    )
);
$logger->addInfo(
    $translator->translate(
        'peakMemoryUsage',
        array(\Zebooka\Utils\Size::humanReadableSize(memory_get_peak_usage(true)))
    )
);
