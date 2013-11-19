<?php

namespace League\Twitter;

class DirectMessageTest extends \PHPUnit_Framework_TestCase
{
    private $direct_message;

    public function setUp()
    {
        $this->direct_message = new DirectMessage(array());
    }

    public function testCanCreateDirectMessage()
    {
        $this->assertInstanceOf('League\Twitter\DirectMessage', $this->direct_message);
    }
}
