<?php

namespace Zebooka\PD;

class Executor
{
    public function execute($cmd)
    {
        $code = 0;
        passthru($cmd, $code);
        return $code;
    }
}
