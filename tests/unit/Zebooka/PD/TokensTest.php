<?php

namespace Zebooka\PD;

class TokensTest extends \PHPUnit_Framework_TestCase
{
    public function test_creation_with_all_arguments()
    {
        $tokens = new Tokens(
            mktime(21, 0, 0, 4, 17, 2007),
            array('unique-token-1', 'unique-token-2'),
            'unique-author',
            'unique-camera',
            'unique-prefix',
            123
        );
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
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
        $tokens = new Tokens(mktime(21, 0, 0, 4, 17, 2007));
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());
        $this->assertEquals(array(), $tokens->tokens);
        $this->assertNull($tokens->author);
        $this->assertNull($tokens->camera);
        $this->assertNull($tokens->prefix);
        $this->assertNull($tokens->shot);
    }

    public function test_creation_with_different_datetime_argument()
    {
        // unix timestamp
        $tokens = new Tokens(mktime(21, 0, 0, 4, 17, 2007));
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('210000', $tokens->time());

        // strtotime
        $tokens = new Tokens('2007-04-17 16:00:00');
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('160000', $tokens->time());

        // array of date
        $tokens = new Tokens(array('unique-date'));
        $this->assertEquals('unique-date', $tokens->date());
        $this->assertNull($tokens->time());

        // array of date and time
        $tokens = new Tokens(array('unique-date', 'unique-time'));
        $this->assertEquals('unique-date', $tokens->date());
        $this->assertEquals('unique-time', $tokens->time());

        // DateTime class
        $tokens = new Tokens(new \DateTime('2007-04-17 16:00:00'));
        $this->assertEquals('070417', $tokens->date());
        $this->assertEquals('160000', $tokens->time());
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
