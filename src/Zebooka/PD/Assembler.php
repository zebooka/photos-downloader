<?php

namespace Zebooka\PD;

class Assembler
{
    private $configure;

    public function  __construct(Configure $configure)
    {
        $this->configure = $configure;
    }

    public function assemble(Tokens $tokens)
    {
        return false;
    }
}
