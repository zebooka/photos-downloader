<?php

namespace Zebooka\Photo;

class ScannerIterator implements \IteratorAggregate
{
    private $iterator;

    public function __construct(\Traversable $iterator)
    {
        $this->iterator = $iterator;
    }

    private function scan()
    {
        $regexp = self::supportedExtensionsRegExp();
        $basepaths = array();
        foreach ($this->iterator as $path) {
            if (preg_match($regexp, $path, $matches)) {
                $basepaths[substr($path, 0, strlen($path) - strlen($matches[1]))] = true;
            }
        }

        return array_keys($basepaths);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->scan());
    }

    static public function supportedExtensions()
    {
        return array(
            '3fr', // Hasselblad
            'arw', // Sony
            'bay', // Casio
            'cr2', // Canon
            'crw', // Canon
            'dcr', // Kodak
            'dng', // Adobe & Leica & Pentax
            'erf', // Epson
            'jpeg',
            'jpg',
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
            'tif',
            'tiff',
            'x3f', // Sigma
        );
    }

    static private function supportedExtensionsRegExp()
    {
        return '/(\.(' . implode('|', array_map('preg_quote', self::supportedExtensions())) . '))$/i';
    }
}
