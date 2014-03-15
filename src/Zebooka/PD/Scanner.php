<?php

namespace Zebooka\PD;

class Scanner
{
    private $files = array();
    private $dirs = array();

    public function __construct(array $sourcePaths)
    {
        foreach ($sourcePaths as $sourcePath) {
            if (is_dir($sourcePath)) {
                $this->dirs[] = $sourcePath;
            } elseif (is_file($sourcePath)) {
                $this->files[] = $sourcePath;
            }
        }
    }

    /**
     * @return false|PhotoBunch
     */
    public function searchForNextFile()
    {
        if (!count($this->files) && count($this->dirs) > 0) {
            while (!count($this->files) && count($this->dirs) > 0) {
                $this->scanDirectory(array_shift($this->dirs));
            }
        }
        if (count($this->files) > 0) {
            return array_shift($this->files);
        } else {
            return false;
        }
    }

    private function scanDirectory($dir)
    {
        $files = array();
        $dirs = array();
        foreach (new \DirectoryIterator($dir) as $path) {
            /** @var \DirectoryIterator $path */
            if ($path->isDot()) {
                continue;
            } elseif ($path->isDir()) {
                $dirs[] = $path->getPathname();
            } elseif ($path->isFile()) {
                $basename = $path->getBasename('.' . $path->getExtension());
                if ('' === $basename) {
                    // skip hidden files
                    continue;
                }
                $bunchId = $path->getPath() . DIRECTORY_SEPARATOR . $basename;
                if (!isset($files[$bunchId])) {
                    $files[$bunchId] = array();
                }
                $files[$bunchId][] = $path->getExtension();
            }
        }
        while (count($dirs) > 0) {
            array_unshift($this->dirs, array_pop($dirs));
        }
        foreach ($files as $bunchId => $extensions) {
            if (count(array_intersect($extensions, self::supportedExtensions())) > 0) {
                $this->files[] = new PhotoBunch($bunchId, $extensions);
            }
        }
    }

    public static function supportedExtensions()
    {
        return array(
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

            'dng', // Adobe & Leica & Pentax

            'jpg',
            'jpeg',
            'tiff',
            'tif',
        );
    }
}
