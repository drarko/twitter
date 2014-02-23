<?php

namespace League\Twitter;

class TrendTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Trend
     */
    private $trend;

    public function setUp()
    {
        $this->trend = new Trend(array());
    }

    public function testCanCreateTrend()
    {
        $this->assertInstanceOf('League\Twitter\Trend', $this->trend);
    }

    public function testgetAndSetName()
    {
        $this->trend->setName('thephpleague');
        $this->assertEquals('thephpleague', $this->trend->getName());
    }

    public function testgetAndSetQuery()
    {
        $this->trend->setQuery('query');
        $this->assertEquals('query', $this->trend->getQuery());
    }

    public function testgetAndSetTimestamp()
    {
        $now = time();
        $this->trend->setTimestamp($now);
        $this->assertEquals($now, $this->trend->getTimestamp());
    }

    public function testgetAndSetUrl()
    {
        $this->trend->setUrl('url');
        $this->assertEquals('url', $this->trend->getUrl());
    }
}
