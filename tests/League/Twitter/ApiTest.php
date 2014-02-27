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
        //$this->api = new Api('KTKKj2J6EekFTnRIzhnYDw','OfAhyG2DfT6kKMPmIvKnYwgVrETIs9cRxJbBl6Ys','97142648-B3f6MCwKPhLbSTMCK34ZbdrNWsaRFYaGPIw0ZTrzk','hJioDaVGp6zMWLDcmSk6Ed8UWyEyJnyGa5HNc4yIEuRW1');
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

    public function testSetCredentials()
    {
        $this->assertEquals('-', $this->api->getConsumerKey());
        $this->api->setCredentials('consumer_key', 'consumer_secret', 'access_token', 'access_token_secret');
        $this->assertEquals('consumer_key', $this->api->getConsumerKey());
        $this->assertEquals('consumer_secret', $this->api->getConsumerSecret());
        $this->assertEquals('access_token', $this->api->getAccessTokenKey());
        $this->assertEquals('access_token_secret', $this->api->getAccessTokenSecret());
    }

    public function testClearCredentials()
    {
        $this->assertEquals('-', $this->api->getConsumerKey());
        $this->api->clearCredentials();
        $this->assertEquals(null, $this->api->getConsumerKey());
        $this->assertEquals(null, $this->api->getConsumerSecret());
        $this->assertEquals(null, $this->api->getAccessTokenKey());
        $this->assertEquals(null, $this->api->getAccessTokenSecret());
    }

    public function testGetSearchPHPLeague()
    {
        $this->mockHttpRequest('{"statuses": []}');
        $result = $this->api->getSearch('thephpleague');
        $this->assertEmpty($result);
    }

    public function testGetUsersSearch()
    {
        $this->mockHttpRequest('[{"id": 2201822384, "name": "test1"},{"id": 2201822384, "name": "test2"}]');
        $result = $this->api->getUsersSearch('thephpleague');
        $this->assertInstanceOf('League\Twitter\User', $result[0]);
        $this->assertEquals(2, count($result));
    }

    public function testGetTrendsCurrent()
    {
        $this->mockHttpRequest('[{"trends": [{"name": "#thephpleague"}], "as_of": "2014-02-23T11:12:30Z" }]');
        $result = $this->api->getTrendsCurrent();
        $this->assertInstanceOf('League\Twitter\Trend', $result[0]);
        $this->assertEquals('#thephpleague', $result[0]->getName());
    }

    public function testGetHomeTimeline()
    {
        $this->mockHttpRequest('[{"created_at": "Sun Feb 23 11:32:39 +0000 2014", "id": 437550566881763328}, {"created_at": "Sun Feb 23 11:32:39 +0000 2014", "id": 423423423242342342}]');
        $result = $this->api->getHomeTimeline(2);
        $this->assertInstanceOf('League\Twitter\Status', $result[0]);
        $this->assertEquals('437550566881763328', $result[0]->getId());
    }

    public function testGetUserTimeline()
    {
        $this->mockHttpRequest('[{"created_at": "Sun Feb 23 09:06:54 +0000 2014", "id": 437513888762847232},{"created_at": "Sun Feb 23 09:06:54 +0000 2014","id": 437513888762847232}]');
        $result = $this->api->getUserTimeline(14336120);
        $this->assertInstanceOf('League\Twitter\Status', $result[0]);
        $this->assertEquals('437513888762847232', $result[0]->getId());
    }

    public function testGetStatus()
    {
        $this->mockHttpRequest('{"created_at": "Sat Mar 28 23:26:14 +0000 2009", "id": 1409441792}');
        $result = $this->api->getStatus(437513888762847232);
        $this->assertInstanceOf('League\Twitter\Status', $result);
        $this->assertEquals('1409441792', $result->getId());
    }
}
