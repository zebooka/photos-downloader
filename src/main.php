<?php

// setup errors handling
error_reporting(-1);
set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
);
set_exception_handler(
    function (\Throwable $e) {
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

$version = trim(file_get_contents(__DIR__ . '/../res/VERSION'));
$logger->addInfo($translator->translate('appName', array(VERSION, $version)));
$logger->addInfo($translator->translate('copyrightInfo'));
$view = new \Zebooka\PD\ConfigureView($configure, $translator, \Zebooka\Utils\Cli\Size::getTerminalWidth() ?: 80);

if ($configure->help || 1 === count($_SERVER['argv'])) {
    $logger->addInfo($view->render());
    exit(0);
} else {
    $logger->addInfo($view->renderConfiguration());
}

// validate regexp
try {
    preg_match($configure->regexpFilter ?: '/test/', 'test');
} catch (\ErrorException $e) {
    $logger->addCritical($translator->translate('error/regexpInvalid', array($configure->regexpFilter)));
    exit(1);
}
try {
    preg_match($configure->regexpNegativeFilter ?: '/test/', 'test');
} catch (\ErrorException $e) {
    $logger->addCritical($translator->translate('error/regexpInvalid', array($configure->regexpNegativeFilter)));
    exit(1);
}

// processing
$processor = new \Zebooka\PD\Processor(
    $configure,
    new \Zebooka\PD\Tokenizer($configure, new \Zebooka\PD\ExifAnalyzer($configure)),
    new \Zebooka\PD\Assembler($configure, new \Zebooka\PD\Hashinator()),
    new \Zebooka\PD\BunchCache(),
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
