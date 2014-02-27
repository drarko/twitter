<?php

namespace League\Twitter;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Status
     */
    private $status;

    public function setUp()
    {
        $this->status = new Status(array());
    }

    public function testCanCreateStatus()
    {
        $this->assertInstanceOf('League\Twitter\Status', $this->status);
    }

    public function testGetAndSetId()
    {
        $this->status->setId(42);
        $this->assertEquals(42, $this->status->getId());
    }

    public function testGetAndSetCreatedAt()
    {
        $this->status->setCreatedAt('now');
        $this->assertEquals('now', $this->status->getCreatedAt());
    }

    public function testGetAndSetFavourited()
    {
        $this->status->setFavorited(true);
        $this->assertEquals(true, $this->status->getFavorited());
    }

    public function testGetAndSetFavoritedCount()
    {
        $this->status->setFavoriteCount(42);
        $this->assertEquals(42, $this->status->getFavoriteCount());
    }

    public function testGetAndSetText()
    {
        $this->status->setText('text');
        $this->assertEquals('text', $this->status->getText());
    }

    public function testGetAndSetLocation()
    {
        $this->status->setLocation('world');
        $this->assertEquals('world', $this->status->getLocation());
    }

    public function testGetAndSetUser()
    {
        $this->status->setUser(new User(array()));
        $this->assertInstanceOf('League\Twitter\User', $this->status->getUser());
    }

    public function testGetAndSetNow()
    {
        $this->status->setNow(42);
        $this->assertEquals(42, $this->status->getNow());
    }
}
