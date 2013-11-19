<?php

namespace League\Twitter;

class TrendTest extends \PHPUnit_Framework_TestCase
{
    private $trend;

    public function setUp()
    {
        $this->trend = new Trend();
    }

    public function testCanCreateTrend()
    {
        $this->assertInstanceOf('League\Twitter\Trend', $this->trend);
    }
}
