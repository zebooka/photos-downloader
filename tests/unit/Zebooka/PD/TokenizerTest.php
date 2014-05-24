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
            ->getMock()
            ->shouldReceive('knownTokens')
            ->withNoArgs()
            ->andReturn(array('hello', 'old', 'world'))
            ->getMock();
    }

    /**
     * @return ExifAnalyzer
     */
    private function exifAnalyzer(PhotoBunch $photoBunch, $datetime, $camera, $tokens = array())
    {
        return \Mockery::mock('\\Zebooka\\PD\\ExifAnalyzer')
            ->shouldReceive('extractDateTimeCameraTokens')
            ->with($photoBunch)
            ->once()
            ->andReturn(array($datetime, $camera, $tokens))
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
        $configure = $this->configure();
        $configure->tokensToAdd = array('new');
        $configure->tokensToDrop = array('old');
        $configure->tokensDropUnknown = true;
        $photoBunch = $this->photoBunch('S_BOA_hello_070417_210000,2_k100d_old_world_old_unknown');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($photoBunch, null, null));
        $tokens = $tokenizer->tokenize($photoBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('2', $tokens->shot);
        $this->assertEquals('BOA', $tokens->author);
        $this->assertEquals('S', $tokens->prefix);
        $this->assertEquals('k100d', $tokens->camera);
        $this->assertEquals(array('hello', 'world', 'new'), $tokens->tokens);
    }

    public function test_tokenize_skips_empty_tokens()
    {
        $configure = $this->configure();
        $photoBunch = $this->photoBunch('070417_210000,3____5s___hello');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($photoBunch, null, null));
        $tokens = $tokenizer->tokenize($photoBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('3', $tokens->shot);
        $this->assertNull($tokens->author);
        $this->assertNull($tokens->prefix);
        $this->assertEquals('5s', $tokens->camera);
        $this->assertEquals(array('hello'), $tokens->tokens);
    }

    public function test_tokenize_dcim_photo()
    {
        $photoBunch = $this->photoBunch('IMGP1234');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($photoBunch, strtotime('2007-04-17 21:00:00'), 'unique-camera'));
        $tokens = $tokenizer->tokenize($photoBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('unique-camera', $tokens->camera);
        $this->assertEquals(array('IMGP1234'), $tokens->tokens);
    }

    public function test_tokenize_lengthy_date()
    {
        $photoBunch = $this->photoBunch('2007-04-17-21.00.00');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($photoBunch, null, null));
        $tokens = $tokenizer->tokenize($photoBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }

    public function test_tokenize_lengthy_separated_date()
    {
        $photoBunch = $this->photoBunch('2007-04-17_21-00-00');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($photoBunch, null, null));
        $tokens = $tokenizer->tokenize($photoBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }

    public function test_tokenize_unknown_date()
    {
        $photoBunch = $this->photoBunch('200YM4DD_H1M2S3');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($photoBunch, null, null));
        $tokens = $tokenizer->tokenize($photoBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals('200YM4DD', $tokens->date());
        $this->assertEquals('H1M2S3', $tokens->time());
    }

    public function test_tokenize_incorrect_date()
    {
        $this->setExpectedException(
            '\\Zebooka\\PD\\TokenizerException',
            'Unable to detect date/time.',
            TokenizerException::NO_DATE_TIME_DETECTED
        );
        $photoBunch = $this->photoBunch('0YM4DD_H1M2S3');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($photoBunch, null, null));
        $tokenizer->tokenize($photoBunch);
    }

}
