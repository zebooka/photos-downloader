<?php

require_once '../vendor-dev/autoload.php';

error_reporting(E_ALL & ~E_DEPRECATED);

// Temporary fix for PHPUnit 4.8, before dropping support for PHP 5.4-5.6
\PHPUnit\Framework\Error\Deprecated::$enabled = false;
