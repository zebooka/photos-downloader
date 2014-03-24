<?php

namespace Zebooka\PD;

class TokenizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Configure
     */
    private function configure()
    {
        return \Mockery::mock('\\Zebooka\\PD\\Configure')
            ->shouldReceive('knownAuthors')
            ->withNoArgs()
            ->andReturn(array('BOA', 'CHV'))
            ->getMock()
            ->shouldReceive('knownCameras')
            ->withNoArgs()
            ->andReturn(array('k100d', 'lx5', '5s'))
            ->getMock();
    }

    /**
     * @return PhotoBunch
     */
    private function photoBunch($basename)
    {
        return \Mockery::mock('\\Zebooka\\PD\\PhotoBunch')
            ->shouldReceive('basename')
            ->withNoArgs()
            ->once()
            ->andReturn($basename)
            ->getMock();
    }

    public function test_tokenize()
    {
        $tokenizer = new Tokenizer($this->configure());
        $tokens = $tokenizer->tokenize($this->photoBunch('S_BOA_hello_070417_210000,2_k100d_world'));
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('2', $tokens->shot);
        $this->assertEquals('BOA', $tokens->author);
        $this->assertEquals('S', $tokens->prefix);
        $this->assertEquals('k100d', $tokens->camera);
        $this->assertEquals(array('hello', 'world'), $tokens->tokens);
    }

    public function test_tokenize_dcim_photo()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'Date/time parameter is invalid.',
            Tokens::ERROR_NO_DATE_TIME
        ); // TODO: for now this is throwing, because there is no ExifAnalyzer.
        $tokenizer = new Tokenizer($this->configure());
        $tokens = $tokenizer->tokenize($this->photoBunch('IMGP1234'));
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals(array('IMGP1234'), $tokens->tokens);
    }

    public function test_tokenize_lengthy_date()
    {
        $tokenizer = new Tokenizer($this->configure());
        $tokens = $tokenizer->tokenize($this->photoBunch('2007-04-17-21.00.00'));
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }

    public function test_tokenize_lengthy_separated_date()
    {
        $tokenizer = new Tokenizer($this->configure());
        $tokens = $tokenizer->tokenize($this->photoBunch('2007-04-17_21-00-00'));
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }
}
