<?php

namespace Zebooka\PD;

class Executor
{
    public function execute($cmd)
    {
        $code = 0;
        passthru($code, $code);
        return $code;
    }
}
