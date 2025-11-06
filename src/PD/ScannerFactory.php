<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

class ScannerFactory
{
    public static function getScannerFromInput(InputInterface $input)
    {

    }

    public static function getScannerIterator(): \Iterator
    {
    }

    public static function getScannerGenerator(): \Generator
    {
    }

    /**
     * @return string[]
     */
    public static function supportedExtensions(): array
    {
        return array_merge(self::supportedPhotoExtensions(), self::supportedVideoExtensions());
    }

    /**
     * @return string[]
     */
    public static function supportedPhotoExtensions(): array
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
    public static function supportedVideoExtensions(): array
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
}
