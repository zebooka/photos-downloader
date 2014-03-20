<?php

namespace Zebooka\PD;

class Tokenizer
{
    private $configure;

    public function  __construct(Configure $configure)
    {
        $this->configure = $configure;
    }

    public function tokenize(PhotoBunch $photoBunch)
    {
        return new Tokens();
    }
}
