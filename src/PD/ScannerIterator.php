<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

/** @deprecated */
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

    public function __construct(InputInterface $input, array $sourcePaths, $recursive)
    {

        $this->originalSourcePaths = Configure::from($input);
        $this->recursive = Configure::recursive($input);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->scanner = new Scanner($this->originalSourcePaths, $this->recursive);
        $this->key = 0;
        $this->value = $this->scanner->searchForNextFile();
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return (false !== $this->value);
    }

    /**
     * @return false|FileBunch
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->value;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->key++;
        $this->value = $this->scanner->searchForNextFile();
    }

    public function getScanner(): Scanner
    {
        return $this->scanner;
    }
}
