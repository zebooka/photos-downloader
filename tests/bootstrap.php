<?php

require_once '../vendor-dev/autoload.php';

// Temporary fix for PHPUnit 4.8, before dropping support for PHP 5.4-5.6
PHPUnit_Framework_Error_Deprecated::$enabled = false;
