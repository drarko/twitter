<?php

namespace League\Twitter;

class ListTwitterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ListTwitter
     */
    private $list;

    public function setUp()
    {
        $this->list = new ListTwitter(array());
    }

    public function testCanCreateList()
    {
        $this->assertInstanceOf('League\Twitter\ListTwitter', $this->list);
    }

    public function testGetAndSetId()
    {
        $this->list->setId(42);
        $this->assertEquals(42, $this->list->getId());
    }

    public function testGetAndSetName()
    {
        $this->list->setName('thephpleague');
        $this->assertEquals('thephpleague', $this->list->getName());
    }

    public function testGetAndSetSlug()
    {
        $this->list->setSlug('thephpleague');
        $this->assertEquals('thephpleague', $this->list->getSlug());
    }

    public function testGetAndSetDescription()
    {
        $this->list->setDescription('description');
        $this->assertEquals('description', $this->list->getDescription());
    }

    public function testGetAndSetFullName()
    {
        $this->list->setFullName('the PHP league');
        $this->assertEquals('the PHP league', $this->list->getFullName());
    }

    public function testGetAndSetMode()
    {
        $this->list->setMode('users');
        $this->assertEquals('users', $this->list->getMode());
    }

    public function testGetAndSetUri()
    {
        $this->list->setUri('uri://google.com');
        $this->assertEquals('uri://google.com', $this->list->getUri());
    }

    public function testGetAndSetMemberCount()
    {
        $this->list->setMemberCount(42);
        $this->assertEquals(42, $this->list->getMemberCount());
    }

    public function testGetAndSetSubscriberCount()
    {
        $this->list->setSubscriberCount(42);
        $this->assertEquals(42, $this->list->getSubscriberCount());
    }

    public function testGetAndSetFollowing()
    {
        $this->list->setFollowing(true);
        $this->assertEquals(true, $this->list->getFollowing());
    }

    public function testGetAndSetUser()
    {
        $this->list->setUser(new User(array()));
        $this->assertInstanceOf('League\Twitter\User', $this->list->getUser());
    }
}
