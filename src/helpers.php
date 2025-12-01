<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

/**
 * @param InputInterface $input
 * @return ScannerInterface|ScannerTwo|ScannerIterator
 */
function scannerFactory(InputInterface $input): ScannerInterface
{
    return (isFromStdin($input) || isPipelineForced($input))
        ? new ScannerTwo($input)
        : new ScannerIterator($input);
}

/**
 * @return string[]
 */
function supportedExtensions(): array
{
    return array_merge(supportedPhotoExtensions(), supportedVideoExtensions());
}

/**
 * @return string[]
 */
function supportedPhotoExtensions(): array
{
    return [
        'jpg',
        'jpeg',
        'tiff',
        'tif',
        'png',

        'heic', // Apple
        'dng', // Adobe
        '3fr', // Hasselblad
        'arw', // Sony
        'bay', // Casio
        'cr2', // Canon
        'crw', // Canon
        'dcr', // Kodak
        'erf', // Epson
        'kdc', // Kodak
        'mef', // Mamiya
        'mrw', // Minolta
        'nef', // Nikon
        'nrw', // Nikon
        'orf', // Olympus
        'pef', // Pentax
        'ptx', // Pentax
        'r3d', // Red One
        'raf', // Fujifilm
        'raw', // Leica & Panasonic
        'rw2', // Panasonic
        'rwl', // Leica
        'sr2', // Sony
        'srf', // Sony
        'srw', // Samsung
        'x3f', // Sigma
    ];
}

/**
 * @return string[]
 */
function supportedVideoExtensions(): array
{
    return [
        'mkv',
        'mov',
        'avi',
        'mpg',
        'mp4',
        'mts',
        '3gp',
    ];
}

function from(InputInterface $input): array
{
    return (array)$input->getOption(Command::FROM);
}

/** @todo */
function isFromStdin(InputInterface $input): bool
{
    return false;
}

/** @todo */
function isPipelineForced(InputInterface $input): bool
{
    return false;
}

function isRecursive(InputInterface $input): bool
{
    return !(bool)$input->getOption(Command::NO_RECURSIVE);
}
