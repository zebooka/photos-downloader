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

// setup logger

// read config
$config = new \Zebooka\PD\Configure($_SERVER['argv']);
var_dump($config);
