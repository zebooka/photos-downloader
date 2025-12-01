<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

class ScannerFactory
{
    public static function getScannerForInput(InputInterface $input)
    {
        return (isFromStdin($input) || isPipelineForced($input))
            ? new ScannerTwo($input)
            : new ScannerIterator($input);
    }
}
