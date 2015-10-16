<?php

namespace Zebooka\Utils;

use Zebooka\Utils\Executor\Command;

class Executor
{
    public function execute($cmd)
    {
        $code = 0;
        passthru($cmd instanceof Command ? $cmd->command() : $cmd, $code);
        return $code;
    }
}
