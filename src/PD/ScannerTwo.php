<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

class ScannerTwo implements \IteratorAggregate, \Countable
{
    /** @var \Iterator */
    private $iterator;

    /** @var InputInterface */
    private $input;

    /** @var bool */
    private $isRecursive;

    /** @var array<string> */
    private $froms;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
        $this->froms = from($input);
        $this->isRecursive = isRecursive($input);

        if (isFromStdin($input) || isPipelineForced($input)) {
            $this->iterator = $this->generator();
        } else {
            $this->iterator = new \ArrayIterator(iterator_to_array($this->generator()));
        }
    }

    public function getIterator(): \Iterator
    {
        if (isFromStdin($this->input) || isPipelineForced($this->input)) {
            yield from $this->iterator;
        } else {
            return $this->iterator;
        }
    }

    public function count(): int
    {
        if ($this->iterator instanceof \Countable) {
            return count($this->iterator);
        }

        throw new \LogicException('Unable to count dynamic generator.');
    }

    public function generator(): \Generator
    {
        while (count($this->froms)) {
            $from = array_pop($this->froms);
            if (is_file($from)) {
                $spl = new \SplFileInfo($from);
                if (!in_array(mb_strtolower($spl->getExtension()), supportedExtensions())) {
                    continue;
                }
                $basename = $spl->getBasename('.' . $spl->getExtension());
                if ('' === $basename) {
                    continue;
                }
                $bunchId = $spl->getPathInfo()->getRealPath() . DIRECTORY_SEPARATOR . $basename;
                yield new FileBunch($bunchId, [$spl->getExtension()]);
            } elseif (is_dir($from)) {
                yield from $this->getFileBunchesForDir($from);
            }
        }
    }

    private function getFileBunchesForDir(string $dir): \Generator
    {
        $files = [];
        foreach (new \DirectoryIterator($dir) as $spl) {
            /** @var \DirectoryIterator $spl */

            if ($spl->isFile()) {
                $basename = $spl->getBasename('.' . $spl->getExtension());
                if ('' === $basename) {
                    continue;
                }
                $bunchId = $spl->getPath() . DIRECTORY_SEPARATOR . $basename;
                if (!isset($files[$bunchId])) {
                    $files[$bunchId] = [];
                }
                $files[$bunchId][] = $spl->getExtension();
            } elseif ($spl->isDir() && !$spl->isDot() && $this->isRecursive) {
                array_push($this->froms, $spl->getPath());
            }
        }

        foreach ($files as $bunchId => $extensions) {
            $videoExts = Scanner::filterBySupportedExtensions($extensions, supportedVideoExtensions());
            $photoExts = Scanner::filterBySupportedExtensions($extensions, supportedPhotoExtensions());
            // video files have priority over photo, as if they exist together, then photo is a poster for video
            if (count($videoExts) > 0) {
                yield new FileBunch($bunchId, $videoExts, array_diff($extensions, $videoExts));
            } elseif (count($photoExts) > 0) {
                yield new FileBunch($bunchId, $photoExts, array_diff($extensions, $photoExts));
            }
        }
    }
}
