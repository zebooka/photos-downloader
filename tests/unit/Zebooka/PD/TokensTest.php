<?php

namespace Zebooka\PD;

class TokensTest extends \PHPUnit_Framework_TestCase
{
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
    }

    public function test_creation_with_different_datetime_argument()
    {
        // unix timestamp
        $timestamp = mktime(21, 0, 0, 4, 17, 2007);
        $tokens = new Tokens($timestamp);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals($timestamp, $tokens->timestamp());

        // strtotime
        $timestr = '2007-04-17 16:00:00';
        $tokens = new Tokens($timestr);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('160000', $tokens->time());
        $this->assertEquals(strtotime($timestr), $tokens->timestamp());

        // array of date
        $tokens = new Tokens(array('unique-date'));
        $this->assertEquals('unique-date', $tokens->date());
        $this->assertNull($tokens->time());
        $this->assertNull($tokens->timestamp());

        // array of date and time
        $tokens = new Tokens(array('unique-date', 'unique-time'));
        $this->assertEquals('unique-date', $tokens->date());
        $this->assertEquals('unique-time', $tokens->time());
        $this->assertNull($tokens->timestamp());

        // DateTime class
        $datetime = new \DateTime('2007-04-17 16:00:00');
        $tokens = new Tokens($datetime);
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('160000', $tokens->time());
        $this->assertEquals($datetime->getTimestamp(), $tokens->timestamp());
    }

    public function test_failure_with_unsupported_date_time_value_type()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'Date/time parameter is invalid.',
            Tokens::ERROR_NO_DATE_TIME
        );
        new Tokens(null);
    }
}
