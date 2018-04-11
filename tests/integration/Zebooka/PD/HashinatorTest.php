<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class HashinatorTest extends TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/hashinator';
    }

    public function test_hashinator()
    {
        $hashinator = new Hashinator();
        $a = $this->resourceDirectory() . '/a';
        $b = $this->resourceDirectory() . '/b';
        $c = $this->resourceDirectory() . '/c';
        $empty = $this->resourceDirectory() . '/empty';
        $notFound = $this->resourceDirectory() . '/not-found';
        $this->assertTrue($hashinator->equal($a, $b));
        $this->assertFalse($hashinator->equal($a, $c));
        $this->assertFalse($hashinator->equal($b, $c));
        $this->assertFalse($hashinator->equal($a, $empty));
        $this->assertFalse($hashinator->equal($b, $empty));
        $this->assertFalse($hashinator->equal($c, $empty));
    }

    public function test_failure_if_file_not_found()
    {
        $this->expectExceptionObject(
            new \UnexpectedValueException(
                'One of compared file is not readable.',
                Hashinator::ERROR_FILE_NOT_READABLE
            )
        );
        $hashinator = new Hashinator();
        $hashinator->equal(
            $this->resourceDirectory() . '/empty',
            $this->resourceDirectory() . '/not-found'
        );
    }
}
