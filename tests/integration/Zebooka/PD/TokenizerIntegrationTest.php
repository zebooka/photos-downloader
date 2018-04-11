<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class TokenizerIntegrationTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    private function realConfigure()
    {
        return new \Zebooka\PD\Configure(
            array(),
            json_decode(file_get_contents(__DIR__ . '/../../../../res/tokens.json'), true)
        );
    }

    /**
     * @return ExifAnalyzer
     */
    private function exifAnalyzer(FileBunch $fileBunch, $datetime, $camera, $tokens = array())
    {
        return \Mockery::mock('\\Zebooka\\PD\\ExifAnalyzer')
            ->shouldReceive('extractDateTimeCameraTokens')
            ->with($fileBunch)
            ->once()
            ->andReturn(array($datetime, $camera, $tokens))
            ->getMock();
    }

    /**
     * @return FileBunch
     */
    private function fileBunch($basename)
    {
        return \Mockery::mock('\\Zebooka\\PD\\FileBunch')
            ->shouldReceive('basename')
            ->withNoArgs()
            ->once()
            ->andReturn($basename)
            ->getMock();
    }

    public function test_vsco_c1()
    {
        $configure = $this->realConfigure();
        $configure->tokensDropUnknown = true;

        // vsco c1 from basename
        $fileBunch = $this->fileBunch('2015_1_test_vsco_c1');
        $tokenizer = new Tokenizer(
            $configure,
            $this->exifAnalyzer($fileBunch, null, null, array())
        );
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals(array('vsco', 'c1'), $tokens->tokens);

        // c1 in exif and vcso c1 in basename
        $fileBunch = $this->fileBunch('2015_2_test_vsco_c1');
        $tokenizer = new Tokenizer(
            $configure,
            $this->exifAnalyzer($fileBunch, null, null, array('c1'))
        );
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals(array('vsco', 'c1'), $tokens->tokens);

        // vsco c1 in exif and c1 in basename
        $fileBunch = $this->fileBunch('2015_3_test_c1');
        $tokenizer = new Tokenizer(
            $configure,
            $this->exifAnalyzer($fileBunch, null, null, array('vsco', 'c1'))
        );
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals(array('c1', 'vsco'), $tokens->tokens);

        // vsco c1 in exif and vcso c1 in basename
        $fileBunch = $this->fileBunch('2015_4_test_vsco_c1');
        $tokenizer = new Tokenizer(
            $configure,
            $this->exifAnalyzer($fileBunch, null, null, array('vsco', 'c1'))
        );
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf('\\Zebooka\\PD\\Tokens', $tokens);
        $this->assertEquals(array('vsco', 'c1'), $tokens->tokens);
    }
}
