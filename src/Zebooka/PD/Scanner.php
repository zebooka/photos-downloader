<?php

namespace Zebooka\PD;

class Scanner
{
    private $files = array();
    private $dirs = array();
    private $stdin;
    private $recursive;

    public function __construct(array $sourcePaths, $recursive)
    {
        $this->recursive = $recursive;
        $sourcePaths = array_unique($sourcePaths);
        foreach ($sourcePaths as $sourcePath) {
            if (Configure::PATHS_FROM_STDIN === $sourcePath) {
                $this->stdin = fopen('php://stdin', 'r');
            } elseif (is_dir($sourcePath)) {
                $this->dirs[] = $sourcePath;
            } elseif (is_file($sourcePath)) {
                $this->addPathAsPhotoBunch($sourcePath);
            }
        }
    }

    public function __destruct()
    {
        if ($this->stdin) {
            fclose($this->stdin);
        }
    }

    private function addPathAsPhotoBunch($sourcePath)
    {
        $path = new \SplFileInfo($sourcePath);
        if (!in_array($path->getExtension(), self::supportedExtensions())) {
            return;
        }
        $basename = $path->getBasename('.' . $path->getExtension());
        if ('' === $basename) {
            return;
        }
        $bunchId = $path->getPath() . DIRECTORY_SEPARATOR . $basename;
        $this->files[] = new PhotoBunch($bunchId, array($path->getExtension()));
    }

    /**
     * @return false|PhotoBunch
     */
    public function searchForNextFile()
    {
        if ($this->stdin && !feof($this->stdin)) {
            while (!count($this->files) && !count($this->dirs) && !feof($this->stdin)) {
                $sourcePath = rtrim(fgets($this->stdin));
                if (is_dir($sourcePath)) {
                    $this->dirs[] = $sourcePath;
                } elseif (is_file($sourcePath)) {
                    $this->addPathAsPhotoBunch($sourcePath);
                }
            }
        }
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
            } elseif ($path->isDir() && $this->recursive) {
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
