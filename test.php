<?php

class A implements \IteratorAggregate
{
    public function __construct(public array $a)
    {
    }
}
