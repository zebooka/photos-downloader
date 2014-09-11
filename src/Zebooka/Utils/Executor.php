<?php

namespace Zebooka\Utils;

class Executor
{
    public function execute($cmd)
    {
        $code = 0;
        passthru($cmd, $code);
        return $code;
    }
}
