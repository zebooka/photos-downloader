<?php

namespace Zebooka\Utils;

class ExecutorTest extends \PHPUnit_Framework_TestCase
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
