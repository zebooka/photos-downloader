<?php

namespace Zebooka\PD\Tokenizer;

use Zebooka\PD\Tokenizer;

class AuthorTest extends \PHPUnit_Framework_TestCase
{
    public function test_extractAuthor_long()
    {
        $tokens = array('I', 'test', 'AUTHOR', 'author');
        $author = Tokenizer::extractAuthor($tokens, array('AUTHOR'));
        $this->assertEquals('AUTHOR', $author);
        $this->assertEquals(array('I', 'test', 'author'), $tokens);
    }

    public function test_extractAuthor_three_capital_symbols()
    {
        $tokens = array('I', 'did', 'test', 'ABC', 'author');
        $author = Tokenizer::extractAuthor($tokens, array('AUTHOR'));
        $this->assertEquals('ABC', $author);
        $this->assertEquals(array('I', 'did', 'test', 'author'), $tokens);
    }

    public function test_extractAuthor_no_IMG_DSC()
    {
        $tokens = array('No', 'author', 'like', 'IMG', 'or', 'DSC');
        $author = Tokenizer::extractAuthor($tokens, array('ABC'));
        $this->assertNull($author);
        $this->assertEquals(array('No', 'author', 'like', 'IMG', 'or', 'DSC'), $tokens);
    }
}
