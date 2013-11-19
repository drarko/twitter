<?php

namespace League\Twitter;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    private $url;

    public function setUp()
    {
        $this->url = new Url('http://www.twitter.com', 'http://www.twitter.com/short');
    }

    public function testCanCreateUrl()
    {
        $this->assertInstanceOf('League\Twitter\Url', $this->url);
    }
}
