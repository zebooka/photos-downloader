<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

class Scanner implements \IteratorAggregate, \Countable
{
    /** @var iterable */
    private $sourcePaths;

    /** @var FileBunch[] */
    private $files = [];

    /** @var bool */
    private $recursive;

    /** @var bool */
    private $scanInstantly;

    public function __construct(iterable $sourcePaths, bool $recursive = true, bool $scanInstantly = false)
    {
        $this->sourcePaths = $sourcePaths;
        $this->recursive = $recursive;
        $this->scanInstantly = $scanInstantly;

        if ($this->scanInstantly) {
            foreach ($this->getFileBunchGenerator() as $fileBunch)
            {
                $this->files[] = $fileBunch;
            }
        }
    }

    public static function factoryFromInput(InputInterface $input): self
    {
        return new self(
            new \ArrayIterator(Configure::from($input)),
            !Configure::noRecursive($input),
        );
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function getIterator(): \Traversable
    {
        if ($this->scanInstantly) {
            foreach ($this->files as $fileBunch) {
                yield $fileBunch;
            }
        } else {
            foreach ($this->getFileBunchGenerator() as $fileBunch) {
                yield $fileBunch;
            }
        }
    }

    public function getFileBunchGenerator(): \Generator
    {
        $dirs = [];
        foreach ($this->sourcePaths as $sourcePath) {
            if (is_file($sourcePath)) {
                $path = new \SplFileInfo($sourcePath);
                if (!in_array(mb_strtolower($path->getExtension()), self::supportedExtensions())) {
                    continue;
                }
                $basename = $path->getBasename('.' . $path->getExtension());
                if ('' === $basename) {
                    continue;
                }
                $bunchId = $path->getPathInfo()->getRealPath() . DIRECTORY_SEPARATOR . $basename;
                yield new FileBunch($bunchId, [$path->getExtension()]);
            } elseif (is_dir($sourcePath)) {
                $dirs[] = ($this->recursive
                    ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath))
                    : new \DirectoryIterator($sourcePath)
                );
            }
        }

        foreach ($dirs as $dir) {
            $files = [];
            foreach ($dir as $spl) {
                /** @var \SplFileInfo $spl */

                if ($spl->isFile()) {
                    $basename = $spl->getBasename('.' . $spl->getExtension());
                    if ('' === $basename) {
                        // skip hidden files
                        continue;
                    }
                    $bunchId = $spl->getPath() . DIRECTORY_SEPARATOR . $basename;
                    if (!isset($files[$bunchId])) {
                        $files[$bunchId] = [];
                    }
                    $files[$bunchId][] = $spl->getExtension();
                }
            }

            foreach ($files as $bunchId => $extensions) {
                $supportedVideo = self::filterBySupportedExtensions($extensions, self::supportedVideoExtensions());
                $supportedPhoto = self::filterBySupportedExtensions($extensions, self::supportedPhotoExtensions());
                if (count($supportedVideo) > 0) {
                    yield new FileBunch($bunchId, $supportedVideo, array_diff($extensions, $supportedVideo));
                } elseif (count($supportedPhoto) > 0) {
                    yield new FileBunch($bunchId, $supportedPhoto, array_diff($extensions, $supportedPhoto));
                }
            }
        }
    }

    /**
     * @param string[] $extensions
     * @param string[] $supportedExtensions
     * @return string[]
     */
    public static function filterBySupportedExtensions(array $extensions, array $supportedExtensions): array
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

    /**
     * @param string[] $extensions
     * @return string[]
     */
    public static function sortExtensions(array $extensions): array
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

    /**
     * @deprecated
     * @return string[]
     */
    public static function supportedExtensions(): array
    {
        return array_merge(self::supportedVideoExtensions(), self::supportedPhotoExtensions());
    }

    /**
     * @deprecated
     * @return string[]
     */
    public static function supportedPhotoExtensions(): array
    {
        return ScannerFactory::supportedPhotoExtensions();
    }

    /**
     * @deprecated
     * @return string[]
     */
    public static function supportedVideoExtensions(): array
    {
        return ScannerFactory::supportedVideoExtensions();
    }
}
