<?php

namespace Zebooka\PD\Tokenizer\DateTime;

use PHPUnit\Framework\TestCase;
use Zebooka\PD\Tokenizer;

class DetectClassicDateTimeTest extends TestCase
{
    public function test_detect_returns_null_on_single_incorrect_integer_token()
    {
        $tokens = $originalTokens = ['009981'];
        $result = Tokenizer::detectClassicDateTime(reset($tokens), 0, $tokens);
        $this->assertNull($result);
        $this->assertEquals($originalTokens, $tokens);
    }

    public function test_detect_returns_null_on_single_incorrect_long_integer_token()
    {
        $tokens = $originalTokens = ['22009981'];
        $result = Tokenizer::detectClassicDateTime(reset($tokens), 0, $tokens);
        $this->assertNull($result);
        $this->assertEquals($originalTokens, $tokens);
    }

    public function test_detect_returns_null_on_single_YYMMDD_integer_token()
    {
        $tokens = $originalTokens = ['201104'];
        $result = Tokenizer::detectClassicDateTime(reset($tokens), 0, $tokens);
        $this->assertNull($result);
        $this->assertEquals($originalTokens, $tokens);
    }

    public function test_detect_returns_date_and_shot()
    {
        $tokens = $originalTokens = ['201104', '12345678'];
        $result = Tokenizer::detectClassicDateTime(reset($tokens), 0, $tokens);
        $this->assertEquals([['201104'], '12345678'], $result);
        $this->assertEquals([], $tokens);
    }
}
