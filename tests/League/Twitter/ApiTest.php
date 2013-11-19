<?php

namespace League\Twitter;

class ApiTest extends \PHPUnit_Framework_TestCase
{
    private $api;

    public function setUp()
    {
        $this->api = new Api();
    }

    public function testCanCreateApi()
    {
        $this->assertInstanceOf('League\Twitter\Api', $this->api);
    }
}
