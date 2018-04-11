<?php

namespace Zebooka\PD\Tokenizer;

use PHPUnit\Framework\TestCase;
use Zebooka\PD\Tokenizer;

class CameraTest extends TestCase
{
    public function test_extractCamera()
    {
        $tokens = array('I', 'test', 'camera', 'cam123');
        $camera = Tokenizer::extractCamera($tokens, array('cam123'));
        $this->assertEquals('cam123', $camera);
        $this->assertEquals(array('I', 'test', 'camera'), $tokens);
    }

    public function test_extractCamera_prefer_tokens()
    {
        $tokens = array('I', 'test', 'camera', 'cam456');
        $camera = Tokenizer::extractCamera($tokens, array('cam123', 'cam456'), 'cam123');
        $this->assertEquals('cam456', $camera);
        $this->assertEquals(array('I', 'test', 'camera'), $tokens);
    }
}
