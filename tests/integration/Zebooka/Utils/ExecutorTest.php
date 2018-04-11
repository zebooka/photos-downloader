<?php

namespace Zebooka\Utils;

use PHPUnit\Framework\TestCase;

class ExecutorTest extends TestCase
{
    public function test_execute_ok()
    {
        $cmd = 'echo 123 >/dev/null 2>&1';
        $executor = new Executor();
        $code = $executor->execute($cmd);
        $this->assertEquals(0, $code);
    }

    public function test_execute_failure()
    {
        $cmd = 'not-existing-command >/dev/null 2>&1';
        $executor = new Executor();
        $code = $executor->execute($cmd);
        $this->assertEquals(127, $code);
    }
}
