<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class TokenizerTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @return Configure
     */
    private function configure()
    {
        return \Mockery::mock(Configure::class)
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
    private function exifAnalyzer(FileBunch $fileBunch, $datetime, $camera, $tokens = array())
    {
        return \Mockery::mock(ExifAnalyzer::class)
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
        return \Mockery::mock(FileBunch::class)
            ->shouldReceive('basename')
            ->withNoArgs()
            ->twice()
            ->andReturn($basename)
            ->getMock();
    }

    public function test_tokenize()
    {
        $configure = $this->configure();
        $configure->tokensToAdd = array('new');
        $configure->tokensToDrop = array('old');
        $configure->tokensDropUnknown = true;
        $fileBunch = $this->fileBunch('S_BOA_hello_070417_210000,2_k100d_old_world_old_unknown');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('2', $tokens->shot);
        $this->assertEquals('BOA', $tokens->author);
        $this->assertEquals('S', $tokens->prefix);
        $this->assertEquals('k100d', $tokens->camera);
        $this->assertEquals(array('hello', 'world', 'new'), $tokens->tokens);
    }

    public function test_authors()
    {
        $configure = $this->configure();
        $fileBunch = $this->fileBunch('S_BOA_hello_070417_210000,2_k100d_old_world_old_unknown');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('BOA', $tokens->author);

        $fileBunch = $this->fileBunch('S_141101_220000_DUY_BOA_hello_5s');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('DUY', $tokens->author);
    }

    public function test_tokenize_new_shot_format()
    {
        $configure = $this->configure();
        $fileBunch = $this->fileBunch('070417_210000_2_k100d__unknown');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals(strtotime('2007-04-17 21:00:00'), $tokens->timestamp());
        $this->assertEquals('2', $tokens->shot);
        $this->assertNull($tokens->author);
        $this->assertNull($tokens->prefix);
        $this->assertEquals('k100d', $tokens->camera);
        $this->assertEquals(array('unknown'), $tokens->tokens);
    }

    public function test_tokenize_skips_empty_tokens()
    {
        $configure = $this->configure();
        $fileBunch = $this->fileBunch('070417_210000,3____5s___hello');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
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
        $fileBunch = $this->fileBunch('IMGP1234');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, strtotime('2007-04-17 21:00:00'), 'unique-camera'));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('unique-camera', $tokens->camera);
        $this->assertEquals(array('IMGP1234'), $tokens->tokens);
    }

    public function test_tokenize_dji_photo()
    {
        $fileBunch = $this->fileBunch('DJI_1234');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, strtotime('2007-04-17 21:00:00'), 'unique-camera'));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('unique-camera', $tokens->camera);
        $this->assertEquals(array('DJI-1234'), $tokens->tokens);
    }

    public function test_tokenize_lengthy_date()
    {
        $fileBunch = $this->fileBunch('2007-04-17-21.00.00');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }

    public function test_tokenize_lengthy_date_2()
    {
        $fileBunch = $this->fileBunch('PHOTO-2007-04-17-21-00-00 2');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals('2', $tokens->shot);
    }

    public function test_tokenize_lengthy_separated_date()
    {
        $fileBunch = $this->fileBunch('2007-04-17_21-00-00');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }

    public function test_tokenize_lengthy_separated_date_from_cyanogenmod()
    {
        $fileBunch = $this->fileBunch('IMG_20070417_210000');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
    }

    public function test_tokenize_unknown_date()
    {
        $fileBunch = $this->fileBunch('200YM4DD_H1M2S3');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('200YM4DD', $tokens->date());
        $this->assertEquals('H1M2S3', $tokens->time());
    }

    public function test_tokenize_sjcam_date()
    {
        $fileBunch = $this->fileBunch('2018_0203_093915_011');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('180203', $tokens->date());
        $this->assertEquals('093915', $tokens->time());
        $this->assertEquals('011', $tokens->shot);
    }

    public function test_tokenize_incorrect_date()
    {
        $this->expectExceptionObject(
            new TokenizerException(
                'Unable to detect date/time.',
                TokenizerException::NO_DATE_TIME_DETECTED
            )
        );
        $fileBunch = $this->fileBunch('0YM4DD_H1M2S3');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokenizer->tokenize($fileBunch);
    }


    public function test_tokenize_failure_with_incorrect_dates_with_placeholders()
    {
        $this->expectExceptionObject(
            new TokenizerException(
                'Unable to detect date/time.',
                TokenizerException::NO_DATE_TIME_DETECTED
            )
        );
        $fileBunch = $this->fileBunch('YYYY_123');
        $exifAnalyzer = $this->exifAnalyzer($fileBunch, null, null);
        $tokenizer = new Tokenizer($this->configure(), $exifAnalyzer);
        $tokenizer->tokenize($fileBunch);
    }

    public function test_tokenize_failure_with_incorrect_dates_with_placeholders_2()
    {
        $this->expectExceptionObject(
            new TokenizerException(
                'Unable to detect date/time.',
                TokenizerException::NO_DATE_TIME_DETECTED
            )
        );
        $fileBunch = $this->fileBunch('1508DD_123');
        $exifAnalyzer = $this->exifAnalyzer($fileBunch, null, null);
        $tokenizer = new Tokenizer($this->configure(), $exifAnalyzer);
        $tokenizer->tokenize($fileBunch);
    }

    public function test_tokenize_with_unix_epoch()
    {
        $fileBunch = $this->fileBunch('hello_world');
        $exifAnalyzer = $this->exifAnalyzer($fileBunch, 0, null);
        $tokenizer = new Tokenizer($this->configure(), $exifAnalyzer);
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals(0, $tokens->timestamp());
    }

    public function test_tokenize_repeated_tokens()
    {
        $fileBunch = $this->fileBunch('hello_hello_hello_hello_hello_hello');
        $exifAnalyzer = $this->exifAnalyzer($fileBunch, 0, null, array('world', 'world', 'world'));
        $tokenizer = new Tokenizer($this->configure(), $exifAnalyzer);
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertContains('hello', $tokens->tokens);
        $this->assertContains('world', $tokens->tokens);
        $this->assertCount(2, $tokens->tokens);
    }

    public function test_tokenize_correctly_sorts_tags()
    {
        $configure = $this->configure();
        $configure->tokensToAdd = array('new');
        $configure->tokensToDrop = array();
        $configure->tokensDropUnknown = false;
        $fileBunch = $this->fileBunch('S_BOA_hello_070417_210000,2_k100d_unknown1_old_world_old_unknown2');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals(array('hello', 'old', 'world', 'unknown1', 'unknown2', 'new'), $tokens->tokens);
    }

    public function test_tokenize_short_scanned_film_photo()
    {
        $fileBunch = $this->fileBunch('1980x_123');
        $exifAnalyzer = $this->exifAnalyzer($fileBunch, null, null);
        $tokenizer = new Tokenizer($this->configure(), $exifAnalyzer);
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('1980x', $tokens->date());
        $this->assertEquals('123', $tokens->shot);
        $this->assertEquals(array(), $tokens->tokens);
    }

    public function test_tokenize_preferExifDateTime()
    {
        $configure = $this->configure();
        $configure->preferExifDateTime = true;
        $fileBunch = $this->fileBunch('S_BOA_hello_070417_210000,2_k100d_old_world_old_unknown');
        $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, '2009-08-21 11:00:00', null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('090821', $tokens->date());
        $this->assertEquals('110000', $tokens->time());
        $this->assertEquals('2', $tokens->shot);
    }

    public function test_tokenize_skips_img_dsc_token()
    {
        foreach (array('IMG', 'DSC', 'DJI', 'ANY') as $notAuthor) {
            $configure = $this->configure();
            $fileBunch = $this->fileBunch($notAuthor . '_1234');
            $tokenizer = new Tokenizer($configure, $this->exifAnalyzer($fileBunch, '2015-11-01 22:00:00', null));
            $tokens = $tokenizer->tokenize($fileBunch);
            $this->assertInstanceOf(Tokens::class, $tokens);
            $this->assertNull($tokens->author);
            $this->assertEquals(array("{$notAuthor}-1234"), $tokens->tokens);
        }
    }

    public function test_tokenize_zero_shots()
    {
        $fileBunch = $this->fileBunch('070417_210000_0');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertSame(0, $tokens->shot);

        $fileBunch = $this->fileBunch('070417_210000_00');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertSame('00', $tokens->shot);

        $fileBunch = $this->fileBunch('070417_210000_07');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertSame('07', $tokens->shot);
        $this->assertNotSame(7, $tokens->shot);
    }

    public function test_vlc_timeshift()
    {
        $basename = 'vlc_180201_120000_BOA.mov_01_06_40_00001';
        $customtokens = explode(Tokens::SEPARATOR, $basename);
        $this->assertTrue(Tokenizer::hasVlcInTokens($customtokens));
        $this->assertCount(8, $customtokens);
        $this->assertEquals(
            ['+ 01 hours 06 minutes 40 seconds', '00001'],
            Tokenizer::detectVlcShift($customtokens)
        );
        $this->assertCount(3, $customtokens);

        $fileBunch = $this->fileBunch($basename);
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals(
            date('r', strtotime('2018-02-01 13:06:40')),
            date('r', $tokens->timestamp())
        );
    }

    public function test_tokenize_whatsapp_date()
    {
        $fileBunch = $this->fileBunch('WhatsApp Image 2018-07-25 at 09.26.50');
        $tokenizer = new Tokenizer($this->configure(), $this->exifAnalyzer($fileBunch, null, null));
        $tokens = $tokenizer->tokenize($fileBunch);
        $this->assertInstanceOf(Tokens::class, $tokens);
        $this->assertEquals('180725', $tokens->date());
        $this->assertEquals('092650', $tokens->time());
        $this->assertEquals(null, $tokens->shot);
    }
}
