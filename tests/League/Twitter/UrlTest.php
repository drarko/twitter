<?php

namespace League\Twitter;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Url
     */
    private $url;

    public function setUp()
    {
        $this->url = new Url(array());
    }

    public function testCanCreateUrl()
    {
        $this->assertInstanceOf('League\Twitter\Url', $this->url);
    }

    public function testgetAndSetExpandedUrl()
    {
        $this->url->setExpandedUrl('http://test-expanded-url.com');
        $this->assertEquals('http://test-expanded-url.com', $this->url->getExpandedUrl());
    }

    public function testgetAndSetUrl()
    {
        $this->url->setUrl('http://test-url.com');
        $this->assertEquals('http://test-url.com', $this->url->getUrl());
    }
}
