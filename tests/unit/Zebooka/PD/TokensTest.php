<?php

namespace Zebooka\PD;

class TokensTest extends \PHPUnit_Framework_TestCase
{
    public function test_creation_with_all_arguments()
    {
        $tokens = new Tokens(
            1234567890,
            array('unique-token-1', 'unique-token-2'),
            'unique-author',
            'unique-camera',
            'unique-prefix',
            123
        );
        $this->assertEquals(1234567890, $tokens->timestamp);
        $this->assertEquals(null, $tokens->date);
        $this->assertEquals(null, $tokens->time);
        $this->assertEquals(array('unique-token-1', 'unique-token-2'), $tokens->tokens);
        $this->assertEquals('unique-author', $tokens->author);
        $this->assertEquals('unique-camera', $tokens->camera);
        $this->assertEquals('unique-prefix', $tokens->prefix);
        $this->assertEquals(123, $tokens->shot);
        // pssst: we can not test here exact values, because they differ for different configured timezones
        $this->assertRegExp('/^[0-9]{6}$/', $tokens->date());
        $this->assertRegExp('/^[0-9]{6}$/', $tokens->time());
    }

    public function test_creation_with_less_arguments()
    {
        $tokens = new Tokens(1234567890);

        $this->assertEquals(1234567890, $tokens->timestamp);
        $this->assertNull($tokens->date);
        $this->assertNull($tokens->time);
        $this->assertEquals(array(), $tokens->tokens);
        $this->assertNull($tokens->author);
        $this->assertNull($tokens->camera);
        $this->assertNull($tokens->prefix);
        $this->assertNull($tokens->shot);
        $this->assertRegExp('/^[0-9]{6}$/', $tokens->date());
        $this->assertRegExp('/^[0-9]{6}$/', $tokens->time());
    }

    public function test_creation_with_different_datetime_argument()
    {
        // unix timestamp
        $tokens = new Tokens(1234567890);
        $this->assertEquals(1234567890, $tokens->timestamp);
        $this->assertNull($tokens->date);
        $this->assertNull($tokens->time);

        // strtotime
        $tokens = new Tokens('2007-04-17 16:00:00 +0700');
        $this->assertEquals(1176800400, $tokens->timestamp);
        $this->assertNull($tokens->date);
        $this->assertNull($tokens->time);

        // array of date
        $tokens = new Tokens(array('unique-date'));
        $this->assertNull($tokens->timestamp);
        $this->assertEquals('unique-date', $tokens->date);
        $this->assertNull($tokens->time);

        // array of date and time
        $tokens = new Tokens(array('unique-date', 'unique-time'));
        $this->assertNull($tokens->timestamp);
        $this->assertEquals('unique-date', $tokens->date);
        $this->assertEquals('unique-time', $tokens->time);

        // DateTime class
        $tokens = new Tokens(new \DateTime('2007-04-17 16:00:00 +0100'));
        $this->assertEquals(1176822000, $tokens->timestamp);
        $this->assertNull($tokens->date);
        $this->assertNull($tokens->time);
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
