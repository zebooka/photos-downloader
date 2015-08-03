#!/usr/bin/env php
<?php

error_reporting(-1);
set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
);
set_exception_handler(
    function (\Exception $e) {
        fwrite(STDOUT, strval($e) . PHP_EOL);
        exit(1);
    }
);

$baseDir = dirname(__FILE__);
$buildDir = $baseDir . '/build';
$buildFile = $buildDir . '/' . basename($baseDir) . '.phar';
if (!is_dir($buildDir)) {
    fwrite(STDOUT, 'Creating build directory ' . escapeshellarg($buildDir) . '...' . PHP_EOL);
    if (!mkdir($buildDir, 0777, true)) {
        exit(1);
    }
}

$alias = basename($baseDir) . '.phar';
fwrite(STDOUT, 'Building ' . escapeshellarg($alias) . ' at ' . escapeshellarg($buildFile) . '...' . PHP_EOL);

if (is_file($buildFile)) {
    fwrite(STDOUT, 'Removing old build of ' . escapeshellarg($buildFile) . '...' . PHP_EOL);
    unlink($buildFile);
}

fwrite(STDOUT, 'Creating new phar...' . PHP_EOL);
$phar = new Phar(
    $buildFile,
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    $alias
);

fwrite(STDOUT, 'Adding files...' . PHP_EOL);
$dirs = array(
    $baseDir . '/src',
    $baseDir . '/res',
    $baseDir . '/vendor',
);
$phar->buildFromIterator(
    array_reduce(
        $dirs,
        function (&$iterator, $dir) {
            /** @var \AppendIterator $iterator */
            $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)));
            return $iterator;
        },
        new \AppendIterator()
    ),
    $baseDir
);

$stub =
    '#!/usr/bin/env php' . PHP_EOL .
    '<?php ' .
    'define(\'VERSION\', \'' . (exec('git describe --tags || git describe --all') ?: '?') . '\'); ' .
    'set_include_path(\'phar://' . $alias . '\' . PATH_SEPARATOR . get_include_path()); ' .
    'include \'phar://' . $alias . '/src/main.php\'; ' .
    '__HALT_COMPILER();' . PHP_EOL;
fwrite(STDOUT, 'Adding stub file...' . PHP_EOL);
$phar->setStub($stub);

fwrite(STDOUT, 'Setuping execute permissions...' . PHP_EOL);
passthru('chmod +x ' . escapeshellarg($buildFile));
