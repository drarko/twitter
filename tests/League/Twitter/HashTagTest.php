<?php

namespace League\Twitter;

class HashTagTest extends \PHPUnit_Framework_TestCase
{
    private $hashTag;

    public function setUp()
    {
        $this->hashTag = new HashTag('#twitter');
    }

    public function testCanCreateHashTag()
    {
        $this->assertInstanceOf('League\Twitter\HashTag', $this->hashTag);
    }
}
