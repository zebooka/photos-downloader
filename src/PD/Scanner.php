<?php

namespace Zebooka\PD;

class Scanner
{
    private $files = array();
    private $dirs = array();
    private $listHandler;
    private $recursive;

    public function __construct(array $sourcePaths, $recursive, $listFile = null)
    {
        $this->recursive = $recursive;
        $sourcePaths = array_unique($sourcePaths);
        foreach ($sourcePaths as $sourcePath) {
            if (Configure::PATHS_FROM_STDIN === $sourcePath) {
                $this->listHandler = fopen($listFile ? : 'php://stdin', 'r');
            } elseif (is_dir($sourcePath)) {
                $this->dirs[] = realpath($sourcePath);
            } elseif (is_file($sourcePath)) {
                $this->addPathAsFileBunch(realpath($sourcePath));
            }
        }
    }

    public function __destruct()
    {
        if ($this->listHandler) {
            fclose($this->listHandler);
        }
    }

    private function addPathAsFileBunch($sourcePath)
    {
        $path = new \SplFileInfo($sourcePath);
        if (!in_array(mb_strtolower($path->getExtension()), self::supportedExtensions())) {
            return;
        }
        $basename = $path->getBasename('.' . $path->getExtension());
        if ('' === $basename) {
            return;
        }
        $bunchId = $path->getPathInfo()->getRealPath() . DIRECTORY_SEPARATOR . $basename;
        $this->files[] = new FileBunch($bunchId, array($path->getExtension()));
    }

    /**
     * @return false|FileBunch
     */
    public function searchForNextFile()
    {
        if ($this->listHandler && !feof($this->listHandler)) {
            while (!count($this->files) && !count($this->dirs) && !feof($this->listHandler)) {
                $sourcePath = rtrim(fgets($this->listHandler));
                if (is_dir($sourcePath)) {
                    $this->dirs[] = $sourcePath;
                } elseif (is_file($sourcePath)) {
                    $this->addPathAsFileBunch($sourcePath);
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
            $supportedVideo = self::filterBySupportedExtensions($extensions, self::supportedVideoExtensions());
            $supportedPhoto = self::filterBySupportedExtensions($extensions, self::supportedPhotoExtensions());
            if (count($supportedVideo) > 0) {
                $this->files[] = new FileBunch($bunchId, $supportedVideo, array_diff($extensions, $supportedVideo));
            } elseif (count($supportedPhoto) > 0) {
                $this->files[] = new FileBunch($bunchId, $supportedPhoto, array_diff($extensions, $supportedPhoto));
            }
        }
    }

    public static function filterBySupportedExtensions(array $extensions, array $supportedExtensions)
    {
        $supportedExtensions = array_map(
            function ($extension) {
                return preg_quote($extension, '/');
            },
            $supportedExtensions
        );
        return array_filter(
            $extensions,
            function ($extension) use ($supportedExtensions) {
                return preg_match('/^(' . implode('|', $supportedExtensions) . ')$/i', $extension);
            }
        );
    }

    public static function sortExtensions(array $extensions)
    {
        $sorted = $tail = array();
        $ranks = array_flip(self::supportedExtensions());
        foreach ($extensions as $extension) {
            $lowerExtension = mb_strtolower($extension);
            if (isset($ranks[$lowerExtension])) {
                $sorted[$ranks[$lowerExtension]] = $extension;
            } else {
                $tail[] = $extension;
            }
        }
        ksort($sorted);
        return array_values(array_merge($sorted, $tail));
    }

    public static function supportedExtensions()
    {
        return array_merge(self::supportedVideoExtensions(), self::supportedPhotoExtensions());
    }

    public static function supportedPhotoExtensions()
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
            'png',
        );
    }

    public static function supportedVideoExtensions()
    {
        return array(
            'mkv',
            'mov',
            'avi',
            'mpg',
            'mp4',
            'mts',
            '3gp',
        );
    }
}
