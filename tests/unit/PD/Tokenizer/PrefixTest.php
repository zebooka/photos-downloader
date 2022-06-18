<?php

namespace Zebooka\PD\Tokenizer;

use PHPUnit\Framework\TestCase;
use Zebooka\PD\Tokenizer;

class PrefixTest extends TestCase
{
    public function test_extractPrefix_first_capital_symbol()
    {
        $tokens = array('I', 'test', 'prefix');
        $prefix = Tokenizer::extractPrefix($tokens);
        $this->assertEquals('I', $prefix);
        $this->assertEquals(array('test', 'prefix'), $tokens);
    }

    public function test_extractPrefix_first_lower_symbol()
    {
        $tokens = array('i', 'test', 'prefix');
        $prefix = Tokenizer::extractPrefix($tokens);
        $this->assertNull($prefix);
        $this->assertEquals(array('i', 'test', 'prefix'), $tokens);
    }

    public function test_extractPrefix_first_digit()
    {
        $tokens = array('1', 'test', 'prefix');
        $prefix = Tokenizer::extractPrefix($tokens);
        $this->assertNull($prefix);
        $this->assertEquals(array('1', 'test', 'prefix'), $tokens);
    }

    public function test_extractPrefix_second_capital()
    {
        $tokens = array('do', 'I', 'test', 'prefix', '?');
        $prefix = Tokenizer::extractPrefix($tokens);
        $this->assertNull($prefix);
        $this->assertEquals(array('do', 'I', 'test', 'prefix', '?'), $tokens);
    }
}
