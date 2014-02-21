<?php

namespace League\Twitter;

class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Api
     */
    private $api;

    public function setUp()
    {
        $this->api = new Api('-', '-', '-', '-');
    }

    private function mockHttpRequest($returnJson)
    {
        $mockHttpClient = $this->getMock('Guzzle\Http\Client', array('send'));
        $mockHttpResponse = $this->getMock('Guzzle\Http\Message\Request', array('getBody'), array(), '', false);
        $mockHttpClient->expects($this->any())->method('send')->will($this->returnValue($mockHttpResponse));
        $mockHttpResponse->expects($this->any())->method('getBody')->will($this->returnValue($returnJson));
        $this->api->setHttpHandler($mockHttpClient);
    }

    public function testCanCreateApi()
    {
        $this->assertInstanceOf('League\Twitter\Api', $this->api);
    }

    public function testGetSearchPHPLeague()
    {
        $this->mockHttpRequest('{"statuses": []}');
        $result = $this->api->getSearch('thephpleague');
        $this->assertEmpty($result);
    }
}
