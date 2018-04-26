<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class TokensTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @return Configure
     */
    private function configure($subDirectoriesFormat = null)
    {
        $configure = \Mockery::mock(Configure::class);
        if ($subDirectoriesFormat) {
            $configure->subDirectoriesFormat = $subDirectoriesFormat;
        }
        return $configure;
    }

    public function test_creation_with_all_arguments()
    {
        $timestamp = mktime(21, 0, 0, 4, 17, 2007);
        $tokens = new Tokens(
            $timestamp,
            array('unique-token-1', 'unique-token-2'),
            'unique-author',
            'unique-camera',
            'unique-prefix',
            123
        );
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals($timestamp, $tokens->timestamp());
        $this->assertEquals(array('unique-token-1', 'unique-token-2'), $tokens->tokens);
        $this->assertEquals('unique-author', $tokens->author);
        $this->assertEquals('unique-camera', $tokens->camera);
        $this->assertEquals('unique-prefix', $tokens->prefix);
        $this->assertEquals(123, $tokens->shot);
        $tokens->increaseShot();
        $this->assertEquals(124, $tokens->shot);
        $this->assertEquals('2007/04', $tokens->assembleDirectory($this->configure()));
        $this->assertEquals('2007/070400', $tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('unique-prefix_070417_210000_124_unique-author_unique-camera_unique-token-1_unique-token-2', $tokens->assembleBasename());
    }

    public function test_creation_with_less_arguments()
    {
        $timestamp = mktime(21, 0, 0, 4, 17, 2007);
        $tokens = new Tokens($timestamp);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals($timestamp, $tokens->timestamp());
        $this->assertEquals(array(), $tokens->tokens);
        $this->assertNull($tokens->author);
        $this->assertNull($tokens->camera);
        $this->assertNull($tokens->prefix);
        $this->assertNull($tokens->shot);
        $this->assertEquals('2007/04', $tokens->assembleDirectory($this->configure()));
        $this->assertEquals('2007/070400', $tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('070417_210000', $tokens->assembleBasename());
    }

    public function test_creation_with_different_datetime_argument()
    {
        // unix timestamp
        $timestamp = mktime(21, 0, 0, 4, 17, 2007);
        $tokens = new Tokens($timestamp);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals($timestamp, $tokens->timestamp());
        $this->assertEquals('2007/04', $tokens->assembleDirectory($this->configure()));
        $this->assertEquals('2007/070400', $tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('070417_210000', $tokens->assembleBasename());

        // strtotime
        $timestr = '2007-04-17 16:00:00';
        $tokens = new Tokens($timestr);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('160000', $tokens->time());
        $this->assertEquals(strtotime($timestr), $tokens->timestamp());
        $this->assertEquals('2007/04', $tokens->assembleDirectory($this->configure()));
        $this->assertEquals('2007/070400', $tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('070417_160000', $tokens->assembleBasename());

        // array of date
        $tokens = new Tokens(array('unique-date'));
        $this->assertEquals('unique-date', $tokens->date());
        $this->assertNull($tokens->time());
        $this->assertNull($tokens->timestamp());
        $this->assertNull($tokens->assembleDirectory($this->configure()));
        $this->assertNull($tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('unique-date', $tokens->assembleBasename());

        // array of date and time
        $tokens = new Tokens(array('unique-date', 'unique-time'));
        $this->assertEquals('unique-date', $tokens->date());
        $this->assertEquals('unique-time', $tokens->time());
        $this->assertNull($tokens->timestamp());
        $this->assertNull($tokens->assembleDirectory($this->configure()));
        $this->assertNull($tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('unique-date_unique-time', $tokens->assembleBasename());

        // DateTime class
        $datetime = new \DateTime('2007-04-17 16:00:00');
        $tokens = new Tokens($datetime);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('160000', $tokens->time());
        $this->assertEquals($datetime->getTimestamp(), $tokens->timestamp());
        $this->assertEquals('2007/04', $tokens->assembleDirectory($this->configure()));
        $this->assertEquals('2007/070400', $tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('070417_160000', $tokens->assembleBasename());

        // scanned film photo with date and shot
        $tokens = new Tokens(array('1985x'), array('test'), null, null, null, 123);
        $this->assertEquals('1985x', $tokens->date());
        $this->assertNull($tokens->time());
        $this->assertNull($tokens->timestamp());
        $this->assertEquals('1985x', $tokens->assembleDirectory($this->configure()));
        $this->assertEquals('1985x', $tokens->assembleDirectory($this->configure('%Y/%y%m00')));
        $this->assertEquals('1985x_123_test', $tokens->assembleBasename());
    }

    public function test_failure_with_unsupported_date_time_value_type()
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException(
                'Date/time parameter is invalid.',
                Tokens::ERROR_NO_DATE_TIME
            )
        );
        new Tokens(null);
    }
}
