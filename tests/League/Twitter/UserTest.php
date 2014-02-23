<?php

namespace League\Twitter;

class UserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        $this->user = new User(array());
    }

    public function testCanCreateUser()
    {
        $this->assertInstanceOf('League\Twitter\User', $this->user);
    }
}
