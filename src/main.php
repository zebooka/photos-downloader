<?php

error_reporting(-1);
require_once dirname(__DIR__) . '/vendor/autoload.php';

$params = \Zebooka\Cli::parseParameters(
    array_slice($_SERVER['argv'], 1),
    array('f', 't'),
    array('f')
);

$froms = (array)$params['f'];

$iterator = array_reduce(
    $froms,
    function (&$iterator, $from) {
        /** @var \AppendIterator $iterator */
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from)));
        return $iterator;
    },
    new \AppendIterator()
);
$scannerIterator = new \Zebooka\Photo\ScannerIterator($iterator);

var_dump($scannerIterator->getIterator());
