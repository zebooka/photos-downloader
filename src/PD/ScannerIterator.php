<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

class ScannerIterator implements ScannerInterface, \Iterator, \Countable
{
    /** @var InputInterface */
    private $input;

    /** @var FileBunch[] */
    private $bunches;

    /** @var int */
    private $key;

    /** @var FileBunch */
    private $value;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->bunches = iterator_to_array(new ScannerTwo($this->input));
        $this->key = 0;
        $this->value = reset($this->bunches);
    }

    #[\ReturnTypeWillChange]
    public function valid(): bool
    {
        return (false !== $this->value);
    }

    /**
     * @return false|FileBunch
     */
    #[\ReturnTypeWillChange]
    public function current(): FileBunch
    {
        return $this->value;
    }

    #[\ReturnTypeWillChange]
    public function key(): int
    {
        return $this->key;
    }

    #[\ReturnTypeWillChange]
    public function next(): void
    {
        $this->key++;
        $this->value = next($this->bunches);
    }

    public function count(): int
    {
        return count($this->bunches);
    }
}
