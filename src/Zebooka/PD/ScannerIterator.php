<?php

namespace Zebooka\PD;

class ScannerIterator implements \Iterator
{
    private $originalSourcePaths;
    /**
     * @var Scanner
     */
    private $scanner;
    private $key;
    private $value;
    private $recursive;

    public function __construct(array $sourcePaths, $recursive)
    {
        $this->originalSourcePaths = $sourcePaths;
        $this->recursive = $recursive;
    }

    public function rewind()
    {
        $this->scanner = new Scanner($this->originalSourcePaths, $this->recursive);
        $this->key = 0;
        $this->value = $this->scanner->searchForNextFile();
    }

    public function valid()
    {
        return (false !== $this->value);
    }

    /**
     * @return false|FileBunch
     */
    public function current()
    {
        return $this->value;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->key++;
        $this->value = $this->scanner->searchForNextFile();
    }
}
